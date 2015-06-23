<?php
  $reader=new XMLReader();
  if (!$reader->open("./musique.xml")) die("Impossible d’ouvrir le fichier XML");
  $tomes=array();
  $months = array(  
    "01" => "janvier",
    "02" => "février",
    "03" => "mars",
    "04" => "avril",
    "05" => "mai",
    "06" => "juin",
    "07" => "juillet",
    "08" => "août",
    "09" => "septembre",
    "10" => "octobre",
    "11" => "novembre",
    "12" => "décembre"
  );
  while($reader->read()) {
    if ($reader->name == 'div' && $reader->nodeType == XMLReader::ELEMENT) {
      $div = new SimpleXMLElement($reader->readOuterXml());
      $idArt = $div->attributes()->xmlid[0]->__toString();//id de l’article
      $idVol = substr($idArt, 0, strpos($idArt, '_'));//id du tome
      $creation = substr($idVol, 3, 7);
      $year = substr($idVol, 3, 4);
      $month = substr($creation, 5, 2);
      $title = "Mercure Galant, $months[$month] $year";
      if (substr($idVol, -1)=='a') $title = "Mercure Galant, $months[$month], première partie, $year";
      if (substr($idVol, -1)=='b') $title = "Mercure Galant, $months[$month], deuxième partie, $year";
      if (substr($idVol, -1)=='e') $title = "Extraordinaire du Mercure Galant, Quartier de $months[$month] $year";
      $bibl = "<title>Mercure Galant</title>, <date>$months[$month] $year</date>.";
      if (substr($idVol, -1)=='a') $bibl = "<title>Mercure Galant</title>, $months[$month], première partie, <date>$year</date>.";
      if (substr($idVol, -1)=='b') $bibl = "<title>Mercure Galant</title>, $months[$month], deuxième partie, <date>$year</date>.";
      if (substr($idVol, -1)=='e') $bibl = "<title>Extraordinaire du Mercure Galant</title>, Quartier de <date>$months[$month] $year</date>.";
      $article = str_replace('&','&amp;',html_entity_decode($div->saveXML(), ENT_NOQUOTES, 'UTF-8'));//très MOCHE
      $article = substr($article, strpos($article, '?'.'>') + 2);//encore plus MOCHE...
      $article = str_replace(' xmlns="http://www.tei-c.org/ns/1.0"', '', $article);//on est plus à ça près...
      $article = str_replace('<div xmlid', '<div xml:id', $article);//vraiment pas!
      $header = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-model href="../../../teibook/teibook.rng" type="apllication/xml" schematypens="http://relaxng.org/ns/structure/1.0"?>
<?xml-stylesheet type="text/xsl" href="../../../teipub/xsl/tei2html.xsl"?>
<TEI xmlns="http://www.tei-c.org/ns/1.0" xml:lang="fr">
  <teiHeader>
    <fileDesc>
      <titleStmt>
        <title>'.$title.'</title>
      </titleStmt>
      <editionStmt>
        <edition>OBVIL/IReMuS</edition>
        <respStmt>
          <name>Nathalie Berton-Blivet</name>
          <resp>Responsable éditorial</resp>
        </respStmt>
        <respStmt>
          <name>Anne Piéjus</name>
          <resp>Responsable éditorial</resp>
          </respStmt>
        <respStmt>
          <name>Vincent Jolivet</name>
          <resp>Informatique</resp>
        </respStmt>
      </editionStmt>
      <publicationStmt>
        <publisher>Université Paris-Sorbonne, LABEX OBVIL</publisher>
        <date when="2015"/>
        <idno>http://obvil.paris-sorbonne.fr/corpus/mercure-galant/'.$idVol.'/</idno>
        <availability status="restricted">
          <licence target="http://creativecommons.org/licenses/by-nc-nd/3.0/fr/">
            <p>Copyright © 2015 Université Paris-Sorbonne, agissant pour le Laboratoire d’Excellence « Observatoire de la vie littéraire » (ci-après dénommé OBVIL).</p>
            <p>Cette ressource électronique protégée par le code de la propriété intellectuelle sur les bases de données (L341-1) est mise à disposition de la communauté scientifique internationale par l’OBVIL, selon les termes de la licence Creative Commons : « Attribution - Pas d’Utilisation Commerciale - Pas de Modification 3.0 France (CC BY-NC-ND 3.0 FR) ».</p>
            <p>Attribution : afin de référencer la source, toute utilisation ou publication dérivée de cette ressource électroniques comportera le nom de l’OBVIL et surtout l’adresse Internet de la ressource.</p>
            <p>Pas d’Utilisation Commerciale : dans l’intérêt de la communauté scientifique, toute utilisation commerciale est interdite.</p>
            <p>Pas de Modification : l’OBVIL s’engage à améliorer et à corriger cette ressource électronique, notamment en intégrant toutes les contributions extérieures. La diffusion de versions modifiées de cette ressource n’est pas souhaitable.</p> 
          </licence>
        </availability>
      </publicationStmt>
      <sourceDesc>
        <bibl>'.$bibl.'</bibl>
      </sourceDesc>
    </fileDesc>
    <profileDesc>
      <creation>
        <date when="'.$creation.'"/>
      </creation>
      <langUsage>
        <language ident="fr"/>
      </langUsage>
    </profileDesc>
  </teiHeader>
  <text>
    <body>';
      //TODO sauter les articles déjà publiés, cf artPublished.txt
      
      if(!isset($tomes[$idVol])) file_put_contents('xml/'.$idVol.'.xml', $header);
      file_put_contents("xml/$idVol.xml", $article, FILE_APPEND);
      $tomes[$idVol][]=$idArt;
    }
  }
  //print_r($tomes);
  
  //footer XML
  $footer='</body>
  </text>
</TEI>';
  foreach (glob("xml/*.xml") as $xmlfile) {
    file_put_contents($xmlfile, $footer, FILE_APPEND);
    }

?>