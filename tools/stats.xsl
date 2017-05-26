<?xml version="1.0" encoding="UTF-8"?>
<xsl:transform exclude-result-prefixes="tei" version="1.0" 
  xmlns="http://www.tei-c.org/ns/1.0" 
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  >
  <xsl:output encoding="UTF-8" indent="yes" method="text"/>
  <xsl:param name="filename"/>
  <xsl:variable name="lf">
    <xsl:text>&#10;</xsl:text>
  </xsl:variable>
  <xsl:variable name="tab">
    <xsl:text>&#9;</xsl:text>
  </xsl:variable>
  
  <xsl:template match="*">
    <xsl:choose>
      <xsl:when test="*">
        <xsl:apply-templates select="*"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="tei:note | tei:teiHeader"/>
  
  <xsl:template match="tei:head|tei:bibl|tei:head//*|tei:bibl//*">
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="tei:text">
    <xsl:apply-templates select="tei:body"/>
  </xsl:template>

  <xsl:template match="tei:body">
    <xsl:apply-templates select="tei:div"/>
  </xsl:template>
  
  <xsl:template match="tei:div ">
    <xsl:choose>
      <xsl:when test="tei:div">
        <xsl:apply-templates select="tei:div"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@xml:id"/>
        <xsl:value-of select="$tab"/>
        <xsl:value-of select=" string-length( normalize-space(.) )"/>
        <xsl:value-of select="$tab"/>
        <xsl:variable name="head">
          <xsl:apply-templates select="tei:head"/>
        </xsl:variable>       
        <xsl:value-of select="normalize-space( $head )"/>
        <xsl:value-of select="$tab"/>
        <xsl:variable name="bibl">
          <xsl:apply-templates select="tei:bibl"/>
        </xsl:variable>       
        <xsl:value-of select="normalize-space( $bibl )"/>
        <xsl:value-of select="$lf"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
</xsl:transform>
