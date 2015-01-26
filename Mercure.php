<?php
//ini_set("display_errors",0);
error_reporting(E_ALL);
//error_reporting(0);

class Mercure {
  public $basehref;// racine Web du corpus
  private static $pdo;
  private $reader; //analyseur XML (XMLReader)
  const OBUL = 67; // ontology base URL length (http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#)
  
  function __construct($path="") {
    // TODO: voir avec Fréd ce mécanisme..
    // inutile de faire include de Web.php ???
    if(!$path) $path = Web::pathinfo();
    $this->basehref = Web::basehref($path);
    
    $this->reader = new XMLReader();
  }
  
  
  private function connect($sqlFile) {      
    if (!file_exists($sqlFile)) exit($sqlFile." doesn’t exist!\n");
    else {
      self::$pdo=new PDO("sqlite:".$sqlFile);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }
  }
  
  
  public function printPersonIndex() {
    self::connect('./mercure-galant.sqlite');
    // Chercher toutes les entrées d’index de type person présentes dans les documents publiés
    //NB: tous les articles indexés ne sont pas publiés (cf 3e cond. WHERE)
    //attention aux problèmes d’indexation cf Louis XIV en MG-1673-04_267 au lieu de MG-1673-04_266 -> lien crevé
    $sql="SELECT DISTINCT tag_id, label, comment
      FROM owl_contains, owl_person_authorityForm
      WHERE owl_contains.tag_id = owl_person_authorityForm.id
        AND tag_type='person'
        AND owl_contains.article_id IN (SELECT name FROM article)
      ORDER BY tag_id";
    print "<h1>Index des personnes</h1>";
    $i=1;
    foreach(self::$pdo->query($sql) as $person) {
      // dans la boucle, ramasser les id
      //print $i . ". ";
      //@id pour lier les tags à la pages persons
      // TODO : faire de même avec les topics
      ($person['comment']!='') ? $comment=' –'.$person['comment'] : $comment=null;
      print '<span id="'.$person['tag_id'].'">'.$person['label'].'</span> '.$comment.'<br/><ul>';
      //chercher les docs où apparaissent les persons
      $sql='SELECT article_id, title, created
        FROM owl_contains, article
        WHERE tag_id="'.$person['tag_id'].'"
        AND owl_contains.article_id = article.name';
      foreach(self::$pdo->query($sql) as $article) {
        $article_url = $this->basehref.substr($article['article_id'], 0, strpos($article['article_id'], '_'))."/".$article['article_id'];
        print '<li>['.$article['created'].'] <a href="'.$article_url.'">'.$article['title'].'</a></li>';
      }
      echo "</ul>";
      $i++;
    }
  }

  public function printTopicIndex() {
    self::connect('./mercure-galant.sqlite');
    $sql="SELECT DISTINCT tag_id, label
      FROM owl_contains, owl_topic
      WHERE owl_contains.tag_id = owl_topic.id
        AND tag_type='topic'
        AND owl_contains.article_id IN (SELECT name FROM article)
      ORDER BY tag_id";
    print "<h1>Index des mots-clés</h1>";
    print '<div id="topics"><ul class="tree">';
    foreach(self::$pdo->query($sql) as $topic) {
      //count pour la FLAMBE
      $occs='SELECT COUNT(article_id)
        FROM owl_contains
        WHERE tag_id="'.$topic['tag_id'].'"';
      foreach(self::$pdo->query($occs) as $occs) $occs=$occs[0];
      print '<li class="more" id="'.$topic['tag_id'].'">'.$topic['label']. ' ('.$occs.' occ)<ul>';
      //chercher les docs où apparaissent les persons
      $sql='SELECT article_id, title, created
        FROM owl_contains, article
        WHERE tag_id="'.$topic['tag_id'].'"
        AND owl_contains.article_id = article.name';
      foreach(self::$pdo->query($sql) as $article) {
        $article_url = $this->basehref.substr($article['article_id'], 0, strpos($article['article_id'], '_'))."/".$article['article_id'];
        $article_year = '<a href="'.$this->basehref.'?q=&start='.$article['created'].'">'.$article['created'].'</a>';
        print '<li>'.$article_year.': <a href="'.$article_url.'">'.$article['title'].'</a></li>';
      }
      echo "</ul></li>";
    }
    echo "</ul></div>";
  }
  
  
  public function printTags($related=true) {
    $article_id = basename($_SERVER['REQUEST_URI']);
    //echo $article_id;
    self::connect('./mercure-galant.sqlite');
    $sql='SELECT id, label, type
      FROM owl_allTags, owl_contains
      WHERE owl_contains.article_id = "'.$article_id.'"
      AND owl_contains.tag_id = owl_allTags.id';
    // voir si on a des résultats
    $results = self::$pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    if(!empty($results)) {
      print '<div id="tags"><ul class="tree"><li>Termes indexés<ul>';
      foreach(self::$pdo->query($sql) as $tag) {
        if ($tag['type']=="person") $page="persons";
        elseif ($tag['type']=="topic") $page="topics";
        print '<li><a href="../'.$page.'#'.$tag['id'].'">'.$tag['label'].'</a></li><br/>';
      }
      print '</ul></li></ul></div>';
      //ramasser les articles qui partagent les mêmes tags
      if($related===true) {
        $tagSet=self::$pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN, 'id');
        $this->relatedDoc($tagSet, $article_id);
      }
    }
  }
  
  //$currentArt id de l’article contexte (ne pas sortir l’id de l’article courant)
  //$tag = liste de tags en tableau
  public function relatedDoc($tagSet, $currentArt, $threshold=3) {
    //if(count($tagSet<$threshold)) break;
    //le tableau des articles par tag
    $artByTag=array();
    $select = self::$pdo->prepare("SELECT article_id FROM owl_contains WHERE tag_id = ? AND article_id != ?");
    foreach($tagSet as $tag) {
      $select->execute(array($tag,$currentArt));
      while ($row=$select->fetchAll(PDO::FETCH_COLUMN, 0)) {
        //print_r($row);
        $artByTag[$tag]=$row;
      }
    }
    //print_r($artByTag);//tous les articles indexés pour chaque tag de l’article courant
    // voir le nombre
    // toutes les occurrences d’un article dans un tableau
    $allOcc=array(); // tous les articles (avec les doublons présents dans $artByTag)
    foreach($artByTag as $tag) $allOcc=array_merge($allOcc,$tag);
    //print_r($allOcc);
    $vals = array_count_values($allOcc);//le nombre de tags en commun pour chaque article
    arsort($vals);//trier les articles par nombre de tags paratagés
    //print_r($vals);
    if(max($vals)>=$threshold) {
      print '<div id="related"><ul class="tree"><li>Voir aussi<ul>';
      foreach($vals as $art => $occ) {
        if ($occ>=$threshold){
          $stmt=self::$pdo->prepare('SELECT title FROM article WHERE name="'.$art.'"');
          $stmt->execute();
          $art_name=$stmt->fetch();
          //id de l’article associé
          ($art_name['title']!='') ? $title=$art_name['title'] : $title='<span style="color:red;">erreur indexation ('.$art.' n’existe pas)</span>';
          //TODO: méthode pour produire hred à partir de id
          $url = $this->basehref.substr($art, 0, strpos($art, '_'))."/".$art;
          //récupérer la liste des tags partagés pour chaque article
          $sql='SELECT tag_id, label
            FROM owl_contains, owl_allTags
            WHERE article_id="'.$art.'"
              AND owl_contains.tag_id=owl_allTags.id
              AND tag_id IN (SELECT tag_id FROM owl_contains WHERE article_id="'.$currentArt.'")';
          print '<li><b><a href="'.$url.'">'.$title.'<br/></b></a>';
          print "$occ mots-clés communs : ";
          $i=1;
          foreach(self::$pdo->query($sql) as $sharedTag) {
            print $sharedTag['label'];
            if($i < $occ) print ", ";
            $i++;
          }
          print "</li>";
        }
      }
      //fermeture de la div id related
      print '</ul></li></ul></div>';      
    }
  }
  
}
?>