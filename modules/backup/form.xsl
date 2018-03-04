<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/backups">
	<div data-module="backup">
		<h3>Резервные копии</h3>
		<xsl:choose>
			<xsl:when test="/backups/backup">
				<table class="table table-bordered">
					<thead>
						<tr>
							<th style="width: 30%;">Файл</th>
							<th style="width: 7%;">Размер</th>
							<th style="width: 63%;">Описание</th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="backup" />
					</tbody>
				</table>
			</xsl:when>
			<xsl:otherwise>
				<div class="empty-table">[ ни одной копии пока не сделано ]</div>
			</xsl:otherwise>
		</xsl:choose>

		<h4>Создать новую копию</h4>
		<form class="form-vertical" action="./?module=backup&amp;action=create" method="post">
			<div class="form-group">
				<label for="backup-name">Имя <small><span class="text-muted">(только цифры и латинские буквы)</span></small></label>
				<input type="text" name="backup_name" id="backup-name" class="form-control" value="{/backups/suggest}"/>
			</div>
			<div class="form-group">
				<label for="backup-description">Описание</label>
				<input type="text" name="backup_description" id="backup-description" class="form-control" />
			</div>
			<input type="submit" class="btn btn-primary" value="Создать" />
		</form>

	</div>
</xsl:template>

<xsl:template match="backup">
	<tr data-filename="{filename}">
		<td class="td_filename">
			<xsl:value-of select="filename" />
		</td>
		<td class="td_filesize">
			<xsl:value-of select="filesize" />
		</td>
		<td class="td_description">
			<div class="backup-in-table-buttons">
				<input type="button" class="btn btn-info btn-xs" data-action="download" value="Скачать" />
				<input type="button" class="btn btn-warning btn-xs" data-action="restore" value="Восстановить" />
				<input type="button" class="btn btn-danger btn-xs" data-action="delete" value="Удалить" />
			</div>
			<xsl:value-of select="description" />
		</td>
	</tr>
</xsl:template>

</xsl:stylesheet>