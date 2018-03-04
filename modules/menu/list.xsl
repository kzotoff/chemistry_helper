<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/menu">

	<div data-module="menu">

		<h3>Управление меню</h3>
		
		<div class="admin-buttons">
			<div class="btn btn-default" id="button_add">Добавить элемент</div>
		</div>

		<xsl:if test="count(child-elements) = 0">[ Нет элементов ]</xsl:if>
		
		<div class="admin_menu_tree">
			<ul>
				<xsl:apply-templates select="child-elements/elem" />
			</ul>
		</div>
	</div>
</xsl:template>

<xsl:template match="elem">
	<li class="menu-elem" data-element-id="{@element-id}">
		<span>
			<xsl:if test="description/orphan = 'orphan'">
				<xsl:attribute name="class">mod-menu-item-orphan</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="description/caption" />
			<span class="actionboard">
				<a class="row-inline-button" data-button-action="moveup"><img src="modules/menu/images/green.gif"    alt="up"   /></a>
				<a class="row-inline-button" data-button-action="movedown"><img src="modules/menu/images/red.gif"      alt="down" /></a>
				<a class="row-inline-button" data-button-action="edit"><img src="modules/menu/images/pencil.gif"   alt="edit" /></a>
				<a class="row-inline-button" data-button-action="delete"><img src="modules/menu/images/redcross.gif" alt="del"  /></a>
			</span>
		</span>
		<xsl:if test="child-elements">
			<ul>
				<xsl:apply-templates select="child-elements/elem" />
			</ul>
		</xsl:if>
	</li>

</xsl:template>

</xsl:stylesheet>