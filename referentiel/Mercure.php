<?php
/*
 [2017-09] glorieux-f
 A été utilisé pour relier la publicaiton à une ontologie de métadonnées.
 Laissé pour mémoire, n'est plus branché à rien.
*/
//ini_set("display_errors",0);
error_reporting(E_ALL);
//error_reporting(0);
commande
// cli usage
set_time_limit(-1);
if (php_sapi_name() == "cli") Mercure::doCli();

class Mercure {
  public $basehref;// racine Web du corpus
  private static $pdo;
  private $reader; //analyseur XML (XMLReader)
  const OBUL = 67; // ontology base URL length (http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#)

  function __construct($path="") {
    $this->reader = new XMLReader();
  }


  private function connect($sqlFile) {
    if (!file_exists($sqlFile)) exit($sqlFile." doesn’t exist!\n");
    else {
      self::$pdo=new PDO("sqlite:".$sqlFile);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }
  }

  // Imprimer l’index des personnes
  public function printPersonIndex() {
    self::connect('./mercure-galant.sqlite');
    //chercher tous les tags person attachés aux documents publiés
    //NB: tous les articles indexés ne sont pas publiés (cf 3e cond. WHERE) + des erreurs d’indexation en base
    $sql="SELECT DISTINCT tag_id, label, comment
      FROM owl_contains, owl_person_authorityForm
      WHERE owl_contains.tag_id = owl_person_authorityForm.id
        AND tag_type='person'
        AND owl_contains.article_id IN (SELECT name FROM article)
      ORDER BY tag_id";
    print "<h1>Index des personnes</h1>";
    foreach(self::$pdo->query($sql) as $person) {
      ($person['comment']!='') ? $comment=' – '.$person['comment'] : $comment=null;
      print '<span id="'.$person['tag_id'].'">'.$person['label'].'</span> '.$comment.'<br/><ul>';
      //les articles où apparaissent les persons
      $sql='SELECT article_id, title, created
        FROM owl_contains, article
        WHERE tag_id="'.$person['tag_id'].'"
        AND owl_contains.article_id = article.name';
      foreach(self::$pdo->query($sql) as $article) {
        $article_url = $this->basehref.substr($article['article_id'], 0, strpos($article['article_id'], '_'))."/".$article['article_id'];
        print '<li>['.$article['created'].'] <a href="'.$article_url.'">'.$article['title'].'</a></li>';
      }
      echo "</ul>";
    }
  }


  /*
   * string printTagsIndex($classe, $full)
   * imprimer les thesaurus (corporation, place, topic)
   * string $classe : la classe du thésaurus (corporation, place ou topic)
   */
  public function printTagsIndex($classe, $full=true) {
    self::connect('./mercure-galant.sqlite');
    $parent=$classe;//$parent permet de définir la racine de l’arbre à imprimer (Topic, Corporation, Place par défaut. Mais on peut aussi partir de plus bas...)
    $selectAll = "SELECT id, label, type, parent FROM owl_allTags WHERE type = '".lcfirst($classe)."'";
    //ramasse les seuls tags utilisés; NB il faut aussi ramasser tous les tags racine pour générer l’arbre (UNION)
    //TODO: revoir algo: il faut rammasser les tags utilisés et TOUS LEURS PARENTS
    $selectUsed = "SELECT DISTINCT id, label, parent
        FROM owl_allTags, owl_contains
        WHERE owl_allTags.id = owl_contains.tag_id
          AND owl_contains.article_id IN (SELECT name FROM article)
      UNION
      SELECT id, label, parent
        FROM allTags
        WHERE parent='Topic'";
    $sql = ($full===true) ? $selectAll : $selectUsed;
    $sth = self::$pdo->prepare($sql);
    $sth->execute();
    $tags = $sth->fetchAll();
    switch ($classe) {
      case "Topic":
        $title="Index des Mots-clés";
        break;
      case "Place":
        $title="Index des Lieux";
        break;
      case "Corporation":
        $title="Index des corporations, institutions et sociétés savantes";
        break;
    }
    print "<h1>$title</h1>";
    print '<div id="thesaurus"><ul class="tree">';
    self::tagsTree($tags, $parent);
    print '</ul>';
  }

  //quick and dirty: méthode ligne de commande pour sortir les thesaurus dans des fichiers HTML (./doc/)
  //http://stackoverflow.com/questions/937627/how-to-redirect-stdout-to-a-file-in-php
  private function thesaurusFile($classe) {
    fclose(STDOUT);
    $path="doc/".lcfirst($classe)."s.html";
    $STDOUT = fopen($path, 'wb');
    $header='<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8"/>
    <title>Mercure Galant, thesaurus</title>
    <link rel="stylesheet" type="text/css" href="../../teipot/html.css" />
  </head>
  <body class="article">';
    $footer='</body>
</html>';
    fwrite($STDOUT, $header);
    fwrite($STDOUT, self::printTagsIndex($classe));
    fwrite($STDOUT, $footer);
    fclose($STDOUT);
  }

  /*
   * string tagsTree($allTags, $parent)
   * méthode récursive pour produire l’arbre des tags
   * array $allTags : tableau de tous les tags
   * string $parent : id du tag parent pour filtrer
   */
  private function tagsTree($allTags, $parent) {
    $tags = self::tagChilds($allTags, $parent);
    foreach($tags as $tag) {
      print '<li>'.$tag['label'].self::taggedDocsList($tag['id']);
      $tagChilds=self::tagChilds($allTags, $tag['id']);
      if(!empty($tagChilds)) {
        print "<ul>";
        self::tagsTree($allTags, $tag['id']);
        print "</ul>";
      }
    print "</li>";
    }
  }

  /*
   * array() tagChilds($tags, $parent)
   * ramasser tous les tags qui ont le même papa
   */
  private function tagChilds($tags, $parent) {
    return array_filter($tags, function($tag) use($parent) {if($tag['parent']==$parent) return $tag;});
  }

  /* Afficher en tête d’article les tags attachés */
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
        //pas classe: TODO voir comment ouvrir l’arbre sur l’ancre
        if ($tag['type']=="person") $page="persons";
        elseif ($tag['type']=="corporation") $page="corporations";
        elseif ($tag['type']=="place") $page="places";
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

  /*
   * afficher la liste formatée des articles pour un tag
   */
  private function taggedDocsList($tagId) {
    $htmlList='';
    $sql='SELECT article_id, title, created
      FROM owl_contains, article
      WHERE tag_id="'.$tagId.'"
      AND owl_contains.article_id = article.name';
    //$docs=self::$pdo->query($sql);
    $docs=self::$pdo->query($sql)->fetchAll();
    $freq=count($docs);
    if ($freq==0) return false;//on sort si pas d’article
    //TODO revoir la logique de cet affichage du compteur -> faire du JS ?
    $htmlList .= " ($freq)";
    $htmlList .= '<ul class="more">';
    foreach($docs as $doc) {
      $doc_url = $this->basehref.substr($doc['article_id'], 0, strpos($doc['article_id'], '_'))."/".$doc['article_id'];
      $doc_created = '<a href="'.$this->basehref.'?q=&start='.$doc['created'].'">'.$doc['created'].'</a>';
      $htmlList .= '<li>'.$doc_created.': <a href="'.$doc_url.'">'.$doc['title'].'</a></li>';
    }
    $htmlList .= "</ul>";
    return $htmlList;
  }

  //$currentArt id de l’article contexte (ne pas sortir l’id de l’article courant)
  //$tag = liste de tags en tableau
  public function relatedDoc($tagSet, $currentArt, $threshold=6) {
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


  // Pilote ligne de commande pour quelques méthodes seulement (génération de fichiers HTML)
  public static function doCli() {
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift arg 1, the script filepath
    if (!count($_SERVER['argv'])) exit("usage : php -f Mercure.php thesaurusFile (Corporation|Place|Topic)\n");
    $method=null;
    $classe=null;
    $args=array();
    while ($arg=array_shift($_SERVER['argv'])) {
      // method
      if ($arg=="thesaurusFile") $method=$arg;
      else if(!$classe) $classe=$arg;
      else $args[]=$arg;
    }
    switch ($method) {
      case "thesaurusFile":
        $mercure = new Mercure();
        $mercure->thesaurusFile($classe);
    }
  }




}
?>
