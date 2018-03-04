<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/page-edit-data">

	<form action="./" method="post" class="form-horizontal admin_edit_form">
		<input type="hidden" name="id"     value="{id}" />
		<input type="hidden" name="module" value="content" />
		<input type="hidden" name="action" value="update" />
			
		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_title">Заголовок</label>
			<div class="col-sm-8"><input class="form-control" id="edit_title" type="text" name="title"  value="{title}"/></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_alias">Псевдоним</label>
			<div class="col-sm-8"><input class="form-control" id="edit_alias" type="text" name="alias" value="{alias}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_filename">Имя файла</label>
			<div class="col-sm-8"><input class="form-control" id="edit_filename" type="text" name="filename" value="{filename}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_meta">meta</label>
			<div class="col-sm-8"><textarea class="form-control" rows="3" id="edit_meta" name="meta"><xsl:value-of select="meta" /></textarea></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_css">CSS</label>
			<div class="col-sm-8"><input class="form-control" id="edit_css" type="text" name="css" value="{custom_css}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_js">Скрипт</label>
			<div class="col-sm-8"><input class="form-control" id="edit_js" type="text" name="js" value="{custom_js}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_generator">PHP-генератор</label>
			<div class="col-sm-8"><input class="form-control" id="edit_generator" type="text" name="generator" value="{generator}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_xsl">XSL</label>
			<div class="col-sm-8"><input class="form-control" id="edit_xsl" type="text" name="xsl" value="{xsl}" /></div>
		</div>
		
		<div class="form-group dialog_buttons">
			<input type="submit" value="Сохранить" class="btn btn-primary" />
		</div>

	</form>

</xsl:template>

</xsl:stylesheet>