<?php
return array(
  "title" => "Mercure Galant", // Nom du corpus
  "srcdir" => dirname( __FILE__ ), // dossier source depuis lequel exécuter la commande de mise à jour
  "cmdup" => "git pull 2>&1", // commande de mise à jour
  "pass" => "", // Mot de passe à renseigner obligatoirement à l’installation
  "srcglob" => array( "xml/MG-*.xml" ), // sources XML à publier
  "sqlite" => "mercure-galant.sqlite", // nom de la base avec les métadonnées
  "formats" => "article, toc, epub, kindle", // formats différents à générer
);
?>
