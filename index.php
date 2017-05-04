<?php
ini_set('display_errors', '1');
error_reporting(-1);
$conf = include( dirname(__FILE__)."/conf.php" );
include( dirname(dirname(__FILE__))."/Teinte/Web.php" );
include( dirname(dirname(__FILE__))."/Teinte/Base.php" );
$base = new Teinte_Base( $conf['sqlite'] );
$path = Teinte_Web::pathinfo(); // document demandé
$basehref = Teinte_Web::basehref(); //
$teinte = $basehref."../Teinte/";

// chercher le doc dans la base
$docid = current( explode( '/', $path ) );
$q = $base->pdo->prepare("SELECT * FROM doc WHERE code = ?; ");
$q->execute( array( $docid ) );
$doc = $q->fetch();

?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title><?php
if( $doc ) echo $doc['title'].' — ';
echo 'Mercure Galant, OBVIL';
    ?></title>
    <link rel="stylesheet" type="text/css" href="<?= $teinte ?>tei2html.css" />
    <link rel="stylesheet" type="text/css" href="<?= $basehref ?>../theme/obvil.css"/>
    <?php
// <link rel="stylesheet" type="text/css" href="semantic/theme/semantic.css" />
    ?>
    <style>
    </style>
  </head>
  <body>
    <div id="center">
      <header id="header">
        <h1><?php
          if ( !$path ) echo '<a href="//obvil.paris-sorbonne.fr/projets/mercure-galant">Projet : Mercure galant</a>';
          else echo '<a href="'.$basehref.'">Mercure Galant</a>';
        ?></h1>
        <a class="logo" href="http://obvil.paris-sorbonne.fr/"><img class="logo" src="<?php echo $basehref; ?>../theme/img/logo-obvil.png" alt="OBVIL"></a>
      </header>
      <div id="contenu">
        <aside id="aside">
          <?php
if ( $doc ) {
  // if (isset($doc['download'])) echo $doc['download'];
  // auteur, titre, date
  echo "\n".'<header>';
  echo "\n".'<a class="title" href="' . $basehref . $doc['code'] . '/">';
  echo $doc['title'].'</a>';
  echo "\n".'</header>';
  // table des matières, quand il y en a une
   if ( file_exists( $f="toc/".$doc['code']."_toc.html" ) ) readfile( $f );
}
// accueil ? formulaire de recherche général
else {
  /*
  echo'
    <form action="">
      <input name="q" class="text" placeholder="Rechercher" value="'.str_replace('"', '&quot;', $pot->q).'"/>
      <div><label>De <input placeholder="année" name="start" class="year" value="'.$pot->start.'"/></label> <label>à <input class="year" placeholder="année" name="end" value="'. $pot->end .'"/></label></div>
      <button type="reset" onclick="return Form.reset(this.form)">Effacer</button>
      <button type="submit">Rechercher</button>
    </form>
  ';
  */
}
          ?>
        </aside>
        <div id="main">
          <nav id="toolbar">
            <?php
            ?>
          </nav>
          <div id="article" class="<?php echo $doc['class']; ?>">
            <?php
if ( $doc ) {
  readfile( "article/".$doc['code']."_art.html" );
}
// pas de livre demandé, montrer un rapport général
else {
  readfile('doc/presentation.html');
  $base->biblio( array( "no", "date", "title" ) );
  /*
  TODO search
  // nombre de résultats
  echo $pot->report();
  // présentation chronologique des résultats
  echo $pot->chrono();
  // présentation bibliographique des résultats
  echo $pot->biblio(array('date', 'byline', 'title', 'occs'));
  // concordance s’il y a recherche plein texte
  echo $pot->concByBook();
  */
}
            ?>
          </div>
        </div>
      </div>
      <?php
// footer
      ?>
    </div>
    <script type="text/javascript" src="<?= $teinte ?>Tree.js">//</script>
    <script type="text/javascript" src="<?= $teinte ?>Sortable.js">//</script>
  </body>
</html>
