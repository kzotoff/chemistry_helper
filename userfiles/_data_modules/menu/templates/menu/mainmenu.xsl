<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/menu">
	<div class="template-menu-menu">
		<ul data-source="menu / mainmenu">
			<li class="template-menu-logo">
				<a href="./">
					<img src="userfiles/template/images/logo.png" alt="logo" />
				</a>
			</li>
			<xsl:apply-templates select="child-elements/elem" />
		</ul>
	</div>
</xsl:template>

<xsl:template match="elem">


	<li class="menu-elem {description/class_item}" data-element-id="{@element-id}">
		<xsl:if test="description/style_item != ''">
			<xsl:attribute name="style"><xsl:value-of select="description/style_item" /></xsl:attribute>			
		</xsl:if>
		
		<a>
			<xsl:choose>
				<xsl:when test="description/link != ''">
					<xsl:attribute name="href"><xsl:value-of select="description/link" /></xsl:attribute>
				</xsl:when>
				<xsl:when test="description/page != ''">
					<xsl:attribute name="href">./<xsl:value-of select="description/page" /></xsl:attribute>
				</xsl:when>
			</xsl:choose>
			<span>
				<xsl:value-of select="description/caption" />
			</span>
		</a>
		<!-- <xsl:if test="child-elements"> -->
			<ul>
				<xsl:apply-templates select="child-elements/elem" />
			</ul>
		<!-- </xsl:if> -->
	</li>
	
	
</xsl:template>

</xsl:stylesheet>