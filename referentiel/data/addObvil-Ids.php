<?php
  // ajouter les id Obvil à load_allLight.xml
  $newFile = fopen("load_allLightNEW.xml", "w") or die("Unable to open file!");
  $point = "?";
  fwrite($newFile, '<?xml version="1.0" encoding="UTF-8"?>'."\n<notices>");
  $months = array(  
    "janvier" => "01",
    "février" => "02",
    "mars" => "03",
    "avril" => "04",
    "mai" => "05",
    "juin" => "06",
    "juillet" => "07",
    "août" => "08",
    "septembre" => "09",
    "octobre" => "10",
    "novembre" => "11",
    "décembre" => "12"
  );
  $i=1;
  //read load_allLight.xml
  // 1739 notices
  /*
  <notice>
    <ref><e>16771003</e></ref>
    <base><e># compte-rendu</e></base>
    <lc><e>SAINT-LEU-LA-FORÊT</e></lc>
    <sre><e>Nouveau Mercure galant, tome X, [décembre], p. 264</e></sre>
    <mc><e>décès</e><e>organiste</e></mc>
    <cat><e>1677.10.05</e></cat>
    <sign><e>Camille Tanguy</e></sign>
    <nc><e>MICHEL, Mr [?-1677]</e></nc>
    <dat><e>1677.12</e></dat>
    <scat><e>16771003</e></scat>
  </notice>
  */
  
  $reader = new XMLReader();
  if (!$reader->open("load_allLight.xml")) die("Impossible loadLight");
  while($reader->read()) {
    //2e condition pour skip closing tag
    if ($reader->name == 'notice' && $reader->nodeType == XMLReader::ELEMENT) {
      print "\n===========\nnotice[$i]\n";
      $notice = new SimpleXMLElement($reader->readOuterXml());
      //la notice pour info
      $bibl=$notice->sre[0]->e[0];
      print "bibl (sre/e): $bibl\n";
      
      /*
       * YEAR, cat/e[1] (1737 occs), sinon scat/e[1] (1710 occs)
       */
      $year = (isset($notice->cat[0]->e)) ? substr($notice->cat[0]->e,0,4) : substr($notice->scat[0]->e,0,4);
      print "year: $year\n";
      
      /*
       * MONTH, pire à rammasser dans le champs texte sre/e (dans les autres champs, il s’agit du numéro du tome...)
       * INFERNAL! un coup on a : 1700.01.27, un autre 13.02.1700, ou bien 1672.06.11-1672.06.18
       * $month1 = candidat month 1: dat/e[1] (la première date indexée, par convention celle de parution) -> 1721 occs
       * $month2 = candidat month 2: sre/e (conversion en code numérique de la première occurrence de mois trouvé dans le champ biblio) -> 1705 occs
       * $month : $month1, sinon $month2, sinon "MM"
       */
      $month1 = (isset($notice->dat[0]->e[0])) ? substr($notice->dat[0]->e[0],5,2) : null;
      print "month1 (dat/e[1]): $month1\n";
      // mois, candidat 2 
      // sre/e: 1705 mentions de mois (janvier)|(février)|(mars)|(avril)|(mai)|(juin)|(juillet)|(août)|(septembre)|(octobre)|(novembre)|(décembre)
      $match = "/(janvier)|(février)|( mars )|(avril)|( mai )|(juin)|(juillet)|(août)|(septembre)|(octobre)|(novembre)|(décembre)/";
      preg_match($match,$bibl,$matches);
      $month2 = (isset($matches) && array_key_exists(trim($matches[0]), $months)) ? $months[trim($matches[0])] : null;
      print "month2 (sre/e): $month2\n";
      //if($month1!=null && $month2!=null && $month1!=$month2) print "WARNING: month error, notice $i\n";
      if(isset($month1)) $month=$month1;
      if(!isset($month) || !preg_match('/[0-9]{2}/',$month)) $month=$month2;
      if(!isset($month) || !preg_match('/[0-9]{2}/',$month)) $month="MM";
      print "MM: $month\n";
      
      //page (la première mention de p. dans le champ libre sre/e)
      $match = "/p\. ?([0-9]+)/";
      preg_match($match,$bibl,$matches);
      $page = (isset($matches[1])) ? $matches[1] : "ppp";
      if(strlen($page)==1) $page = "00".$page;
      if(strlen($page)==2) $page = "0".$page;
      print "page (sre/e): $page\n";
      
      // candidat OBVIL
      $obvil = "MG-".$year."-".$month."_".$page;
      print "cat_obvil: $obvil";
      
      //enrichir le fichier
      $notice->addChild('cat_Obvil', $obvil);
      /*
       * HACK DE MERDE POUR AVOIR DE L'UTF-8 AVEC SIMPLEXML
       * fwrite($newFile, str_replace('<?xml version="1.0"?>','',$notice->asXML()));
       */
      $dom = dom_import_simplexml($notice)->ownerDocument;
      $dom->encoding = 'UTF-8';
      fwrite($newFile, str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$dom->saveXML()));
      
      unset($year,$month,$page);
      $i++;
    }
  }
  fwrite($newFile,'</notices>');
  fclose($newFile);
?>