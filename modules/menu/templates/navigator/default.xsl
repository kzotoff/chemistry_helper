<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/navigator">
	<div class="demo-navigator">
		<ul>
			<xsl:for-each select="elem">
				<li>
					<a href="{alias}" alt="{alias}" >
						<span><xsl:value-of select="caption" /></span>
					</a>
				</li>
				<xsl:if test="position()!=last()">&#160;&gt;&#160;</xsl:if>
			</xsl:for-each>
		</ul>
	</div>
</xsl:template>


</xsl:stylesheet>