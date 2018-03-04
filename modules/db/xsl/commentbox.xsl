<?xml version="1.0" encoding="utf-8"?>

<!--

standard comments list

-->

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/comments">

	<div class="commentbox-outer-wrapper" data-dialog-title="{title}" data-attach-handlers="commentsFormHandlers" data-report-id="{report_id}">
		
		<div class="commentbox-comment-list">
			<a class="commentbox-print-icon" target="_blank" href="">
				<img src="images/printer.gif" alt="print" />
			</a>
			<xsl:if test="not(comment)">
				<span class="comments_no_comments">Нет комментариев.</span>
			</xsl:if>
			<xsl:apply-templates select="comment" />
		</div>

		<div class="form-vertical" data-meaning="wrap-form-here" data-form-container="commentbox-adder-form">
			<input type="hidden" name="ajaxproxy" value="db" />
			<input type="hidden" name="action"    value="call_api" />
			<input type="hidden" name="method"    value="comments_add" />
			<input type="hidden" name="row_id"    value="{main_object_id}" />

			<div class="form-group" data-edit-target="add-comment-text">
				<label for="commentbox-add-area">Добавить комментарий:</label>
				<textarea id="commentbox-add-area" class="form-control" wrap="physical" rows="6" name="comments_comment_text"></textarea>
			</div>
			
			<div class="form-group" data-edit-target="add-comment-files">
				<label for="commentbox-add-files">Прикрепить файлы:</label>
				<input id="commentbox-add-files" type="file" name="attachthis[]" class="commentbox-add-file form-control" multiple="multiple" />
			</div>
		
		</div>		
		
		<div class="comments-dialog-buttons">
			<input type="button" class="btn btn-primary" value="Добавить комментарий" data-button-action="form-submit" data-form-submit="commentbox-adder-form" />
			<input type="button" class="btn btn-primary" value="Закрыть" data-button-action="form-cancel" />
		</div>

	</div>
	
</xsl:template>


<xsl:template match="comment">
	<div class="commentbox" data-comment-id="{comments_id}">
		<p class="commentbox-single-header">
			<img class="commentbox-single-delete-button" src="images/red_cross_diag.png" alt="del" data-button-action="comment-delete" />
			<span class="commentbox-single-header-user"><xsl:value-of select="comments_user_id" /></span>
			<span class="commentbox-single-header-stamp"><xsl:value-of select="comments_stamp" /></span>
		</p>
		<p class="commentbox-single-object-info">
			<!-- <xsl:text>[comment target]</xsl:text> -->
			<xsl:if test="comments_attached_name!= ''">
				<a class="commentbox-single-attached-info" href="./?module=db&amp;action=call_api&amp;method=comments_get_attached&amp;row_id={comments_id}" target="_blank">
					<img src="images/floppy.png" alt="download" />
					<span><xsl:value-of select="comments_attached_name" /></span>
				</a>
			</xsl:if>
		</p>
		<p class="commentbox-single-text">
			<xsl:value-of select="comments_comment_text" />
		</p>
	</div>
</xsl:template>


</xsl:stylesheet>