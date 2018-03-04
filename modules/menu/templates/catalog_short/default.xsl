<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/catalog-node-description">
	<p class="catalog-short-caption">SHORT CATALOG: [ <xsl:value-of select="/catalog-node-description/node-properties/caption" /> ]</p>
	<xsl:for-each select="elems/elem">
		<a class="catalog-item catalog-short {/catalog-node-description/node-properties/style_content}">
			<xsl:if test="alias != ''">
				<xsl:attribute name="href"><xsl:value-of select="alias" /></xsl:attribute>
			</xsl:if>
			<div class="catalog-item-picture">
				<img src="userfiles/images/catalog/{picture}" alt="" />
			</div>
			<div class="catalog-item-caption"><xsl:value-of select="caption" /></div>
		</a>
	</xsl:for-each>
</xsl:template>


</xsl:stylesheet>