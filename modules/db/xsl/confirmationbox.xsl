<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/confirmation-box">

	<div class="confirmation-box-outer-wrapper" data-attach-handlers="confirmationBoxAttachHandlers">
		<xsl:for-each select="api-data">
			<input type="hidden" data-api-param="{param}" data-api-value="{value}" />
		</xsl:for-each>
		
		<div class="confirmation-box-text">
			<xsl:value-of select="confirmation-text" />
		</div>
		
		<input type="button" value="OK"     class="btn btn-primary" data-button-action="confirm-action" />
		<input type="button" value="Отмена" class="btn btn-primary" data-button-action="cancel-action"  />
	</div>
	
</xsl:template>

</xsl:stylesheet>