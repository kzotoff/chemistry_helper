<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/menu">
	<ul data-source="menu / default">
		<xsl:apply-templates select="child-elements/elem" />
	</ul>
</xsl:template>

<!--
	commented xsl:if below is a workaround of some strange XSL-transformer bug:
		1) if LI contains another UL, transformer adds EOL after opening LI
		2) if LI doesn't contain UL, there comes EOL
		3) if list-style-position set to "inside", opera-12 draws this EOL as extra space between list marker and element text
		
	other way is to add &#160; before opening LI, but in this case you will have to margin text to the left by 0.4em (nbsp width)
-->
<xsl:template match="elem">


	<li class="menu-elem {description/class_item}" data-element-id="{@element-id}">
		<xsl:if test="description/style_item != ''">
			<xsl:attribute name="style"><xsl:value-of select="description/style_item" /></xsl:attribute>			
		</xsl:if>
		
		<a>
			<xsl:if test="description/page != ''">
				<xsl:attribute name="href">./<xsl:value-of select="description/page" /></xsl:attribute>
			</xsl:if>
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