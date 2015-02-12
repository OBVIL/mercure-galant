<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE Ontology [
<!ENTITY xsd "http://www.w3.org/2001/XMLSchema#" >
]>
<xsl:transform version="1.1"
  xmlns="http://www.w3.org/2002/07/owl#"
  xmlns:th="http://www.semanticweb.org/mercure-galant/ontologie/thesaurus"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  exclude-result-prefixes="#default th">
  <xsl:output encoding="UTF-8" indent="no" method="xml"/>
  
  <!--
    Cette transformation peut produire des déclarations de classe récurrentes qu’il faut filtrer en sortie
    -->
  
  <xsl:template match="/">
    <Ontology xmlns="http://www.w3.org/2002/07/owl#"
      xml:base="http://www.semanticweb.org/mercure-galant/ontologie/mots-cles"
      xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
      xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
      xmlns:xml="http://www.w3.org/XML/1998/namespace"
      ontologyIRI="http://www.semanticweb.org/mercure-galant/ontologie/mots-cle">
      <Prefix name="" IRI="http://www.w3.org/2002/07/owl#"/>
      <Prefix name="owl" IRI="http://www.w3.org/2002/07/owl#"/>
      <Prefix name="rdf" IRI="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/>
      <Prefix name="xsd" IRI="http://www.w3.org/2001/XMLSchema#"/>
      <Prefix name="rdfs" IRI="http://www.w3.org/2000/01/rdf-schema#"/>
      <xsl:apply-templates select="//th:term"/>
    </Ontology>
  </xsl:template>
  
  <xsl:template name="skip"/>
  
  <!--
    instances des formes d’autorité et relation avec les éventuelles formes rejetées
    tpl moche (xsl:for-each) mais utile pour filtrer les lignes redondantes en sortie
  -->
  <xsl:template match="th:term">
    <xsl:param name="pos">
        <xsl:text>term[</xsl:text><xsl:value-of select="position()"/><xsl:text>] </xsl:text>
    </xsl:param>
    <!-- pour chaque entrée on crée l’individu, éventuellement la classe -->
    <xsl:for-each select="th:entree">
      <xsl:value-of select="$pos"/>
      <xsl:apply-templates select="."/>      
      <xsl:text>
</xsl:text>
    </xsl:for-each>
    <!-- pour chaque enfant, on exprime le lien de sous-classe -->
    <xsl:for-each select="th:enfant">
      <xsl:value-of select="$pos"/>
      <xsl:apply-templates select="."/>
      <xsl:text>
</xsl:text>    
    </xsl:for-each>
    <!-- une forme d’autorité peut avoir plusieurs formes rejetées : donc for-each nécessaire -->
    <xsl:for-each select="th:formeRejetee">
      <xsl:value-of select="$pos"/>
      <xsl:apply-templates select="."/>
      <xsl:text>
</xsl:text>    
    </xsl:for-each>
    <!--une forme rejetée à une et une seule forme d’autorité -->
    <xsl:if test="th:formeAutorite">
      <xsl:value-of select="$pos"/>
      <xsl:apply-templates select="th:formeAutorite"/>
      <xsl:text>
</xsl:text>
    </xsl:if>
    <!-- au plus, unique annotation par term -->
    <xsl:if test="th:note">
      <xsl:value-of select="$pos"/>
      <xsl:apply-templates select="th:note"/>
      <xsl:text>
</xsl:text>
    </xsl:if>
  </xsl:template>
  
  <!-- pour chaque entrée, on déclare toujours un individu et une classe (seulement si l’entrée est une forme d’autorité) -->
  <xsl:template match="th:entree">
    <xsl:param name="key">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>      
    </xsl:param>
    <xsl:if test="not(following-sibling::th:formeAutorite)">
      <classe>
      <xsl:attribute name="key">
        <xsl:value-of select="$key"/>
      </xsl:attribute>
      <xsl:value-of select="."/>
    </classe>
    </xsl:if>
    <individu>
      <xsl:attribute name="key">
        <xsl:value-of select="$key"/>
      </xsl:attribute>
      <xsl:value-of select="."/>
    </individu>
  </xsl:template>
  
  <!-- on traverse, on recrée l’arbre grâce à la relation en enfant -->
  <xsl:template match="th:parent"/>
  
  <xsl:template match="th:enfant">
    <xsl:param name="key">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>      
    </xsl:param>
    <relation>
      <xsl:attribute name="name">isSubClasseOf</xsl:attribute>
      <xsl:attribute name="of">
        <xsl:text>#</xsl:text>
        <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
      </xsl:attribute>
      <xsl:value-of select="$key"/>
    </relation>
  </xsl:template>
  
  <xsl:template match="th:formeRejetee">
    <relation>
      <xsl:attribute name="name">isRejectedFormOf</xsl:attribute>
      <xsl:attribute name="of">
        <xsl:text>#</xsl:text>
        <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
      </xsl:attribute>
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>
    </relation>
  </xsl:template>
  
  <xsl:template match="th:formeAutorite">
    <relation>
      <xsl:attribute name="name">isAuthorityFormOf</xsl:attribute>
      <xsl:attribute name="of">
        <xsl:text>#</xsl:text>
        <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
      </xsl:attribute>
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>
    </relation>
  </xsl:template>
  
  <xsl:template match="th:note">
    <annotation>
      <xsl:attribute name="of">
        <xsl:text>#</xsl:text>
        <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
      </xsl:attribute>
      <xsl:value-of select="."/>
    </annotation>
  </xsl:template>
 
</xsl:transform>