<?xml version="1.0" encoding="utf-8"?>

<!--

standard data table

-->

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/">

    <div class="datablock-wrapper" id="datablock_wrapper_{report/datablock_id}" data-report-id="{report/report_id}" data-config-context-button="{report/context-menu-button}">
        <xsl:variable name="table_width" select="sum(/report/header/field_caption/@width)+1" />
        <!-- <xsl:if test="count(report/report-menu/*) &gt; 0"> -->
            <div class="datablock-buttons">

                <xsl:if test="report/report_caption != ''">
                    <span class="datablock-caption"><xsl:value-of select="report/report_caption" /></span>
                </xsl:if>

                <!-- <xsl:apply-templates select="report/builtin-buttons/*[not(self::counter)]" /> -->
                <xsl:apply-templates select="report/builtin-buttons/*" />
                <xsl:apply-templates select="report/report-menu" />
                <xsl:apply-templates select="report/filter-groups" />
                <!-- <xsl:apply-templates select="report/builtin-buttons/counter" /> -->


            </div>
        <!-- </xsl:if> -->

        <xsl:if test="report/data_set">
            <div class="datablock-header" id="datablock_table_wrapper_header_{report/datablock_id}">
                <table style="width: {$table_width}px;" id="datablock_table_header_{report/datablock_id}">
                    <thead>
                        <xsl:for-each select="report/header/field_caption">
                            <th data-field="{@field}" style="width: {@width}px;">
                                <xsl:if test="@sorter_type">
                                    <xsl:attribute name="data-sorter-type"><xsl:value-of select="@sorter_type" /></xsl:attribute>
                                </xsl:if>
                                <div class="datablock-header-text">
                                    <xsl:choose>
                                        <xsl:when test="@special='checkbox'">
                                            <input type="checkbox" name="./name" class="./class" data-meaning="row-select-checkbox" data-simplesorter-no-sort="yes" />
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:value-of select="." />
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </div>
                            </th>
                        </xsl:for-each>
                    </thead>
                </table>
            </div>

            <div class="datablock-content" id="datablock_table_wrapper_content_{report/datablock_id}">
                <xsl:if test="report/no-context-menu">
                    <xsl:attribute name="data-no-context-menu">yes</xsl:attribute>
                </xsl:if>
                <table style="width: {$table_width}px;" id="datablock_table_content_{report/datablock_id}">
                    <colgroup>
                        <xsl:for-each select="report/header/field_caption">
                            <col style="width: {@width}px;" />
                        </xsl:for-each>
                    </colgroup>
                    <xsl:apply-templates select="report/data_set/data_row" />
                </table>
            </div>
<!--
            <div class="json_data">
                <xsl:value-of select="report/json" />
            </div>
-->
        </xsl:if>
    </div>

</xsl:template>



<!-- API buttons -->
<xsl:template match="/report/report-menu/report-menu-item">
    <a class="btn btn-default" data-meaning="api-button">
        <xsl:choose>
            <xsl:when test="direct-api != ''">
                <xsl:attribute name="data-direct-api-method"><xsl:value-of select="direct-api" /></xsl:attribute>
            </xsl:when>
            <xsl:when test="api != ''">
                <xsl:attribute name="data-api-method"><xsl:value-of select="api" /></xsl:attribute>
            </xsl:when>
            <xsl:when test="js != ''">
                <xsl:attribute name="onclick"><xsl:value-of select="js" /></xsl:attribute>
            </xsl:when>
            <xsl:when test="link != ''">
                <xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute>
            </xsl:when>
        </xsl:choose>
        <xsl:if test="id != ''">
            <xsl:attribute name="id"><xsl:value-of select="id" /></xsl:attribute>
        </xsl:if>
        <xsl:if test="after != ''">
            <xsl:attribute name="data-api-after"><xsl:value-of select="after" /></xsl:attribute>
        </xsl:if>
        <xsl:if test="image != ''">
            <img src="{image}" alt="" />
        </xsl:if>
        <xsl:if test="caption != ''">
            <span>
                <xsl:value-of select="caption" />
            </span>
        </xsl:if>
    </a>
</xsl:template>



<!-- filters, calendars and other special controls at the header -->

<!-- record count informer -->
<xsl:template name="row-count-informer">

    <div class="btn btn-default datablock-row-count">
        <span data-meaning="count-checked" class="datablock-row-count-hideshow">
        </span>

        <span data-meaning="count-visible" class="datablock-row-count-hideshow">
        </span>

        <xsl:if test="/report/builtin-buttons/pager">
            <span data-meaning="count-on-page" class="datablock-row-count-hideshow">
                <xsl:value-of select="/report/record-count-start" />-<xsl:value-of select="/report/record-count-end" />
            </span>
        </xsl:if>

        <xsl:if test="/report/record-count-filtered != /report/record-count-total">
            <span data-meaning="count-filtered" class="datablock-row-count-hideshow">
                <xsl:value-of select="/report/record-count-filtered" />
            </span>
        </xsl:if>

        <span data-meaning="count-total" class="datablock-row-count-hideshow">
            <xsl:value-of select="/report/record-count-total" />
        </span>

        <div class="datablock-buttons-popup">
            <div data-meaning="filter-info">
                <xsl:apply-templates select="/report/filter-info/filter-elem" />
            </div>

            <div data-meaning="filter-helper"><!-- informer help -->

                <div class="datablock-row-count-hideshow">
                    <span>Отмечено:</span>
                    <span data-meaning="count-checked">
                    </span>
                </div>

                <div class="datablock-row-count-hideshow">
                    <span>Видимых:</span>
                    <span data-meaning="count-visible">
                    </span>
                </div>

                <xsl:if test="/report/builtin-buttons/pager">
                    <div class="datablock-row-count-hideshow">
                        <span>На странице:</span>
                        <span data-meaning="count-on-page">
                            <xsl:value-of select="/report/record-count-start" />-<xsl:value-of select="/report/record-count-end" />
                        </span>
                    </div>
                </xsl:if>

                <xsl:if test="/report/record-count-filtered != /report/record-count-total">
                    <div class="datablock-row-count-hideshow">
                        <span>Отфильтровано:</span>
                        <span data-meaning="count-filtered">
                            <xsl:value-of select="/report/record-count-filtered" />
                        </span>
                    </div>
                </xsl:if>

                <div class="datablock-row-count-hideshow">
                    <span>Всего строк:</span>
                    <span data-meaning="count-total">
                        <xsl:value-of select="/report/record-count-total" />
                    </span>
                </div>

            </div>

            <button class="btn btn-default filter-dialog-button" data-meaning="api-button" data-api-method="filter_dialog" data-api-after="functionsDB.installFilterDialogEvents">Настроить фильтр...</button>
        </div>
    </div>

</xsl:template>


<xsl:template match="/report/filter-info/filter-elem">
    <div>
        <span>
            <xsl:value-of select="field/caption" />
        </span>
        <span>
            <xsl:value-of select="filter/display" />
        </span>
        <span>
            <xsl:value-of select="param" />
        </span>
        <span>
            <a class="btn btn-xs btn-default" data-meaning="api-button" data-api-method="filter_clear_one" data-api-param-filter-index="{index}">
                <img src="modules/db/images/red_cross_diag.png" alt="delete filter" />
            </a>
        </span>
    </div>
</xsl:template>


<xsl:template match="/report/builtin-buttons/pager">
    <div class="datablock-header-control btn-group">
        <a class="btn btn-default" data-meaning="api-button" data-api-method="page_first">
            <img src="modules/db/images/page_first.png" alt="first" />
        </a>
        <a class="btn btn-default" data-meaning="api-button" data-api-method="page_prev">
            <img src="modules/db/images/page_prev.png" alt="prev" />
        </a>
        <xsl:call-template name="row-count-informer" />
        <a class="btn btn-default" data-meaning="api-button" data-api-method="page_next">
            <img src="modules/db/images/page_next.png" alt="next" />
        </a>
        <a class="btn btn-default" data-meaning="api-button" data-api-method="page_last">
            <img src="modules/db/images/page_last.png" alt="last" />
        </a>
    </div>
</xsl:template>

<xsl:template match="/report/builtin-buttons/calendar">
    <div class="datablock-header-control">
        <xsl:value-of select="./@caption" />:
        <input type="text" class="form-control" data-meaning="simple-calendar" data-field-name="{.}" />
        <button class="glyphicon glyphicon-remove" data-meaning="simple-filter-cleaner" data-target-filter-type="simple-calendar" data-target-field-name="{.}"></button>
    </div>
</xsl:template>

<xsl:template match="/report/builtin-buttons/fast-search">
    <div class="datablock-header-control">
        <input type="text" placeholder="поиск" class="form-control" data-meaning="simple-search-box" data-search-in=".datablock-content td" data-scroll-container=".datablock-content" />
        <button class="glyphicon glyphicon-remove" data-meaning="simple-filter-cleaner" data-target-filter-type="simple-search-box"></button>
    </div>
</xsl:template>

<xsl:template match="/report/builtin-buttons/fast-filter">
    <div class="datablock-header-control">
        <input type="text" placeholder="фильтр" class="form-control" data-meaning="simple-filter-box" data-search-in=".datablock-content table" data-scroll-container=".datablock-content" />
        <button class="glyphicon glyphicon-remove" data-meaning="simple-filter-cleaner" data-target-filter-type="simple-filter-box"></button>
    </div>
</xsl:template>

<xsl:template match="/report/builtin-buttons/counter">
    <xsl:if test="not(/report/builtin-buttons/pager)">
        <xsl:call-template name="row-count-informer" />
        <xsl:text> </xsl:text>
    </xsl:if>
</xsl:template>

<xsl:template match="/report/builtin-buttons/filter-clear">
    <a class="btn btn-default" data-meaning="api-button" data-api-method="filter_clear_all">
        Сброс фильтра
    </a>
</xsl:template>



<!-- very special filtering -->
<xsl:template match="/report/filter-groups">
    <span class="datablock-filter-buttons">
        <xsl:apply-templates select="filter-group" />
    </span>
</xsl:template>


<xsl:template match="filter-group">
    <span class="btn-group" data-filter-group="{@name}" data-filter-type="{@type}">
        <xsl:apply-templates select="filter-button" />
    </span>
</xsl:template>


<xsl:template match="filter-button">
    <label class="datablock-filter-button btn btn-info btn-sm">
        <input data-filter-name="{@name}" data-filter-condition="{condition}" data-filter="{value}" data-field-name="{field}" type="checkbox" class="btn btn-info btn-sm" />
        <span class="decorative"></span><!-- this span changes color of checked button -->
        <xsl:if test="caption != ''">
            <span class="button-caption"><xsl:value-of select="caption" /></span>
        </xsl:if>
    </label>
</xsl:template>



<!-- datablock contents -->
<xsl:template match="data_row">
    <tr data-row-id="{@id}">
        <xsl:for-each select="./data|./special">
            <td>
                <xsl:choose>
                    <xsl:when test="name(.)='data'">
                        <xsl:attribute name="data-field-name"><xsl:value-of select="@field" /></xsl:attribute>
                        <xsl:choose>
                            <xsl:when test="@out_as_is">
                                <xsl:value-of select="." disable-output-escaping="yes" />
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="." />
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:when>
                    <xsl:when test="name(.)='special'">
                        <input type="checkbox" class="{./@class}" name="{./@name}" value="{./@value}" data-meaning="row-select-checkbox" />
                    </xsl:when>
                </xsl:choose>
            </td>
        </xsl:for-each>
    </tr>
</xsl:template>


</xsl:stylesheet>