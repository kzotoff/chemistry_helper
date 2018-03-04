<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/catalog-node-description">
	<xsl:choose>
		<xsl:when test="count(elems/elem) &gt; 0">
			<xsl:apply-templates select="elems" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:apply-templates select="node-properties" />
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>


<xsl:template match="elems">
	<div class="content-groups-tabled" data-source="full / default">
		<xsl:for-each select="elem">
			<a class="content-group {/catalog-node-description/node-properties/style_content}">
				<xsl:choose>
					<xsl:when test="page != ''">
						<xsl:attribute name="href"><xsl:value-of select="page" /></xsl:attribute>
					</xsl:when>
					<xsl:when test="alias != ''">
						<xsl:attribute name="href"><xsl:value-of select="alias" /></xsl:attribute>
					</xsl:when>
				</xsl:choose>

				<div class="content-group-image">
					<xsl:if test="(price != '') and (price != '0')">
						<div class="content-group-price">
							<span class="rouble-sign">P</span><xsl:value-of select="price" />
						</div>
					</xsl:if>
					<xsl:choose>
						<xsl:when test="picture != ''">
							<img src="userfiles/images/{picture}" alt="" />
						</xsl:when>
						<xsl:otherwise>
							<img src="userfiles/images/no_image.png" alt="" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
				

				<div class="content-group-texts" data-model="short">
					<div>
						<div class="caption">
							<xsl:value-of select="caption" />
						</div>
					</div>
					<div>
						<div class="detailed">
							<xsl:value-of select="text" disable-output-escaping="yes" />
						</div>
					</div>
				</div>

			</a>

		</xsl:for-each>
	</div>
</xsl:template>



<xsl:template match="node-properties">
	<div class="single-item-information" data-row-id="{id}" data-source="full / default">
		<div>
			<xsl:if test="picture != ''">
				<div class="single-item-image">
					<div class="single-item-image-outer">
						<div class="table-cell">
							<img src="userfiles/images/{picture}" alt="" />
						</div>
					</div>
				</div>
			</xsl:if>
			<div class="single-item-texts">
				<div class="single-item-caption">
					<span>
						<span>
							<xsl:value-of select="caption" disable-output-escaping="yes" />
						</span>
					</span>
				</div>
				<div class="single-item-text">
					<xsl:value-of select="text" disable-output-escaping="yes" />
				</div>
			</div>
		</div>
	</div>
</xsl:template>


</xsl:stylesheet>