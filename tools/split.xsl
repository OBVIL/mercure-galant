<?xml version="1.0" encoding="UTF-8"?>
<xsl:transform exclude-result-prefixes="tei" version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:tei="http://www.tei-c.org/ns/1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output encoding="UTF-8" indent="yes" method="xml"/>
  <xsl:param name="folder">test/</xsl:param>
  
  <xsl:template match="node()|@*">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  
  <xsl:template match="/">
    <xsl:choose>
      <xsl:when test="/*/tei:text/tei:body/tei:div[not(@xml:id)]">
        <xsl:message terminate="yes"> AÏE ! </xsl:message>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates select="/*/tei:text/tei:body/tei:div"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="tei:body/tei:div">
    <xsl:call-template name="document"/>
  </xsl:template>
  
  <xsl:template name="document">
    <xsl:param name="id" select="@xml:id"/>
    <xsl:param name="date" select="substring($id, 4, 10)"/>
    <xsl:document href="{$folder}{$id}.xml" encoding="UTF-8" indent="yes" method="xml">
      <xsl:text disable-output-escaping="yes"><![CDATA[<?xml-model href="https://oeuvres.github.io/Teinte/teinte.rng" type="application/xml" schematypens="http://relaxng.org/ns/structure/1.0"?>
<?xml-stylesheet type="text/xsl" href="../../Teinte/tei2html.xsl"?>
]]></xsl:text>
      <TEI xml:lang="fr" xmlns="http://www.tei-c.org/ns/1.0">
        <xsl:attribute name="xml:id">
          <xsl:value-of select="$id"/>
        </xsl:attribute>
        <teiHeader>
          <fileDesc>
            <titleStmt>
              <title>Mercure Galant, janvier 1681</title>
            </titleStmt>
            <editionStmt>
              <edition>OBVIL/IReMuS</edition>
              <respStmt>
                <name>Nathalie Berton-Blivet</name>
                <resp>Responsable éditorial</resp>
              </respStmt>
              <respStmt>
                <name>Anne Piéjus</name>
                <resp>Responsable éditorial</resp>
              </respStmt>
              <respStmt>
                <name>Frédéric Glorieux</name>
                <resp>Informatique éditoriale</resp>
              </respStmt>
              <respStmt>
                <name>Vincent Jolivet</name>
                <resp>Informatique éditoriale</resp>
              </respStmt>
            </editionStmt>
            <publicationStmt>
              <publisher>Université Paris-Sorbonne, LABEX OBVIL</publisher>
              <date when="2017"/>
              <idno>
                <xsl:text>http://obvil.paris-sorbonne.fr/corpus/mercure-galant/</xsl:text>
                <xsl:value-of select="$id"/>
              </idno>
              <availability status="restricted">
                <licence target="http://creativecommons.org/licenses/by-nc-nd/3.0/fr/">
                  <p>Copyright © 2017 Université Paris-Sorbonne, agissant pour le Laboratoire d’Excellence « Observatoire de la vie littéraire » (ci-après dénommé OBVIL).</p>
                  <p>Cette ressource électronique protégée par le code de la propriété intellectuelle sur les bases de données (L341-1) est mise à disposition de la communauté scientifique internationale par l’OBVIL, selon les termes de la licence Creative Commons : « Attribution - Pas d’Utilisation Commerciale - Pas de Modification 3.0 France (CC BY-NC-ND 3.0 FR) ».</p>
                  <p>Attribution : afin de référencer la source, toute utilisation ou publication dérivée de cette ressource électroniques comportera le nom de l’OBVIL et surtout l’adresse Internet de la ressource.</p>
                  <p>Pas d’Utilisation Commerciale : dans l’intérêt de la communauté scientifique, toute utilisation commerciale est interdite.</p>
                  <p>Pas de Modification : l’OBVIL s’engage à améliorer et à corriger cette ressource électronique, notamment en intégrant toutes les contributions extérieures. La diffusion de versions modifiées de cette ressource n’est pas souhaitable.</p>
                </licence>
              </availability>
            </publicationStmt>
            <sourceDesc>
              <bibl><title>Mercure Galant</title>, <date>janvier 1681</date>.</bibl>
            </sourceDesc>
          </fileDesc>
          <profileDesc>
            <creation>
              <date when="{$date}"/>
            </creation>
            <langUsage>
              <language ident="fr"/>
            </langUsage>
          </profileDesc>
        </teiHeader>
        <text>
          <body>
            <xsl:apply-templates/>
          </body>
        </text>
      </TEI>
    </xsl:document>
  </xsl:template>
</xsl:transform>
