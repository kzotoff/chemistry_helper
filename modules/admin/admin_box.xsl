<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/admin-buttons">

	<xsl:if test="active-module != ''">
		<div class="admin-box-padder"></div>
	</xsl:if>
	<div class="admin-box-main" data-active-module="{active-module}" data-show-admin-link="{show-config-link}" data-cms-settings-phantom="{cms-settings-phantom}">
		<xsl:if test="active-module = ''">
			<xsl:attribute name="class">admin-box-main admin-box-transparent</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="button" />
		<xsl:choose>
			<xsl:when test="active-module != ''">
				<a href="./" data-button-action="edit-return">return to content</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="/admin-buttons/edit-mode = 'yes'">
						<a onclick="document.getElementById('editpagecontentform').submit();" data-button-action="edit-page-save">save page</a>
						<a href="./{/admin-buttons/active-page}" data-button-action="edit-page-cancel">cancel</a>
					</xsl:when>
					<xsl:otherwise>
						<a href="./{/admin-buttons/active-page}?edit" data-button-action="edit-page">edit this page</a>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
		
		<a href="{p_id}.?cmslogout" class="button-right" data-button-action="admin-quit">logout</a>
		
		<a href="#" data-button-action="admin-module-help" class="button-right">
			<xsl:if test="active-module != ''">
				<xsl:attribute name="data-help-path"><xsl:value-of select="active-module" /></xsl:attribute>
			</xsl:if>
			<xsl:text>help</xsl:text>
		</a>

		<xsl:if test="active-module != '' and show-config-link = 'yes'">
			<a href="#" data-button-action="admin-module-settings" class="button-right">module config</a>
		</xsl:if>
		
		<a href="#" data-button-action="admin-module-cms-config" class="button-right">CMS settings</a>
		
	</div>

	
</xsl:template>


<xsl:template match="button">
	<a href="./?module={module-name}&amp;action=manage" data-button-action="module-admin" data-button-target="module-{module-name}">
		<xsl:if test="module-name = /admin-buttons/active-module">
			<xsl:attribute name="class">active-button</xsl:attribute>
		</xsl:if>
		<xsl:value-of select="caption" />
	</a>
</xsl:template>


</xsl:stylesheet>