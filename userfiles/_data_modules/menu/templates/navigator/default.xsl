<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/navigator">
	<div class="navigator" data-source="navigator / default">
		<xsl:for-each select="elem">
			<a href="{alias}" title="{alias}" data-item-id="{id}">
				<xsl:value-of select="caption" />
			</a>
			<xsl:if test="position()!=last()">&#160;&gt;&#160;</xsl:if>
		</xsl:for-each>
	</div>
</xsl:template>


</xsl:stylesheet>