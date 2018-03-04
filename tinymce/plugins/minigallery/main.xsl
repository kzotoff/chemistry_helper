<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/">
	<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html></xsl:text>
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>
		<xsl:text>gallery!</xsl:text>
	</title>
	<link rel="stylesheet" type="text/css" href="gallery.css" />
	<link rel="stylesheet" type="text/css" href="../../../lib/jquery-ui.css" />
	<script src="../../../lib/jquery.js" type="text/javascript"></script>
	<script src="../../../lib/jquery-ui.js" type="text/javascript"></script>
	<script src="js.js" type="text/javascript"></script>
	</head>
	<body>
	
	<div id="mainbar">
<!-- path to the folder *********************************************************************** -->
		<div id="folder_path">
			<xsl:apply-templates select="/root/folderpath" />	
		</div>

<!-- buttons as needed ************************************************************************ -->
		<div class="buttondiv bn right" id="b_del_item"><img src="./images/del.png" alt="" /></div>
		<div class="buttondiv bn right" id="b_move_item"><img src="./images/move.gif" alt="" /></div>
		<div class="buttondiv bn right" id="b_add_album"><img src="./images/folder_add.png" alt="" /></div>
		<div class="buttondiv bn right" id="b_add_photo"><img src="./images/add.png" alt="" /></div>
		<div class="antifloat"></div>
	</div>

	<div id="addphotoform" class="popupform">
		<form action="manage.php" enctype="multipart/form-data" method="post">
		<input type="hidden" name="action" value="addfile" />
		<table class="commonform">
		<tr><td class="cf_l w200">Файл</td><td class="cf_r w300"><input id="filenameinput" class="filenameinput" type="file" multiple="multiple" accept="image/*" name="filename[]" /></td></tr>
		<tr><td class="cf_l w200">URL</td><td class="cf_r w300"><input id="urlinput" class="textinput" type="text" name="url" /></td></tr>
		<tr><td class="cf_comment" colspan="2">Можно одновременно URL и файл. Или несколько файлов.</td></tr>
		<tr><td class="cf_l">Название</td><td class="cf_r"><input class="textinput" type="text" name="caption" /></td></tr>
		<tr><td class="cf_comment" colspan="2">Допустимые форматы: jpg, gif, png</td></tr>
		<tr><td class="cf_c" colspan="2"><input id="a111" type="submit" value="Загрузить" class="onetimebutton" /></td></tr>
		</table>
		</form>
	</div>

	<div id="addalbumform" class="popupform">
		<form action="manage.php" method="post">
		<input type="hidden" name="action" value="addalbum" />
		<table class="commonform">
		<tr><td class="cf_l w200">Название</td><td class="cf_r w300"><input class="textinput" type="text" name="albumname" /></td></tr>
		<tr><td class="cf_c" colspan="2"><input id="a222" type="submit" value="Создать" class="onetimebutton" /></td></tr>
		</table>
		</form>
	</div>

	<div id="moveform" class="popupform">
		<table class="commonform">
		<tr>
			<td class="cf_l w200">Куда переместим?</td>
			<td class="cf_r w300">
				<select class="textinput" id="movetarget" name="movetarget">
					<option value="00000000-0000-0000-0000-000000000000">* в корень *</option>
					<xsl:for-each select="/root/folderlist/folder">
						<option value="{@id}">
						<xsl:value-of select="." />
						</option>
					</xsl:for-each>
				</select>
			</td>
		</tr>
		<tr><td class="cf_c" colspan="2"><input id="movebutton" type="button" value="Переместить" /></td></tr>
		</table>
	</div>
	
	<xsl:apply-templates select="/root/message" />
	
<!-- folders and pictures ********************************************************************* -->
	<div class="album" id="all_images">
		<xsl:apply-templates select="/root/list" />
		<div class="antifloat"></div>
	</div>

<!-- some common info ************************************************************************* -->
	</body>
	</html>
</xsl:template>

<!-- folder path info ************************************************************************* -->
<xsl:template match="folder">
	<div class="folder_path_element">
		<span>
			<xsl:if test="not (@root = 'yes')">
				<xsl:text>&#160;/&#160;</xsl:text>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="@current = 'yes'">
					<xsl:value-of select="caption" disable-output-escaping="yes" />
				</xsl:when>
				<xsl:otherwise>
					<a href="main.php?id={id}"><xsl:value-of select="caption" disable-output-escaping="yes"  /></a>
				</xsl:otherwise>
			</xsl:choose>
		</span>
	</div>
</xsl:template>

<!-- album and items ************************************************************************** -->
<xsl:template match="albumitem">

	<div class="album_item">
		<xsl:choose>
			<xsl:when test="./type = 1">
				<xsl:attribute name="class">album_item album_folder</xsl:attribute>
				<a class="preview_div" href="main.php?id={./name}">
					<img src="images/folder.gif" class="preview" alt="" />
				</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name="class">album_item album_picture</xsl:attribute>
				<div class="img_link preview_div">
					<img src="{full_path_preview}" class="preview" alt="{caption}" />
				</div>
			</xsl:otherwise>
		</xsl:choose>

		<div class="caption_div">
			<span class="album_caption">
				<xsl:value-of select="caption" disable-output-escaping="yes" />&#160;
			</span>
			<xsl:if test="./type = 0">
				<input type="text" value="{full_path}" style="" />
			</xsl:if>
		</div>
		
		<input type="checkbox" name="checkbox_{@id}" id="cb_{@id}" class="cb" />
		

	</div>
</xsl:template>

<xsl:template match="message">
	<div id="errorform" class="popupform">
	<span class="block"><xsl:value-of select="." /></span>
	<input type="button" id="ef_ok" class="block" value="OK" />
	</div>
</xsl:template>

<!-- placeholders ***************************************************************************** -->
<xsl:template match="parentinfo|albuminfo">
</xsl:template>

</xsl:stylesheet>