<?php
ini_set('display_errors', '1');
error_reporting(-1);
// prendre le pot
include (dirname(__FILE__).'/../teipot/Teipot.php');
// mettre le sachet SQLite dans le pot
$pot=new Teipot(dirname(__FILE__).'/mercure-galant.sqlite', 'fr');
// est-ce qu’un fichier statique (ex: epub) est attendu pour ce chemin ? 
// Si oui, l’envoyer maintenant depuis la base avant d’avoir écrit la moindre ligne
$pot->file($pot->path);
// chemin css, js ; baseHref est le nombre de '../' utile pour revenir en racine du site
$teipot=$pot->baseHref.'../teipot/';
// autres ressources spécifiques
$theme=$pot->baseHref.'../theme/';
// Si un document correspond à ce chemin, charger un tableau avec différents composants (body, head, breadcrumb…)
$doc=$pot->doc($pot->path);
// pas de body trouvé, charger des résultats en mémoire
if (!isset($doc['body'])) {
  $timeStart=microtime(true);
  $pot->search();
}
?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <?php 
if(isset($doc['head'])) echo $doc['head']; 
else echo '
<title>OBVIL, Mercure Galant</title>
';
    ?>
    <link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900,700italic,600italic' rel='stylesheet' type='text/css' />
    <link rel="stylesheet" type="text/css" href="<?php echo $teipot; ?>html.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $teipot; ?>teipot.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $theme; ?>obvil.css" />
  </head>
  <body>
    <div id="center">
      <div id="bordertop"> </div>
      <header id="header">
        <h1>
          <a href="<?php echo $pot->baseHref.'?'.$pot->qsa(); ?>">OBVIL, Mercure Galant</a>
        </h1>
        <a class="logo" href="http://obvil.paris-sorbonne.fr/"><img class="logo" src="<?php echo $theme; ?>img/logo-obvil.png" alt="OBVIL"></a>
      </header>
      <div id="contenu"><div id="contenu2">
        <aside id="aside">
          <?php
// les concordances peuvent être très lourdes, placer la nav sans attendre
// livre
if (isset($doc['bookid'])) {
  // auteur, titre, date
  echo "\n".'<header>';
  if ($doc['end']) echo "\n".'<div class="date">'.$doc['end'] .'</div>';
  if ($doc['byline']) echo "\n".'<div class="byline">'.$doc['byline'] .'</div>';
  echo "\n".'<a class="title" href="'.$pot->baseHref.$doc['bookname'].'/">'.$doc['title'].'</a>';
  echo "\n".'</header>';
  // rechercher dans ce livre
  echo '
  <form action=".#conc" name="searchbook" id="searchbook">
    <input name="q" id="q" onclick="this.select()" class="search" size="20" placeholder="Dans ce volume" title="Dans ce volume" value="'. str_replace('"', '&quot;', $pot->q) .'"/>
    <input type="image" id="go" alt="&gt;" value="&gt;" name="go" src="'. $theme . 'img/loupe.png"/>
  </form>
  ';
  // table des matières
  echo '
          <div id="toolpan" class="toc">
            <ul class="tabs">
              <li id="toc" onclick="this.parentNode.parentNode.className=this.id"><!--<span>Table des<br/> matières</span>--></li>
              <li id="download" onclick="this.parentNode.parentNode.className=this.id"><!--<span>Télécharger</span>--></li>
            </ul>
            <div class="toc">
              '.$doc['toc'].'
            </div>
            <div class="download">
               '.((isset($doc['download']))?$doc['download']:'').'
            </div>
          </div>
  ';
}
// accueil ? formulaire de recherche général
else {
  echo'
    <form action="">
      <input name="q" class="text" placeholder="Rechercher" value="'.str_replace('"', '&quot;', $pot->q).'"/>
      <div><label>De <input placeholder="année" name="start" class="year" value="'.$pot->start.'"/></label> <label>à <input class="year" placeholder="année" name="end" value="'. $pot->end .'"/></label></div>
      '.$pot->bylist().'
      <button type="reset" onclick="return Form.reset(this.form)">Effacer</button>
      <button type="submit">Rechercher</button>
    </form>
  ';
}
          ?>
        </aside>
        <div id="main">
          <nav id="toolbar">
            <?php
if (isset($doc['prevnext'])) echo $doc['prevnext'];    
            ?>
          </nav>
          <div id="article">
            <?php
if (isset($doc['body'])) {
  echo $doc['body'];
  // page d’accueil d’un livre avec recherche plein texte, afficher une concordance
  if ($pot->q && (!$doc['artname'] || $doc['artname']=='index')) echo $pot->concBook($doc['bookid']);
}
// pas de livre demandé, montrer un rapport général
else {
  // nombre de résultats
  echo $pot->report();
  // présentation chronologique des résultats
  echo $pot->chrono();
  // présentation bibliographique des résultats
  echo $pot->biblio(array('date', 'title', 'occs'));
  // concordance s’il y a recherche plein texte
  echo $pot->concByBook();
}
            ?>
          </div>
        </div>
      </div></div>
      <?php 
// footer
      ?>
    </div>
    <script type="text/javascript" src="<?php echo $teipot; ?>Tree.js">//</script>
    <script type="text/javascript" src="<?php echo $teipot; ?>Form.js">//</script>
    <script type="text/javascript" src="<?php echo $teipot; ?>Sortable.js">//</script>
    <script type="text/javascript"><?php if (isset($doc['js']))echo $doc['js']; ?></script>  
  </body>
</html>
