<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="table">
    <div class="pt-table">
        <xsl:apply-templates select="item" />
    </div>

</xsl:template>


<xsl:template match="item">
	<div class="pt-item" data-period="{period}" data-group="{group}" data-color="{color}" data-number="{number}">

        <xsl:if test="(period = 6) and (group = '')">
            <xsl:attribute name="data-group">
                <xsl:value-of select="number - 54" />
            </xsl:attribute>
            <xsl:attribute name="data-extra-row">1</xsl:attribute>
        </xsl:if>

        <xsl:if test="(period = 7) and (group = '')">
            <xsl:attribute name="data-group">
                <xsl:value-of select="number - 86" />
            </xsl:attribute>
            <xsl:attribute name="data-extra-row">1</xsl:attribute>
        </xsl:if>

        <div class="pt-item-sign">
            <xsl:value-of select="sign" />
        </div>
        <div class="pt-item-numbers">
            <div class="pt-item-number">
                <xsl:value-of select="number" />
            </div>
            <div class="pt-item-mass">
                <xsl:value-of select="format-number(translate(translate(mass, ']', ''), '[', ''), '#.00')" />
            </div>
        </div>
        <div class="pt-item-title">
            <xsl:value-of select="title_ru" />
        </div>

	</div>
</xsl:template>

</xsl:stylesheet>