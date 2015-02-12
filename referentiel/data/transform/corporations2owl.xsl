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
      xml:base="http://www.semanticweb.org/mercure-galant/ontologie/corporation"
      xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
      xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
      xmlns:xml="http://www.w3.org/XML/1998/namespace"
      ontologyIRI="http://www.semanticweb.org/mercure-galant/ontologie/corporation">
      <Prefix name="" IRI="http://www.w3.org/2002/07/owl#"/>
      <Prefix name="owl" IRI="http://www.w3.org/2002/07/owl#"/>
      <Prefix name="rdf" IRI="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/>
      <Prefix name="xsd" IRI="http://www.w3.org/2001/XMLSchema#"/>
      <Prefix name="rdfs" IRI="http://www.w3.org/2000/01/rdf-schema#"/>
      
      <!-- Schéma des Corporation -->
      <Declaration>
        <Class IRI="#Corporation"/>
      </Declaration>
      <Declaration>
        <ObjectProperty IRI="#is_authority_corporation_form_of"/>
      </Declaration>
      <Declaration>
        <ObjectProperty IRI="#is_rejected_corporation_form_of"/>
      </Declaration>
      <FunctionalObjectProperty>
        <ObjectProperty IRI="#is_rejected_corporation_form_of"/>
      </FunctionalObjectProperty>
      <InverseFunctionalObjectProperty>
        <ObjectProperty IRI="#is_authority_corporation_form_of"/>
      </InverseFunctionalObjectProperty>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#is_authority_corporation_form_of"/>
        <Class IRI="#Corporation"/>
      </ObjectPropertyDomain>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#is_rejected_corporation_form_of"/>
        <Class IRI="#Corporation"/>
      </ObjectPropertyDomain>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#is_authority_corporation_form_of"/>
        <Class IRI="#Corporation"/>
      </ObjectPropertyRange>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#is_rejected_corporation_form_of"/>
        <Class IRI="#Corporation"/>
      </ObjectPropertyRange>
      <!-- fin du schéma -->
      <xsl:apply-templates select="//th:term[not(th:formeAutorite)]"/>
    </Ontology>
  </xsl:template>
  
  <!-- on traverse -->
  <xsl:template match="th:parent"/>
  
  <!-- pour chaque entrée (seulement si l’entrée est une forme d’autorité) -->
  <xsl:template match="th:entree">
    <xsl:param name="key">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>      
    </xsl:param>
    <!-- déclaration de la classe -->
    <Declaration>
      <Class>
        <xsl:attribute name="IRI"><xsl:value-of select="$key"/></xsl:attribute>
      </Class>
    </Declaration>
    <!-- label de la classe -->
    <AnnotationAssertion>
      <AnnotationProperty abbreviatedIRI="rdfs:label"/>
      <IRI><xsl:value-of select="$key"/></IRI>
      <Literal datatypeIRI="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select="normalize-space(.)"/></Literal>
    </AnnotationAssertion>
    <!-- déclaration de l’individu -->
    <Declaration>
      <NamedIndividual>
        <xsl:attribute name="IRI"><xsl:value-of select="$key"/></xsl:attribute>
      </NamedIndividual>
    </Declaration>
    <!-- label de l’individu (redondant avec le label de classe...)
      <AnnotationAssertion>
      <AnnotationProperty abbreviatedIRI="rdfs:label"/>
      <IRI><xsl:value-of select="$key"/></IRI>
      <Literal datatypeIRI="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select="."/></Literal>
      </AnnotationAssertion>
    -->
    <!-- Classe de l’individu -->
    <ClassAssertion>
      <Class>
        <xsl:attribute name="IRI"><xsl:value-of select="$key"/></xsl:attribute>
      </Class>
      <NamedIndividual>
        <xsl:attribute name="IRI"><xsl:value-of select="$key"/></xsl:attribute>
      </NamedIndividual>
    </ClassAssertion>
    <!-- si pas de parent, la corporation est fille de Corporation -->
    <xsl:if test="not(following-sibling::th:parent)">
      <SubClassOf>
        <Class>
          <xsl:attribute name="IRI">
            <xsl:value-of select="$key"/>
          </xsl:attribute>
        </Class>
        <Class>
          <xsl:attribute name="IRI">
            <xsl:text>#Corporation</xsl:text>
          </xsl:attribute>
        </Class>
      </SubClassOf>      
    </xsl:if>
    <xsl:apply-templates select="th:enfant"/>
    <xsl:apply-templates select="th:formeRejetee"/>
  </xsl:template>
  
  
  <xsl:template match="th:enfant">
    <xsl:param name="key">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>      
    </xsl:param>
    <SubClassOf>
      <Class>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$key"/>
        </xsl:attribute>
      </Class>
      <Class>
        <xsl:attribute name="IRI">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
        </xsl:attribute>
      </Class>
    </SubClassOf>
  </xsl:template>
  
  
  <xsl:template match="th:formeRejetee">
    <xsl:param name="key">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>      
    </xsl:param>
    <!-- déclaration de l’individu "forme rejetée" -->
    <Declaration>
      <NamedIndividual>
        <xsl:attribute name="IRI"><xsl:value-of select="$key"/></xsl:attribute>
      </NamedIndividual>
    </Declaration>
    <!-- label de l’individu "forme rejetée" -->
    <AnnotationAssertion>
      <AnnotationProperty abbreviatedIRI="rdfs:label"/>
      <IRI><xsl:value-of select="$key"/></IRI>
      <Literal datatypeIRI="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select="normalize-space(.)"/></Literal>
    </AnnotationAssertion>
    <!-- classe de l’individu "forme rejetée" -->
    <ClassAssertion>
      <Class>
        <xsl:attribute name="IRI">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
        </xsl:attribute>
      </Class>
      <NamedIndividual>
        <xsl:attribute name="IRI"><xsl:value-of select="$key"/></xsl:attribute>
      </NamedIndividual>
    </ClassAssertion>
    <!-- déclaration de la propriété #is_rejected_corporation_form_of -->
    <ObjectPropertyAssertion>
      <ObjectProperty>
        <xsl:attribute name="IRI"><xsl:text>#is_rejected_corporation_form_of</xsl:text></xsl:attribute>
      </ObjectProperty>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="translate(.,' ','_')"/>
        </xsl:attribute>
      </NamedIndividual>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>
        </xsl:attribute>
      </NamedIndividual>
    </ObjectPropertyAssertion>
    <!-- déclaration de la propriété #is_authority_corporation_form_of -->
    <ObjectPropertyAssertion>
      <ObjectProperty>
        <xsl:attribute name="IRI"><xsl:text>#is_authority_corporation_form_of</xsl:text></xsl:attribute>
      </ObjectProperty>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="translate(preceding-sibling::th:entree,' ','_')"/>          
        </xsl:attribute>
      </NamedIndividual>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="translate(.,' ','_')"/>          
        </xsl:attribute>
      </NamedIndividual>
    </ObjectPropertyAssertion>
  </xsl:template>
  
 
</xsl:transform>