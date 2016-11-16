================================================
Instance OBVIL de Webprotege
https://obvil-dev.paris-sorbonne.fr/webprotege/
================================================

***Installation de Webprotege
http://protegewiki.stanford.edu/wiki/WebProtegeAdminGuide


1. Installation de MongoDB (CentOS)

http://docs.mongodb.org/manual/tutorial/install-mongodb-on-red-hat-centos-or-fedora-linux/
Configuration
/etc/mongod.conf
NB : ajout de smallfiles=true, cf
http://docs.mongodb.org/manual/reference/configuration-options/#storage.smallFiles
http://stackoverflow.com/questions/14584393/why-getting-error-mongod-dead-but-subsys-locked-and-insufficient-free-space-for

Pour mémoire :
port=27017
ATTENTION : host=obvil-dev (important pour la configuration de Webprotege, cf ci-dessous).
sudo service mongod (start|stop|restart)
logpath=/var/log/mongodb/mongod.log
dbpath=/var/lib/mongo


2. Webprotege

2.1. Déployer webprotege.war (https://github.com/protegeproject/webprotege/releases)
/usr/share/tomcat/webapps/webprotege

2.2. Créer le data directory
/var/www/obvil-dev.paris-sorbonne.fr/data/webprotege
NB : donner les droits au user tomcat :
drwxrwsr-x 6 tomcat apache    4096 14 mai   13:24 webprotege

Configuration
/usr/share/tomcat/webapps/webprotege/webprotege.properties :
data.directory=/var/www/obvil-dev.paris-sorbonne.fr/data/webprotege
application.host=obvil-dev.paris-sorbonne.fr/webprotege

***POUR MÉMOIRE****************************************************************
*** mongodb.host=obvil-dev
*** ce paramétrage dans webprotege.properties empêche la connexion à la base...
*******************************************************************************

Compte Webprotege
Vincent Jolivet
passwd : obvilProtege


=====================
Quelques liens utiles
=====================

*** Redémarrer TOMCAT
sudo service tomcat start

***Exprimer un thesaurus : classe ou individus ?
https://mailman.stanford.edu/pipermail/protege-owl/2011-March/016403.html

***SPARQL pour RDF
http://www.w3.org/TR/rdf-sparql-query/
http://www.yoyodesign.org/doc/w3c/rdf-sparql-query/