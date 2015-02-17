<?php
/*
 * Le projet Mercure Galant entretient des thésaurus de noms de personnes, de lieux, d’organisations et de mots-clés.
 * Ces thésaurus sont exprimés sous la forme d’une ontologie, à même d’exprimer des relations entre noms de personne et d’organisation par exemple.
 * Cette ontologie sert aussi à l’indexation des articles publiés :
 * https://obvil-dev.paris-sorbonne.fr/webprotege/#List:coll=Home;
 *
 * Cette classe Owl prend en entrée l’export RDF/XML de l’ontologie complète (cf lien "Download" de la page référencée ci-dessus).
 * Elle charge les données utiles à l’indexation des articles publiés dans la base de publication, mercure-galant.sqlite, conformément au schéma défini dans owl.sql.
 *
 * La classe Owl écrit dans mercure-galant.sqlite.
 * Les méthodes utiles à l’exploitation en lecture de cette base sont définies dans la classe Mercure.
 *
 * Les méthodes de la classe Owl se lancent en ligne de commande.
 *
 */
error_reporting(E_ALL);
//ini_set("display_errors",0);
//error_reporting(0);

// cli usage
set_time_limit(-1);
if (php_sapi_name() == "cli") Owl::doCli();

class Owl {
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
   * Création des tables utiles à l’indexation dans mercure-galant.sqlite
   */
  private function owlTables() {
    self::connect('./mercure-galant.sqlite');// en dur!
    self::$pdo->exec(file_get_contents(dirname(__FILE__).'/owl.sql'));
  }
  
  /*
   * Suppression des tables d’indexation
   */
  private function dropOwlTables() {
    self::connect('./mercure-galant.sqlite');// en dur!
    $tables=array("owl_allTags",
                  "owl_contains",
                  "owl_person_authorityForm",
                  "owl_person_rejectedForm",
                  "owl_topic");
    foreach($tables as $table) {
      $drop = self::$pdo->prepare("DROP TABLE IF EXISTS $table");
      $drop->execute();
      print "DROP TABLE $table\n";
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
      if ($this->reader->name == 'owl:NamedIndividual' && $this->reader->nodeType == XMLReader::ELEMENT) {
        $node = new SimpleXMLElement($this->reader->readOuterXml());
        // TODO: impossible de se débarasser de ce warning de merde
        $classeURL = strval($node->children('rdf',TRUE)->type->attributes('rdf',TRUE)->resource);
        $classe = substr($classeURL,Owl::OBUL);
        switch($classe) {
          // table contains (artid, termid, termtype)
          // tous les articles sont des individus la classe Article, on les repère donc grâce à la valeur rdf:type
          case 'Article':
            $article = substr($node->attributes('rdf',TRUE)->about,Owl::OBUL); // id de l’article
            //PERSONS by article indexation
            foreach($node->children()->contains_person as $person) {
              $personid = substr($person->attributes('rdf',TRUE)->resource,Owl::OBUL);
              print $article . " contains_person " . $personid ." [type:person]\n";
              //insertion en base
              $insert = self::$pdo->prepare("INSERT into owl_contains (article_id, tag_id, tag_type) VALUES (?, ?, ?)");
              $insert->execute(array($article, $personid, 'person'));
            }
            //TOPICS
            foreach($node->children()->contains_topic as $topic) {
              $topicid = substr($topic->attributes('rdf',TRUE)->resource,Owl::OBUL);
              print $article . " contains_topic " . $topicid ." [type:topic]\n";
              //insertion en base
              $insert = self::$pdo->prepare("INSERT into owl_contains (article_id, tag_id, tag_type) VALUES (?, ?, ?)");
              $insert->execute(array($article, $topicid, 'topic'));
            }
            //PLACES
            foreach($node->children()->contains_place as $place) {
              $topicid = substr($place->attributes('rdf',TRUE)->resource,Owl::OBUL);
              print $article . " contains_place " . $topicid ." [type:place]\n";
              //insertion en base
              $insert = self::$pdo->prepare("INSERT into owl_contains (article_id, tag_id, tag_type) VALUES (?, ?, ?)");
              $insert->execute(array($article, $topicid, 'place'));
            }     
            //CORPORATIONS
            foreach($node->children()->contains_corporation as $corporation) {
              $topicid = substr($corporation->attributes('rdf',TRUE)->resource,Owl::OBUL);
              print $article . " contains_corporation " . $topicid ." [type:corporation]\n";
              //insertion en base
              $insert = self::$pdo->prepare("INSERT into owl_contains (article_id, tag_id, tag_type) VALUES (?, ?, ?)");
              $insert->execute(array($article, $topicid, 'corporation'));
            }
            break;
          // tous les persName sont des individus la classe Article, on les repère donc grâce à la valeur rdf:type
          // table AuthorityPersonForm (apfid, label, comment)
          case 'AuthorityPersonForm':
            $apfid = substr($node->attributes('rdf',TRUE)->about,Owl::OBUL);
            $apflabel = $node->children('rdfs',TRUE)->label;
            $apfcomment = $node->children('rdfs',TRUE)->comment;
            print $apfid . " | " . $apflabel . " | " . $apfcomment . "\n";
            //insertion table des formes d’autorité
            $insert = self::$pdo->prepare("INSERT into owl_person_authorityForm (id, label, comment) VALUES (?, ?, ?)");
            $insert->execute(array($apfid, $apflabel, $apfcomment));
            //insertion table de tous les tags
            $insert = self::$pdo->prepare("INSERT into owl_allTags (id, label, parent, type) VALUES (?, ?, ?, ?)");
            $insert->execute(array($apfid, $apflabel, 'Person', 'person'));
            break;
          // table RejectedPersonForm (rpfid, label, apfid)
          case 'RejectedPersonForm':
            $rpfid = substr($node->attributes('rdf',TRUE)->about,Owl::OBUL);
            $rpflabel = $node->children('rdfs',TRUE)->label;
            $apfid = substr($node->children()->is_rejected_person_form_of->attributes('rdf',TRUE)->resource,Owl::OBUL);
            print $rpfid . " | " . $rpflabel . " | POUR: " . $apfid . "\n";
            // insertion en base
            $insert = self::$pdo->prepare("INSERT into owl_person_rejectedForm (id, label, apf_id) VALUES (?, ?, ?)");
            $insert->execute(array($rpfid, $rpflabel, $apfid));
            break;
        }
      }
      // table Topic (topicid, topiclabel, parent)
      // Là c’est un peu le bordel : on est pas en mesure ici de déterminer la classe racine (Topic, Place ou Corporation ?)
      // Le typage du tag se fait en passe 2 avec self::typeTag()
      elseif ($this->reader->name == 'owl:Class') {
        $node = new SimpleXMLElement($this->reader->readOuterXml());
        $parent = substr($node->children(rdfs,TRUE)->subClassOf->attributes('rdf',TRUE)->resource,Owl::OBUL);
        $skip = array("Article","AuthorityPersonForm","PersonForm","RejectedPersonForm");// super chip (s’assurer qu’on ne génère pas de doublon)...
        if($parent && !in_array($parent,$skip)) {
          $topicid = substr($node->attributes('rdf',TRUE)->about,Owl::OBUL);
          $topiclabel = $node->children('rdfs',TRUE)->label;
          echo "TOPIC: " . $topicid . " (" . $topiclabel . "): enfant de " . $parent . "\n";
          // insertion en base
          $insert = self::$pdo->prepare("INSERT INTO owl_topic (id, label, parent) VALUES (?, ?, ?)");
          $insert->execute(array($topicid, $topiclabel, $parent));
          //insertion table de tous les tags
          $insert = self::$pdo->prepare("INSERT INTO owl_allTags (id, label, parent, type) VALUES (?, ?, ?, ?)");
          $insert->execute(array($topicid, $topiclabel, $parent, 'tag'));
        }
      }
    }
    $this->reader->close();
    
  }
  
  //passe 2: typer les tags
  private function typeTag() {
    self::connect('./mercure-galant.sqlite');
    $sql="SELECT id FROM owl_allTags WHERE type='tag'";// inutile de rammaser les Persons déjà typés
    $tags=self::$pdo->query($sql)->fetchAll();
    foreach($tags as $tag) {
      $type=self::getType($tag['id']);
      print $type . " -> " . $tag['id']."\n";
      $sql="UPDATE owl_allTags SET type = ? WHERE id = ?";
      $up = self::$pdo->prepare($sql);
      $up->execute(array(self::getType($tag['id']),$tag['id']));
    }
  }
  
  // Déterminer le type (corporation, person, place, topic) d’un tag
  private function getType($tagId) {
    $sql='SELECT parent FROM owl_allTags WHERE id="'.$tagId.'"';//trouver le père du tag courant
    $dad = self::$pdo->prepare($sql);
    $dad->execute();
    $parentId = $dad->fetchColumn();
    if($parentId==true) return self::getType($parentId);
    else return lcfirst($tagId);
  }
  
  
  public static function doCli() {
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift arg 1, the script filepath
    if (!count($_SERVER['argv'])) exit("usage : php -f Owl.php (owl2sqlite|owlTables|dropOwlTables|typeTag) src.owl?\n");
    $method=null;//method to call
    $owlFile=null;//XML src
    $dest=null;
    $args=array();
    while ($arg=array_shift($_SERVER['argv'])) {
      // method
      if ($arg=="owl2sqlite" || $arg=="owlTables" || $arg=="dropOwlTables" || $arg=="typeTag") $method=$arg;
      else if(!$owlFile) $owlFile=$arg;
      else $args[]=$arg;
    }
    switch ($method) {
      case "owl2sqlite":
        $mercure = new Owl($owlFile);
        $mercure->owl2sqlite();
        break;
      case "owlTables":
        $mercure = new Owl(null);
        $mercure->owlTables();
        break;
      case "typeTag":
        $mercure = new Owl(null);
        $mercure->typeTag();
        break;
      case "dropOwlTables":
        $mercure = new Owl(null);
        $mercure->dropOwlTables();
    }
  }

}
?>