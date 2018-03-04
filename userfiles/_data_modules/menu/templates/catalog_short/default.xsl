<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/catalog-node-description">
	<div class="content-groups" data-source="short / default">
<!-- 	<p class="catalog-short-caption"><xsl:value-of select="/catalog-node-description/node-properties/caption" /></p> -->
		<xsl:for-each select="elems/elem">
			<a class="content-group {/catalog-node-description/node-properties/style_content}">
				<xsl:choose>				
					<xsl:when test="page != ''">
						<xsl:attribute name="href"><xsl:value-of select="page" /></xsl:attribute>
					</xsl:when>
					<xsl:when test="alias != ''">
						<xsl:attribute name="href"><xsl:value-of select="alias" /></xsl:attribute>
					</xsl:when>				
				</xsl:choose>

				<div class="content-group-caption" data-model="short">
					<div>				
						<div>				
							<xsl:value-of select="caption" disable-output-escaping="yes" />
						</div>
					</div>
				</div>

				<div class="content-group-image">
					<xsl:choose>
						<xsl:when test="picture != ''">
							<img src="userfiles/images/{picture}" alt="" />
						</xsl:when>
						<xsl:otherwise>
							<img src="userfiles/images/no_image.png" alt="" />
						</xsl:otherwise>
					</xsl:choose>						
				</div>
			</a>
		
		</xsl:for-each>
	</div>
</xsl:template>


</xsl:stylesheet>