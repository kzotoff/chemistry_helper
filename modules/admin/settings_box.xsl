<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/JuliaCMS_module_definition/main_script">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/class_name">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/css">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/js">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/admin_caption">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/admin_css">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/admin_js">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/disabled">
</xsl:template>
<xsl:template match="/JuliaCMS_module_definition/break_after">
</xsl:template>

<xsl:template match="/JuliaCMS_module_definition">
	<form class="settings-form">
		<xsl:apply-templates select="*" />
	</form>
</xsl:template>

<xsl:template match="*">
	
	<div class="settings-dialog-node">
		<label for="" class="control-label">
			<xsl:choose>
				<xsl:when test="@caption != ''" >
					<xsl:value-of select="@caption" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="name(.)" />
				</xsl:otherwise>
			</xsl:choose>
		</label>

		<xsl:choose>
		
			<xsl:when test="@type = 'select'">
				<div class="input-container">
					<select class="form-control" data-initial-value="{./value}">
						<xsl:choose>
							<xsl:when test="@data-path-hash !=''">
								<xsl:attribute name="data-path-hash"><xsl:value-of select="@data-path-hash" /></xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="readonly">readonly</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:for-each select="./possible/option">
							<option value="{@value}">
								<xsl:if test="@value = ../../value">
									<xsl:attribute name="selected">selected</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="." />
							</option>
						</xsl:for-each>
					</select>			
				</div>
			</xsl:when>
		
			<xsl:when test="count(./*) > 0 or @type = 'array'">
				<xsl:if test="@type = 'array' and count(./*) = 0">
					<div class="settings-dialog-node">[ Пустой список ]</div>
				</xsl:if>
				<xsl:for-each select="./*">
					<xsl:apply-templates select="." />
				</xsl:for-each>
			</xsl:when>
			
			<xsl:otherwise>
				<div class="input-container">
					<input class="form-control" data-initial-value="{.}" type="text" value="{.}">
						<xsl:choose>
							<xsl:when test="@data-path-hash !=''">
								<xsl:attribute name="data-path-hash"><xsl:value-of select="@data-path-hash" /></xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="readonly">readonly</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
					</input>
				</div>
			</xsl:otherwise>
			
		</xsl:choose>

	</div>
</xsl:template>


</xsl:stylesheet>