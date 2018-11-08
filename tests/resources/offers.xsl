<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:i="urn:1C.ru:commerceml_2" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" >
  <xsl:output method="xml" indent="yes"/>

  <xsl:template match="/">
    <data>
      <materials>
        <xsl:for-each select=".//i:ПакетПредложений//i:Предложения//i:Предложение">
          <Material>
            <id><xsl:apply-templates select="i:Ид" /></id>
            <fields>
              <xsl:for-each select=".//i:Цена[descendant::i:Валюта[contains(text(), 'RUB') or contains(text(), 'руб') or contains(text(), 'RUR')]]">
                <field urn="price"><xsl:value-of select="i:ЦенаЗаЕдиницу/text()"/></field>
              </xsl:for-each>
              <field urn="available">
                <xsl:choose>
                  <xsl:when test="(i:Количество/text()) > 0">1</xsl:when>
                  <xsl:otherwise>0</xsl:otherwise>
                </xsl:choose>
              </field>
            </fields>
          </Material>
        </xsl:for-each>
      </materials>
    </data>
  </xsl:template>

</xsl:stylesheet>