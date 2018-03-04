<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/root">
	<div data-module="feedback">
		<h3>Шаблоны писем для обратной связи</h3>

		<div class="admin-buttons">
		
			<form class="form-inline" method="post" action="./">
				<input type="hidden" name="module" value="feedback" />
				<input type="hidden" name="action" value="add_template" />
				<label for="module-feedback-add-name">Создать еще:&#160;</label>
				<input id="module-feedback-add-name" type="text" class="form-control" placeholder="имя файла для нового шаблона" name="filename" />
				<input type="submit" class="btn btn-default" value="Добавить" />
			</form>
			
		</div>
		<table class="table table-bordered tablesorter unified-table">
			<thead>
				<tr>
					<th style="width: 20%;">Файл</th>
					<th style="width: 70%;">Заголовок</th>
					<th style="width: 10%;">Действия</th>
				</tr>
			</thead>
			<tbody>
				<xsl:apply-templates select="files/file" />
			</tbody>
		</table>
		
	</div>
</xsl:template>

<xsl:template match="file">

	<tr class="template_info_row" >
		<td data-meaning="template-filename"><xsl:value-of select="filename" /></td>
		<td><xsl:value-of select="title" /></td>
		<td class="actionboard">
			<a class="row-inline-button" data-button-action="edit"><img src="modules/feedback/images/pencil.gif" alt="" /></a>
			<a class="row-inline-button" data-button-action="delete"><img src="modules/feedback/images/redcross.gif" alt="" /></a>
		</td>
	</tr>

</xsl:template>


</xsl:stylesheet>