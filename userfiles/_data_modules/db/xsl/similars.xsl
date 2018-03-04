<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/compare-data">
	<div class="sim-compare">
		<div class="sim-compare-header"><!-- here will be buttons -->
		</div>	
		<div class="sim-compare-v-scroller">
			<div class="sim-compare-reference">
				<xsl:apply-templates select="reference" />
			</div>
			
			<div class="sim-compare-tests">
				<div class="sim-compare-tests-inner" style="width: {count(similars/elem) * 17}em">
					<xsl:for-each select="similars/elem">
						<xsl:apply-templates select="." />
					</xsl:for-each>
				</div>
			</div>
		</div>
	</div>
</xsl:template>


<xsl:template match="/compare-data/reference">
	<div class="sim-compare-data">
		<div>
			reference details
		</div>
		<xsl:for-each select="*">
			<div data-code="{name(.)}">
				<span class="sim-reference-header">
					<xsl:value-of select="name()" />
				</span>
				<span class="sim-reference-value">
					<xsl:value-of select="." />
				</span>
			</div>
		</xsl:for-each>
	</div>
</xsl:template>

<xsl:template match="/compare-data/similars/elem">
	<div class="sim-compare-data">
		<div>
			similarity: <xsl:value-of select="sim" />
		</div>
		<xsl:for-each select="data/*">
			<div data-code="{name(.)}">
				<xsl:value-of select="." />
			</div>
		</xsl:for-each>
	</div>
</xsl:template>





</xsl:stylesheet>