<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/menu-edit-data">

	<form action="./" method="post" class="form-horizontal admin_edit_form">

		<input id="menu_id" type="hidden" name="menu_id" value="{menu_id}" />
		<input id="edit_id" type="hidden" name="id" value="{id}" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="module" value="menu" />

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_caption">Название</label>
			<div class="col-sm-8"><input class="form-control" id="edit_caption" type="text" name="caption" value="{caption}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_parent">Родительский элемент</label>
			<div class="col-sm-8">
				<select class="form-control" id="edit_parent" name="parent">
					<option> -- верхний уровень -- </option>
					<xsl:for-each select="menu-elems/elem">
						<option value="{id}">
							<xsl:if test="@selected = 'selected'">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:if test="@top-level = 'top-level'">
								<xsl:attribute name="class">menu-item-select-top-level</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="caption" />
						</option>
					</xsl:for-each>
				</select>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_page">Страница</label>
			<div class="col-sm-8">
				<select class="form-control" id="edit_page" name="page">
					<option value=""> -- нет страницы --</option>
					<xsl:for-each select="page-list/module">
						<option class="select_divider"><xsl:value-of select="@name" /></option>
						<xsl:for-each select="catcher">
							<option value="{alias}">
								<xsl:if test="alias = ../../../page">
									<xsl:attribute name="selected">selected</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="title" />
							</option>
						</xsl:for-each>
					</xsl:for-each>
				</select>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_link">Ссылка</label>
			<div class="col-sm-8"><input class="form-control" id="edit_link" type="text" name="link" value="{link}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_alias">Псевдоним</label>
			<div class="col-sm-8"><input class="form-control" id="edit_alias" type="text" name="alias" value="{alias}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_title">Заголовок</label>
			<div class="col-sm-8"><input class="form-control" id="edit_title" type="text" name="title" value="{title}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_meta">meta</label>
			<div class="col-sm-8"><textarea class="form-control" rows="3" id="edit_meta" name="meta"><xsl:value-of select="meta" /></textarea></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_text">Вспомогательный текст</label>
			<div class="col-sm-8"><textarea class="form-control" id="edit_text" name="text"><xsl:value-of select="text" /></textarea></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_picture">Картинка</label>
			<div class="col-sm-8"><input class="form-control" id="edit_picture" type="text" name="picture" value="{picture}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_xsl">XSL</label>
			<div class="col-sm-8"><input class="form-control" id="edit_xsl" type="text" name="xsl" value="{xsl}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_style_content">CSS каталога</label>
			<div class="col-sm-8"><input class="form-control" id="edit_style_content" type="text" name="style_content" value="{style_content}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_style_item">Стиль</label>
			<div class="col-sm-8"><input class="form-control" id="edit_style_item" type="text" name="style_item" value="{style_item}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_class_item">CSS-класс</label>
			<div class="col-sm-8"><input class="form-control" id="edit_class_item" type="text" name="class_item" value="{class_item}" /></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_hidden">Скрытый</label>
			<div class="col-sm-8">
				<input id="edit_hidden" type="checkbox" name="hidden">
					<xsl:if test="hidden = '1'">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
			</div>
		</div>

		<div class="form-group dialog_buttons">
			<input type="submit" value="Сохранить" class="btn btn-primary" />
		</div>

	</form>

</xsl:template>

</xsl:stylesheet>