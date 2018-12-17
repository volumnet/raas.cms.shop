<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:i="urn:1C.ru:commerceml_2" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" >
  <xsl:output method="xml" indent="yes"/>

  <xsl:template name="join">
      <xsl:param name="list" />
      <xsl:param name="separator"/>

      <xsl:for-each select="$list">
        <xsl:value-of select="." />
        <xsl:if test="position() != last()">
          <xsl:value-of select="$separator" />
        </xsl:if>
      </xsl:for-each>
  </xsl:template>

  <xsl:template match="/">
    <data>
      <fields>
        <xsl:for-each select=".//i:Классификатор//i:Свойство">
          <Field>
            <xsl:if test="@Статус = 'Удален'">
              <xsl:attribute name="delete">1</xsl:attribute>
            </xsl:if>
            <xsl:if test="./i:ТипЗначений = 'Справочник'">
              <values>
                <xsl:for-each select="./i:ВариантыЗначений/i:Справочник">
                  <value>
                    <xsl:attribute name="id">
                      <xsl:value-of select="./i:ИдЗначения" />
                    </xsl:attribute>
                    <xsl:apply-templates select="i:Значение" />
                  </value>
                </xsl:for-each>
              </values>
            </xsl:if>
            <id><xsl:apply-templates select="i:Ид" /></id>
            <classname update="false">RAAS\CMS\Material_Type</classname>
            <pid update="false" map="false">4</pid>
            <name><xsl:apply-templates select="i:Наименование" /></name>
            <datatype update="false">
              <xsl:choose>
                <xsl:when test="./i:ТипЗначений = 'Справочник'">select</xsl:when>
                <xsl:otherwise>text</xsl:otherwise>
              </xsl:choose>
            </datatype>
            <source_type update="false">
              <xsl:if test="./i:ТипЗначений = 'Справочник'">csv</xsl:if>
            </source_type>
            <source>
              <xsl:if test="./i:ТипЗначений = 'Справочник'">
                <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
                <xsl:call-template name="join">
                  <xsl:with-param name="list" select="i:ВариантыЗначений/i:Справочник/i:Значение" />
                  <xsl:with-param name="separator" select="'&#xa;'" />
                </xsl:call-template>
                <xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
              </xsl:if>
            </source>
          </Field>
        </xsl:for-each>
      </fields>

      <pages>
        <xsl:for-each select=".//i:Классификатор//i:Группа">
          <Page>
            <xsl:if test="@Статус = 'Удален'">
              <xsl:attribute name="delete">1</xsl:attribute>
            </xsl:if>
            <id><xsl:apply-templates select="i:Ид" /></id>
            <pid>
              <xsl:choose>
                <xsl:when test="name(../..) = 'Группа'">
                  <xsl:apply-templates select="../../i:Ид" />
                </xsl:when>
                <xsl:otherwise>
                  <xsl:attribute name="update">false</xsl:attribute>
                </xsl:otherwise>
              </xsl:choose>
            </pid>
            <name><xsl:apply-templates select="i:Наименование" /></name>
          </Page>
        </xsl:for-each>
      </pages>

      <materials>
        <xsl:for-each select=".//i:Каталог//i:Товар">
          <Material>
            <xsl:if test="@Статус = 'Удален'">
              <xsl:attribute name="delete">1</xsl:attribute>
            </xsl:if>
            <id><xsl:apply-templates select="i:Ид" /></id>
            <pid update="false">4</pid>
            <name><xsl:apply-templates select="i:Наименование" /></name>
            <description><xsl:apply-templates select="i:Описание" /></description>
            <pages_ids create="true">
              <xsl:for-each select="i:Группы/i:Ид">
                <pageId><xsl:value-of select="text()"/></pageId>
              </xsl:for-each>
            </pages_ids>
            <fields>
              <field urn="article"><xsl:apply-templates select="i:Артикул" /></field>
              <xsl:if test="./i:Картинка">
                <field urn="images">
                  <xsl:for-each select="./i:Картинка">
                    <value><xsl:value-of select="text()"/></value>
                  </xsl:for-each>
                </field>
              </xsl:if>
              <xsl:for-each select="./i:ЗначенияСвойств/i:ЗначенияСвойства">
                <field>
                  <xsl:attribute name="id"><xsl:value-of select="i:Ид"/></xsl:attribute>
                  <xsl:value-of select="i:Значение"/>
                </field>
              </xsl:for-each>
            </fields>
          </Material>
        </xsl:for-each>
      </materials>
    </data>
  </xsl:template>

</xsl:stylesheet>