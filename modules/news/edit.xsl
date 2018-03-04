<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/news-edit-data">

	<form action="./?module=news" method="post" class="form-horizontal admin_edit_form">
		<input type="hidden" name="id"     value="{id}" />
		<input type="hidden" name="module" value="news" />
		<input type="hidden" name="action" value="edit_item" />
		
		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_title">Заголовок</label>
			<div class="col-sm-8"><input class="form-control" id="edit_title" type="text" name="caption"  value="{caption}"/></div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_summary">Подробности</label>
			<div class="col-sm-8"><textarea class="form-control" rows="5" id="edit_summary" type="text" name="summary"><xsl:value-of select="summary"></xsl:value-of></textarea></div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_free_link">Ссылка</label>
			<div class="col-sm-8"><input class="form-control" id="edit_free_link" type="text" name="link" value="{link}" /></div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-sm-4" for="edit_link">Страница</label>
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
			<label class="control-label col-sm-4" for="edit_streams">Потоки</label>
			<div class="col-sm-8"><input class="form-control" id="edit_streams" type="text" name="streams" value="{streams}" /></div>
		</div>

		<div class="form-group dialog_buttons">
			<input type="submit" value="Сохранить" class="btn btn-primary" />
		</div>
	</form>

</xsl:template>

</xsl:stylesheet>