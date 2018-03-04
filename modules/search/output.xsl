<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/search-results">
	<p class="search_info">
		Поиск по запросу:
		<span class="search_pattern"><xsl:value-of select="pattern" /></span>.
		Всего результатов: <xsl:value-of select="count(./result)" />
	</p>
	<ol>
		<xsl:apply-templates select="result" />
	</ol>
</xsl:template>


<xsl:template match="result">
	<li class="search_result">
		<a>
			<xsl:if test="alias!=''">
				<xsl:attribute name="href"><xsl:value-of select="alias" /></xsl:attribute>
				<xsl:attribute name="class">search-underlined</xsl:attribute>
			</xsl:if>
			<span class="search_header"><xsl:value-of select="title" /></span><br />
		</a>
		<span class="search_highlight"><xsl:value-of select="highlight" disable-output-escaping="yes" /></span>
	</li>
</xsl:template>

</xsl:stylesheet>