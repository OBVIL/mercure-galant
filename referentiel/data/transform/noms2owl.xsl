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
  
  <xsl:template match="/">
    <!-- ns declarations -->
    <Ontology
      xml:base="http://www.semanticweb.org/mercure-galant/ontologie/noms"
      xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
      xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
      xmlns:xml="http://www.w3.org/XML/1998/namespace"
      ontologyIRI="http://www.semanticweb.org/mercure-galant/ontologie/noms">
      <Prefix name="rdf" IRI="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/>
      <Prefix name="rdfs" IRI="http://www.w3.org/2000/01/rdf-schema#"/>
      <Prefix name="xsd" IRI="http://www.w3.org/2001/XMLSchema#"/>
      <Prefix name="owl" IRI="http://www.w3.org/2002/07/owl#"/>
      <!-- schéma de l’ontologie PersonForm -->
      <!-- CLASSES -->
      <Declaration>
        <Class IRI="#PersonForm"/>
      </Declaration>
      <Declaration>
        <Class IRI="#AuthorityPersonForm"/>
      </Declaration>
      <Declaration>
        <Class IRI="#RejectedPersonForm"/>
      </Declaration>
      <SubClassOf>
        <Class IRI="#AuthorityPersonForm"/>
        <Class IRI="#PersonForm"/>
      </SubClassOf>
      <SubClassOf>
        <Class IRI="#RejectedPersonForm"/>
        <Class IRI="#PersonForm"/>
      </SubClassOf>
      <!-- OBJECTS PROPS -->
      <Declaration>
        <ObjectProperty IRI="#is_authority_person_form_of"/>
      </Declaration>
      <Declaration>
        <ObjectProperty IRI="#is_rejected_person_form_of"/>
      </Declaration>
      <FunctionalObjectProperty>
        <ObjectProperty IRI="#is_rejected_person_form_of"/>
      </FunctionalObjectProperty>
      <InverseFunctionalObjectProperty>
        <ObjectProperty IRI="#is_authority_person_form_of"/>
      </InverseFunctionalObjectProperty>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#is_authority_person_form_of"/>
        <Class IRI="#AuthorityPersonForm"/>
      </ObjectPropertyDomain>
      <ObjectPropertyDomain>
        <ObjectProperty IRI="#is_rejected_person_form_of"/>
        <Class IRI="#RejectedPersonForm"/>
      </ObjectPropertyDomain>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#is_authority_person_form_of"/>
        <Class IRI="#RejectedPersonForm"/>
      </ObjectPropertyRange>
      <ObjectPropertyRange>
        <ObjectProperty IRI="#is_rejected_person_form_of"/>
        <Class IRI="#AuthorityPersonForm"/>
      </ObjectPropertyRange>      
      <!-- Générer les individus -->
      <xsl:apply-templates select="//th:nom"/>
    </Ontology>
  </xsl:template>
  
  <xsl:template match="th:nom">
    <xsl:apply-templates select="th:autorite"/>
    <xsl:apply-templates select="th:ep"/>
  </xsl:template>
  
  <!-- instances des formes d’autorité et relation avec les éventuelles formes rejetées -->
  <xsl:template match="th:autorite">
    <!-- Produire la clé d’identification -->
    <xsl:param name="entID">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>
    </xsl:param>
    <!-- Déclaration de l’individu -->
    <Declaration>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>
      </NamedIndividual>
    </Declaration>
    <!-- Déclaration de la classe de l’individu -->
    <ClassAssertion>
      <Class IRI="#AuthorityPersonForm"/>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>
      </NamedIndividual>
    </ClassAssertion>
    <!-- Définition du propriété label de l’individu -->
    <AnnotationAssertion>
      <AnnotationProperty abbreviatedIRI="rdfs:label"/>
      <IRI><xsl:value-of select="$entID"/></IRI>
      <Literal datatypeIRI="&xsd;string"><xsl:value-of select="."/></Literal>
    </AnnotationAssertion>
    <!-- Annotation concernant l’individu (thesaurus:na) -->
    <xsl:if test="following-sibling::th:na">
      <AnnotationAssertion>
        <AnnotationProperty abbreviatedIRI="rdfs:comment"/>
        <IRI><xsl:value-of select="$entID"/></IRI>
        <Literal datatypeIRI="&xsd;string"><xsl:value-of select="following-sibling::th:na"/></Literal>
      </AnnotationAssertion>
    </xsl:if>
    <!-- instanction des objectProperty (relation des formes d’autorité à leurs éventuelles formes rejetées) -->
    <xsl:if test="following-sibling::th:ep">
      <xsl:for-each select="following-sibling::th:ep">
        <ObjectPropertyAssertion>
          <ObjectProperty IRI="#is_authority_person_form_of"/>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:value-of select="$entID"/>
            </xsl:attribute>
          </NamedIndividual>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:text>#</xsl:text>
              <xsl:value-of select="translate(.,' ','_')"/>
            </xsl:attribute>
          </NamedIndividual>
        </ObjectPropertyAssertion>
        <ObjectPropertyAssertion>
          <ObjectProperty IRI="#is_rejected_person_form_of"/>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:text>#</xsl:text>
              <xsl:value-of select="translate(.,' ','_')"/>
            </xsl:attribute>
          </NamedIndividual>
          <NamedIndividual>
            <xsl:attribute name="IRI">
              <xsl:value-of select="$entID"/>
            </xsl:attribute>
          </NamedIndividual>
        </ObjectPropertyAssertion>
      </xsl:for-each>
    </xsl:if>
  </xsl:template>
  
  <!-- instances des formes rejetées -->
  <xsl:template match="th:ep">
    <!-- Produire la clé d’identification -->
    <xsl:param name="entID">
      <xsl:text>#</xsl:text>
      <xsl:value-of select="translate(.,' ','_')"/>
    </xsl:param>
    <!-- Déclaration de l’individu -->
    <Declaration>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>
      </NamedIndividual>
    </Declaration>
    <!-- Déclaration de la classe de l’individu -->
    <ClassAssertion>
      <Class IRI="#RejectedPersonForm"/>
      <NamedIndividual>
        <xsl:attribute name="IRI">
          <xsl:value-of select="$entID"/>
        </xsl:attribute>
      </NamedIndividual>
    </ClassAssertion>
    <!-- Définition du propriété label de l’individu -->
    <AnnotationAssertion>
      <AnnotationProperty abbreviatedIRI="rdfs:label"/>
      <IRI><xsl:value-of select="$entID"/></IRI>
      <Literal datatypeIRI="&xsd;string"><xsl:value-of select="."/></Literal>
    </AnnotationAssertion>
  </xsl:template>
 
</xsl:transform>