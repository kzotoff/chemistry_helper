<?xml version="1.0" encoding="utf-8"?>



<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />



<xsl:template match="/">

    <div class="filter-dialog" data-form-name="filter-dialog-form" data-dialog-title="Фильтр">
        <input type="hidden" name="ajaxproxy" value="db" />
        <input type="hidden" name="action" value="call_api" />
        <input type="hidden" name="method" value="filter_apply_mass" />
        <input type="hidden" name="report_id" value="{filter-dialog/report-id}" />

        <xsl:apply-templates select="/filter-dialog/filter-info" />

        <xsl:call-template name="filter-add-more-adder" />

		<div class="edit-dialog-buttons">
			<input type="button" class="btn btn-primary" value="Сохранить" data-button-action="form-submit" data-form-submit="filter-dialog-form" />
			<input type="button" class="btn btn-primary" value="Закрыть"    data-button-action="form-cancel" />
		</div>
    </div>

</xsl:template>



<xsl:template match="filter-info">

    <div class="filter-current" data-report-id="{/filter-dialog/report-id}">
        <xsl:apply-templates select="filter-elem" />
    </div>

</xsl:template>



<xsl:template match="filter-elem">

    <div class="filter-elem" data-filter-index="{index}">

        <select class="form-control" data-meaning="field-selector" name="filter-elem-{index}-field">
            <xsl:apply-templates select="/filter-dialog/fields-info">
                <xsl:with-param name="selected-field" select="field/name" />
            </xsl:apply-templates>
        </select>

        <select class="form-control" data-meaning="filter-type" name="filter-elem-{index}-type">
            <xsl:apply-templates select="/filter-dialog/filter-types"> 
                <xsl:with-param name="selected-type" select="filter/type" />
            </xsl:apply-templates>
        </select>

        <input class="form-control" type="text" data-control-type="{field/type}" data-meaning="filter-param" value="{param}" name="filter-elem-{index}-param"/>

        <button class="btn btn-default filter-elem-remove">
            <img src="modules/db/images/red_cross_diag.png" alt="remove" />
        </button>

    </div>

</xsl:template>



<xsl:template name="filter-add-more-adder">

    <div class="filter-elem filter-elem-template" data-filter-index="new" data-meaning="filter-add-more-adder">

		<!-- the last button is only visible when in template mode -->
        <button class="btn btn-default filter-elem-add">
            <img src="modules/db/images/plus_one.png" alt="add more" />
        </button>

        <select class="form-control" data-meaning="field-selector" name="filter-elem-new-field">
            <xsl:apply-templates select="/filter-dialog/fields-info" />
        </select>

        <select class="form-control" data-meaning="filter-type" name="filter-elem-new-type">
            <option value="-">не добавлять</option>
            <xsl:apply-templates select="/filter-dialog/filter-types" />
        </select>

        <input class="form-control" type="text" data-meaning="filter-param" value="" name="filter-elem-new-param" />

        <button class="btn btn-default filter-elem-remove">
            <img src="modules/db/images/red_cross_diag.png" alt="remove" />
        </button>

    </div>

</xsl:template>



<xsl:template match="/filter-dialog/filter-types/*">
    <xsl:param name="selected-type" />
    <option value="{type}">
        <xsl:if test="$selected-type = type">
            <xsl:attribute name="selected">selected</xsl:attribute>
        </xsl:if>
        <xsl:value-of select="caption" />
    </option>
</xsl:template>



<xsl:template match="/filter-dialog/fields-info/*">
    <xsl:param name="selected-field" />
    <option value="{field}">
        <xsl:if test="$selected-field = field">
            <xsl:attribute name="selected">selected</xsl:attribute>
        </xsl:if>
        <xsl:value-of select="caption" />
    </option>
</xsl:template>




</xsl:stylesheet>