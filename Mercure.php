<?php
//TODO : écrire feature pour un doc similiaire (par ex. ayant au moins 2 tags en commun avec le doc courant)
//ini_set("display_errors",0);
error_reporting(E_ALL);
//error_reporting(0);
include_once(dirname(__FILE__).'/../teipub/Teipub.php');

// cli usage
set_time_limit(-1);
if (php_sapi_name() == "cli") Mercure::doCli();

class Mercure {
  private static $pdo;
  private $owlFile; //file path of OWL file
  private $reader; //analyseur XML (XMLReader)
  const OBUL = 67; // ontology base URL length (http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#)
  
  function __construct($owlFile) {
    $this->owlFile = $owlFile;
    $this->reader = new XMLReader();
  }
  
  private function connect($sqlFile) {
  if (!file_exists($sqlFile)) exit($sqlFile." doesn’t exist!\n");
  else {
    self::$pdo=new PDO("sqlite:".$sqlFile);
    self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }
  } 

  /*
   * charge les champs utiles de mercure-galant.owl dans mercure-galant.sqlite
   * NB input: fichier RDF/XML (.owl) produit à l’export par WebProtege (et non Protege Standalone)
   */
  private function owl2sqlite() {
    self::connect('./mercure-galant.sqlite');// en dur!
    // input: fichier RDF/XML (.owl) produit à l’export par WebProtege
    if (!$this->reader->open($this->owlFile)) die("Impossible d’ouvrir le fichier OWL");
    while($this->reader->read()) {
      if ($this->reader->name == 'owl:NamedIndividual') {
        $node = new SimpleXMLElement($this->reader->readOuterXml());
        // TODO: impossible de se débarasser de ce warning de merde
        $classeURL = strval($node->children('rdf',TRUE)->type->attributes('rdf',TRUE)->resource);
        $classe = substr($classeURL,Mercure::OBUL);
        switch($classe) {
          // table contains (artid, termid, termtype)
          case 'Article':
            $article = "MG-".substr($node->attributes('rdf',TRUE)->about,Mercure::OBUL); // id de l’article
            foreach($node->children()->contains_person as $person) {
              $personid = substr($person->attributes('rdf',TRUE)->resource,Mercure::OBUL);
              print $article . " contains_person " . $personid ." [type:person]\n";
              //insertion en base
              $insert = self::$pdo->prepare("INSERT into owl_contains (article_id, tag_id, tag_type) VALUES (?, ?, ?)");
              $insert->execute(array($article, $personid, 'person'));
            }
            foreach($node->children()->contains_topic as $topic) {
              $topicid = substr($topic->attributes('rdf',TRUE)->resource,Mercure::OBUL);
              print $article . " contains_topic " . $topicid ." [type:topic]\n";
              //insertion en base
              $insert = self::$pdo->prepare("INSERT into owl_contains (article_id, tag_id, tag_type) VALUES (?, ?, ?)");
              $insert->execute(array($article, $topicid, 'topic'));
            }            
            break;
          // table AuthorityPersonForm (apfid, label, comment)
          case 'AuthorityPersonForm':
            $apfid = substr($node->attributes('rdf',TRUE)->about,Mercure::OBUL);
            $apflabel = $node->children('rdfs',TRUE)->label;
            $apfcomment = $node->children('rdfs',TRUE)->comment;
            print $apfid . " | " . $apflabel . " | " . $apfcomment . "\n";
            //insertion table des formes d’autorité
            $insert = self::$pdo->prepare("INSERT into owl_person_authorityForm (id, label, comment) VALUES (?, ?, ?)");
            $insert->execute(array($apfid, $apflabel, $apfcomment));
            //insertion table de tous les tags
            $insert = self::$pdo->prepare("INSERT into owl_allTags (id, label, type) VALUES (?, ?, ?)");
            $insert->execute(array($apfid, $apflabel, 'person'));
            break;
          // table RejectedPersonForm (rpfid, label, apfid)
          case 'RejectedPersonForm':
            $rpfid = substr($node->attributes('rdf',TRUE)->about,Mercure::OBUL);
            $rpflabel = $node->children('rdfs',TRUE)->label;
            $apfid = substr($node->children()->is_rejected_person_form_of->attributes('rdf',TRUE)->resource,Mercure::OBUL);
            print $rpfid . " | " . $rpflabel . " | POUR: " . $apfid . "\n";
            // insertion en base
            $insert = self::$pdo->prepare("INSERT into owl_person_rejectedForm (id, label, apf_id) VALUES (?, ?, ?)");
            $insert->execute(array($rpfid, $rpflabel, $apfid));
            break;
        }
      }
      // table Topic (topicid, topiclabel, parent)
      elseif ($this->reader->name == 'owl:Class') {
        $node = new SimpleXMLElement($this->reader->readOuterXml());
        $parent = substr($node->children(rdfs,TRUE)->subClassOf->attributes('rdf',TRUE)->resource,Mercure::OBUL);
        $skip = array("Article","AuthorityPersonForm","PersonForm","RejectedPersonForm");// super chip (utile a priori que pour PersonForm)...
        if($parent && !in_array($parent,$skip)) {
          $topicid = substr($node->attributes('rdf',TRUE)->about,Mercure::OBUL);
          $topiclabel = $node->children('rdfs',TRUE)->label;
          echo "TOPIC: " . $topicid . " (" . $topiclabel . "): enfant de " . $parent . "\n";
          // insertion en base
          $insert = self::$pdo->prepare("INSERT INTO owl_topic (id, label, parent) VALUES (?, ?, ?)");
          $insert->execute(array($topicid, $topiclabel, $parent));
          //insertion table de tous les tags
          $insert = self::$pdo->prepare("INSERT INTO owl_allTags (id, label, type) VALUES (?, ?, ?)");
          $insert->execute(array($topicid, $topiclabel, 'topic'));
        }
      }
    }
    $this->reader->close();
    // fin de la lecture on peut générer quelques tables utiles pour les traitements à venir
    // une table avec tous les tags id, label, type
    // TODO: implémenter trigger pour éviter le SELECT?
    /*
    $allTags='SELECT id, label FROM owl_person_authorityForm UNION SELECT id, label FROM owl_topic';
    foreach(self::$pdo->query($allTags) as $tag) {
      $insert = self::$pdo->prepare("INSERT into owl_allTags (id, label) VALUES (?, ?)");
      $insert->execute(array($tag['id'], $tag['label']));
    }
    */
    
    
  }
  
  private function owlTables() {
    self::connect('./mercure-galant.sqlite');// en dur!
    self::$pdo->exec(file_get_contents(dirname(__FILE__).'/ontology.sql'));
  }
  private function dropOwlTables() {
    self::connect('./mercure-galant.sqlite');// en dur!
    $drop = self::$pdo->prepare("DROP TABLE owl_person_authorityForm; DROP TABLE owl_contains; DROP TABLE owl_person_rejectedForm ; DROP TABLE owl_topic");
    $drop->execute();
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
        $article_url = "http://localhost/~bolsif/corpus/mercure-galant/".substr($article['article_id'], 0, strpos($article['article_id'], '_'))."/".$article['article_id'];
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
        $article_url = "http://localhost/~bolsif/corpus/mercure-galant/".substr($article['article_id'], 0, strpos($article['article_id'], '_'))."/".$article['article_id'];
        $article_year = '<a href="http://localhost/~bolsif/corpus/mercure-galant/?q=&start='.$article['created'].'">'.$article['created'].'</a>';
        print '<li>'.$article_year.': <a href="'.$article_url.'">'.$article['title'].'</a></li>';
      }
      echo "</ul></li>";
    }
    echo "</ul></div>";
  }
  
  
  public function printTags() {
    $article_id = basename($_SERVER['REQUEST_URI']);
    //echo $article_id;
    self::connect('./mercure-galant.sqlite');
    $sql='SELECT id, label, type
      FROM owl_allTags, owl_contains
      WHERE owl_contains.article_id = "'.$article_id.'"
      AND owl_contains.tag_id = owl_allTags.id';
      //TODO: test si la requête renvoie des résultats
    print '<div id="tags"><ul class="tree"><li>Mots-clés<ul>';
    foreach(self::$pdo->query($sql) as $tag) {
      if ($tag['type']=="person") $page="persons";
      elseif ($tag['type']=="topic") $page="topics";
      print '<li><a href="../'.$page.'#'.$tag['id'].'">'.$tag['label'].'</a></li><br/>';
    }
    print '</ul></li>
    </ul></div>';
    //TODO
    //essayer de ramasser les articles qui partagent les mêmes tags
    $tagSet=self::$pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN, 'id');
    $this->similarArt($tagSet, $article_id);
  }
  
  //$art id de l’article contexte (ne pas sortir l’id de l’article courant)
  //$tag = liste de tags en tableau
  public function similarArt($tagSet, $art, $threshold=3) {
    //print_r($tagSet);
    //le tableau des articles par tag
    $artByTag=array();
    $select = self::$pdo->prepare("SELECT article_id FROM owl_contains WHERE tag_id = ? AND article_id != ?");
    foreach($tagSet as $tag) {
      $select->execute(array($tag,$art));
      while ($row=$select->fetchAll(PDO::FETCH_COLUMN, 0)) {
        //print_r($row);
        $artByTag[$tag]=$row;
      }
      
    }
    //print_r($artByTag);//tous les articles indexés pour chaque tag
    // voir le nombre
    // toutes les occurrences d’un article dans un tableau
    $allOcc=array(); // tous les articles (avec les doublons présents dans $artByTag)
    foreach($artByTag as $tag) $allOcc=array_merge($allOcc,$tag);
    //print_r($allOcc);
    $vals = array_count_values($allOcc);//le nombre de tags en commun pour chaque article
    //print_r($vals);
    foreach($vals as $art => $occ) {
      if ($occ>=$threshold) print "$occ tags communs avec $art<br/>";
    }
  }
  

  public static function doCli() {
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift arg 1, the script filepath
    if (!count($_SERVER['argv'])) exit("usage : php -f Mercure.php (owl2sqlite|owlTables) (src.owx)\n");
    $method=null;//method to call
    $owlFile=null;//XML src
    $dest=null;
    $args=array();
    while ($arg=array_shift($_SERVER['argv'])) {
      // method
      if ($arg=="owl2sqlite" || $arg=="owlTables" || $arg=="dropOwlTables") $method=$arg;
      // first non method argument is supposed to be the source document
      else if(!$owlFile) $owlFile=$arg;
      else $args[]=$arg;
    }
    switch ($method) {
      case "owl2sqlite":
        $mercure = new Mercure($owlFile);
        $mercure->owl2sqlite();
        break;
      case "owlTables":
        $mercure = new Mercure();
        $mercure->owlTables();
        break;
      case "dropOwlTables":
        $mercure = new Mercure();
        $mercure->dropOwlTables();
    }
  }
}



/********************* Memo et doc */

/*
 * doc RDF/XML pour création de la table contains
 *
 *
 *
  <owl:NamedIndividual rdf:about="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#1672-01_065">
    <rdf:type rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#Article"/>
    <url rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">http://localhost/~bolsif/corpus/mercure-galant/MG-1672-01/MG-1672-01_065</url>
    <contains_person rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#LOIR,_Mr_du"/>
    <contains_person rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#RACINE,_Jean_[1639-1699]"/>
    <contains_topic rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#actualité_théâtrale"/>
    <contains_topic rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#commentaire_de_l&apos;intrigue"/>
    <contains_topic rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#création_d&apos;oeuvre"/>
    <contains_topic rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#exotisme"/>
    <contains_topic rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#tragédie"/>
  </owl:NamedIndividual>      
*/

/*
 * doc RDF/XML pour création de la table AuthorityPersonForm
 *
  <owl:NamedIndividual rdf:about="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#FONTMORT,_Mr_de">
    <rdf:type rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#AuthorityPersonForm"/>
    <rdfs:label rdf:datatype="http://www.w3.org/2001/XMLSchema#string">FONTMORT, Mr de</rdfs:label>
    <rdfs:comment rdf:datatype="http://www.w3.org/2001/XMLSchema#string">président et lieutenant général à Niort (1685.11 et 1692.08).</rdfs:comment>
  </owl:NamedIndividual>
*/

/*
 * doc RDF/XML pour création de la table RejectedPersonForm
 *
  <owl:NamedIndividual rdf:about="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#LOUIS_DE_FRANCE_[1682-1712]">
    <rdf:type rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#RejectedPersonForm"/>
    <rdfs:label rdf:datatype="http://www.w3.org/2001/XMLSchema#string">LOUIS DE FRANCE [1682-1712]</rdfs:label>
    <is_rejected_person_form_of rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#BOURGOGNE,_Louis_de_France_[1682-1712],_duc_de"/>
  </owl:NamedIndividual>
*/

/*
 * doc RDF/XML pour création de la table Topic
 *
  <owl:Class rdf:about="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#versification">
    <rdfs:label rdf:datatype="http://www.w3.org/2001/XMLSchema#string">versification</rdfs:label>
    <rdfs:subClassOf rdf:resource="http://www.semanticweb.org/mercure-galant/ontologie/mercure-galant#Topic"/>
  </owl:Class>
*/
 

/*
 * sérialisation OWL/XML (que des triplets) : mercure-galant.owx
 * 
  if ($this->reader->name == 'ClassAssertion') {
    $node = new SimpleXMLElement($this->reader->readOuterXml());
    switch($node->Class['IRI']) {
    //sortir la liste des formes d’autorité
    case '#AuthorityPersonForm':
      print "AUTORITÉ: " . substr($node->NamedIndividual['IRI'][0],1) . "\n";
      break;
    }
  }
  if ($this->reader->name == 'ObjectPropertyAssertion') {
    $node = new SimpleXMLElement($this->reader->readOuterXml());
    switch($node->ObjectProperty['IRI']) {
      case '#is_authority_person_form_of':
        print "RELATION: " . $node->NamedIndividual[0]['IRI'][0] . " is_authority_person_form_of " . $node->NamedIndividual[1]['IRI'][0] . "\n";
        break;
      case '#contains_person':
        print "CONTAINS: " . $node->NamedIndividual[0]['IRI'][0] . " contains_person " . $node->NamedIndividual[1]['IRI'][0] . "\n";
        break;
    }
  }
*/
?>