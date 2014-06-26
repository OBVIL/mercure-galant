<?php
include_once(dirname(__FILE__).'/../teipub/Teipub.php');

// cli usage
set_time_limit(-1);
if (php_sapi_name() == "cli") Mercure::doCli();

class Mercure {
  /** file path of OWL file */
  private $owlFile;
  /** analyseur XML (XMLReader) */
  private $reader;
  
  
  function __construct($owlFile) {
    $this->owlFile = $owlFile;
    $this->reader = new XMLReader();
  }
  

  public function liste() {
    //print $this->owlFile . "\n";
    if (!$this->reader->open($this->owlFile)) die("Impossible d’ouvrir le fichier OWL");
    while($this->reader->read()) {
      /*
      if ($this->reader->nodeType == XMLReader::ELEMENT && $this->reader->name == 'NamedIndividual') {
        print substr($this->reader->getAttribute('IRI'),1) . "\n";
      }
      */
      if ($this->reader->name == 'ClassAssertion') {
        //$item = array();
        $node = new SimpleXMLElement($this->reader->readOuterXml());
        switch($node->Class['IRI']) {
          //sortir la liste des formes d’autorité
          case '#AuthorityForm':
          print "AUTORITÉ: " . substr($node->NamedIndividual['IRI'][0],1) . "\n";
          break;
        }
      }
      if ($this->reader->name == 'ObjectPropertyAssertion') {
        $node = new SimpleXMLElement($this->reader->readOuterXml());
        switch($node->ObjectProperty['IRI']) {
          case '#isAuthorityFormOf':
            print "RELATION: " . $node->NamedIndividual[0]['IRI'][0] . " isAuthorityFormOf " . $node->NamedIndividual[1]['IRI'][0] . "\n";
            break;
          case '#contains':
            print "CONTAINS: " . $node->NamedIndividual[0]['IRI'][0] . " contains " . $node->NamedIndividual[1]['IRI'][0] . "\n";
            break;
        }
      }
    }
    $this->reader->close();
  }



  public static function doCli() {
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift arg 1, the script filepath
    if (!count($_SERVER['argv'])) exit("usage : php -f Mercure.php (liste|todo) (src.owx)\n");
    $method=null;//method to call
    $owlFile=null;//XML src
    $dest=null;
    $args=array();
    while ($arg=array_shift($_SERVER['argv'])) {
      // method
      if ($arg=="liste" || $arg=="todo") $method=$arg;
      // first non method argument is supposed to be the source document
      else if(!$owlFile) $owlFile=$arg;
      // record other args for some commands
      else $args[]=$arg;
    }
    switch ($method) {
      case "liste":
        $mercure = new Mercure($owlFile);
        $mercure->liste();
        break;
      case "todo":
        echo "method todo\n";
        break;
    }
  }
}

?>