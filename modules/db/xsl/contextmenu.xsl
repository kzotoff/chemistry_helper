<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/menu">
	<table class="contextmenu_table">
		<xsl:for-each select="menu_item|menu_divider">
			<xsl:apply-templates select="." />
		</xsl:for-each>
	</table>
</xsl:template>


<xsl:template match="menu_divider">
	<tr class="contextmenu_divider contextmenu_item_disabled">
		<td colspan="2"><hr /></td>
	</tr>
</xsl:template>

<xsl:template match="menu_item">
	<tr class="contextmenu_item">

		<xsl:if test="@disabled"><xsl:attribute name="class">contextmenu_item_disabled</xsl:attribute></xsl:if>
		<xsl:if test="@api">
			<xsl:attribute name="data-call-api"><xsl:value-of select="@api" /></xsl:attribute>
		</xsl:if>

		<xsl:if test="@after">
			<xsl:attribute name="data-after-api"><xsl:value-of select="@after" /></xsl:attribute>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="@js">
				<xsl:attribute name="onclick"><xsl:value-of select="@js" /></xsl:attribute>
			</xsl:when>
			<xsl:when test="@link">
				<xsl:attribute name="onclick">location.href = '<xsl:value-of select="@link" />';</xsl:attribute>
			</xsl:when>
		</xsl:choose>
		
		<td>
			<xsl:if test="@image"><img src="{@image}" alt="" /></xsl:if>
		</td>
		<td>
			<xsl:if test="@class">   <xsl:attribute name="class"><xsl:value-of select="@class" /></xsl:attribute></xsl:if>
			<xsl:if test="@style">   <xsl:attribute name="style"><xsl:value-of select="@style" /></xsl:attribute></xsl:if>
			
			<xsl:value-of select="." />
		</td>
	</tr>
</xsl:template>


</xsl:stylesheet>