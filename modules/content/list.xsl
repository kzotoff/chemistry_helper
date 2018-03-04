<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/pages-list">
	<div data-module="content">
		<h3>Управление страницами</h3>
		<div class="admin-buttons">
			<div class="btn btn-default" id="button_add">Добавить</div>
		</div>
		
		<table class="tablesorter table table-bordered unified-table">
			<thead>
				<tr>
					<th>Псевдоним</th>
					<th>Заголовок</th>
					<th>Файл</th>
					<th>CSS</th>
					<th>Скрипт</th>
					<th>Генератор</th>
					<th>XSL</th>
					<th>Статус</th>
					<th>Действия</th>
				</tr>
			</thead>
			<tbody>
				<xsl:apply-templates select="page-data" />
			</tbody>
		</table>
		
	</div>
</xsl:template>


<xsl:template match="page-data">

	<tr class="item_info_row" data-row-id="{id}" data-alias="{alias}" data-default-page="{default-page}">
		<td><xsl:value-of select="alias" /></td>
		<td><xsl:value-of select="title" /></td>
		<td><xsl:value-of select="filename" /></td>
		<td><xsl:value-of select="custom_css" /></td>
		<td><xsl:value-of select="custom_js" /></td>
		<td><xsl:value-of select="generator" /></td>
		<td><xsl:value-of select="xsl" /></td>
		<td><xsl:value-of select="file_status" /></td>
		<td class="actionboard">
			<a class="row-inline-button" data-button-action="edit"><img src="modules/content/images/pencil.gif" alt="" /></a>
			<a class="row-inline-button" data-button-action="goto"><img src="modules/content/images/right_green.gif" alt="" /></a>
			<a class="row-inline-button" data-button-action="delete"><img src="modules/content/images/redcross.gif" alt="" /></a>
		</td>
	</tr>

</xsl:template>


</xsl:stylesheet>