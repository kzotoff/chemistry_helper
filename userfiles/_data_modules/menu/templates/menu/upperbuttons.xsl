<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/menu">

	<div class="upperbuttons-panel">
		<xsl:apply-templates select="child-elements/elem" />
	</div>

</xsl:template>

<xsl:template match="elem">

	<a class="menu-elem btn btn-default {description/class_item}" data-element-id="{@element-id}">
		<xsl:if test="@current = 'current'">
			<xsl:attribute name="class">btn btn-default upperbuttons-current<xsl:value-of select="description/class_item" /></xsl:attribute>
		</xsl:if>

		<xsl:if test="description/style_item != ''">
			<xsl:attribute name="style"><xsl:value-of select="description/style_item" /></xsl:attribute>			
		</xsl:if>

		<xsl:if test="description/page = '' and description/link = ''">
			<xsl:attribute name="disabled">disabled</xsl:attribute>			
		</xsl:if>

		<xsl:if test="description/link != ''">
			<xsl:attribute name="href"><xsl:value-of select="description/link" /></xsl:attribute>
		</xsl:if>

		<xsl:if test="description/page != ''">
			<xsl:attribute name="href">./<xsl:value-of select="description/page" /></xsl:attribute>
		</xsl:if>

		<xsl:value-of select="description/caption" />
	</a>
	
</xsl:template>

</xsl:stylesheet>