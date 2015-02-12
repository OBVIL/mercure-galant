<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE Ontology [
<!ENTITY xsd "http://www.w3.org/2001/XMLSchema#" >
]>
<xsl:transform version="1.1"
  xmlns="http://www.w3.org/2002/07/owl#"
  xmlns:th="http://www.semanticweb.org/mercure-galant/ontologie/thesaurus"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  exclude-result-prefixes="#default th">
  <xsl:output encoding="UTF-8" indent="yes" method="xml"/>
  
  <!--
    Génération de l’indexation des articles du Mercure Galant, sous la forme d’une ontologie :
    ** Création de la classe Article
    ** Création des individus de la classe Article
    ** Indexation nom des individus de la classe Article
    ** Indexation mot-clé des individus de la classe Article
    ** Indexation lieux des individus de la classe Article
    input : loadUTF8.xml
    output : complète les ontologies noms.owl, mots-cles.owl et lieux.owl en indexant les articles.
    
    TODO: GARDER AUSSI L'ID HISTORIQUE DES ARTICLES POUR L'INDEXATION
  -->
  
  <xsl:template match="/">
    <Ontology
      xml:base="http://www.semanticweb.org/mercure-galant/ontologie/indexation-mercure"
      xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
      xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
      xmlns:xml="http://www.w3.org/XML/1998/namespace"
      ontologyIRI="http://www.semanticweb.org/mercure-galant/ontologie/indexation-mercure">
      <Prefix name="rdf" IRI="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/>
      <Prefix name="rdfs" IRI="http://www.w3.org/2000/01/rdf-schema#"/>
      <Prefix name="xsd" IRI="http://www.w3.org/2001/XMLSchema#"/>
      <Prefix name="owl" IRI="http://www.w3.org/2002/07/owl#"/>
      
      <!-- déclaration des OBJECT PROPERTIES d’indexation (nécessaire pour la définition des ranges et domains) -->
      <Declaration>
        <ObjectProperty IRI="#contains_person"/>
      </Declaration>
      <Declaration>
        <ObjectProperty IRI="#contains_topic"/>
      </Declaration>
      <Declaration>
        <ObjectProperty IRI="#contains_corporation"/>
      </Declaration>      
      <Declaration>
        <ObjectProperty IRI="#contains_place"/>
      </Declaration>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#contains_person"/>
        <Class IRI="#Article"/>
      </ObjectPropertyDomain>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#contains_topic"/>
        <Class IRI="#Article"/>
      </ObjectPropertyDomain>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#contains_corporation"/>
        <Class IRI="#Article"/>
      </ObjectPropertyDomain>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#contains_place"/>
        <Class IRI="#Article"/>
      </ObjectPropertyDomain>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#contains_person"/>
        <Class IRI="#AuthorityPersonForm"/>
      </ObjectPropertyRange>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#contains_topic"/>
        <Class IRI="#Topic"/>
      </ObjectPropertyRange>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#contains_corporation"/>
        <Class IRI="#Corporation"/>
      </ObjectPropertyRange>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#contains_place"/>
        <Class IRI="#Place"/>
      </ObjectPropertyRange>
      
      <xsl:apply-templates select="//th:notice"/>
    </Ontology>
  </xsl:template>
  
  
  <xsl:template match="th:notice">
    <xsl:apply-templates select="th:cat_Obvil/th:e"/>
  </xsl:template>
  
  <!-- Création des individus de la classe Article (tous les articles indexés du Mercure) -->
  <xsl:template match="th:e">
    <!-- Produire la clé d’identification -->
    <xsl:param name="entID">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>
    </xsl:param>
    <Declaration>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>
      </NamedIndividual>
    </Declaration>
    <ClassAssertion>
      <Class IRI="#Article"/>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>        
      </NamedIndividual>
    </ClassAssertion>
    <!-- construction de l’URL de réf pour chaque article -->
    <DataPropertyAssertion>
      <DataProperty IRI="#url"/>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>
      </NamedIndividual>
      <Literal datatypeIRI="&xsd;anyURI">
        <xsl:text>http://obvil.paris-sorbonne.fr/corpus/mercure-galant/</xsl:text>
        <xsl:value-of select="substring-before(.,'_')"/>
        <xsl:text>/</xsl:text>
        <xsl:value-of select="."/>
      </Literal>
    </DataPropertyAssertion>
    
    <!-- indexation Person -->
    <xsl:if test="../../th:nc/th:e">
      <xsl:for-each select="../../th:nc/th:e">
        <ObjectPropertyAssertion>
          <ObjectProperty IRI="#contains_person"/>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:value-of select="$entID"/>
            </xsl:attribute>
          </NamedIndividual>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:text>#</xsl:text>
              <xsl:value-of select="normalize-space(translate(.,' ','_'))"/>
            </xsl:attribute>
          </NamedIndividual>
        </ObjectPropertyAssertion>
      </xsl:for-each>
    </xsl:if>
    
    <!-- indexation mots-clés -->
    <xsl:if test="../../th:mc/th:e">
      <xsl:for-each select="../../th:mc/th:e">
        <ObjectPropertyAssertion>
          <ObjectProperty IRI="#contains_topic"/>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:value-of select="$entID"/>
            </xsl:attribute>
          </NamedIndividual>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:text>#</xsl:text>
              <xsl:value-of select="normalize-space(translate(.,' ','_'))"/>
            </xsl:attribute>
          </NamedIndividual>
        </ObjectPropertyAssertion>
      </xsl:for-each>
    </xsl:if>
    
    <!-- indexation corporations -->
    <xsl:if test="../../th:lcinst/th:e">
      <xsl:for-each select="../../th:lcinst/th:e">
        <ObjectPropertyAssertion>
          <ObjectProperty IRI="#contains_corporation"/>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:value-of select="$entID"/>
            </xsl:attribute>
          </NamedIndividual>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:text>#</xsl:text>
              <xsl:value-of select="normalize-space(translate(.,' ','_'))"/>
            </xsl:attribute>
          </NamedIndividual>
        </ObjectPropertyAssertion>
      </xsl:for-each>
    </xsl:if>
    
    <!-- indexation lieux -->
    <xsl:if test="../../th:lc/th:e">
      <xsl:for-each select="../../th:lc/th:e">
        <ObjectPropertyAssertion>
          <ObjectProperty IRI="#contains_place"/>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:value-of select="$entID"/>
            </xsl:attribute>
          </NamedIndividual>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:text>#</xsl:text>
              <xsl:value-of select="normalize-space(translate(.,' ','_'))"/>
            </xsl:attribute>
          </NamedIndividual>
        </ObjectPropertyAssertion>
      </xsl:for-each>
    </xsl:if>
    
  </xsl:template>
  
</xsl:transform>