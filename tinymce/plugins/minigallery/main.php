<?php
header('Content-Type: text/html; charset=utf-8');

require('_init.php');

require('lib/xslt.cls.php');
require('lib/errors.cls.php');

// first, get album ID to show, try firt from GET, then from session, then root
if (($id = get_album_id(@$_GET['id'])) > '') {
	$_SESSION['gallery_'.DB_PREFIX]['folder_id'] = $id;
} else {
	$id = isset($_SESSION['gallery_'.DB_PREFIX]['folder_id']) ? $_SESSION['gallery_'.DB_PREFIX]['folder_id'] : $root_guid;
	$_SESSION['gallery_'.DB_PREFIX]['folder_id'] = $id;
}

$_ = '';

///// error reporting and other messaging /////////////////////////////////////////////////////////
if (isset($_SESSION['gallery_'.DB_PREFIX]['message'])&&($_SESSION['gallery_'.DB_PREFIX]['message']>'')) {
	$_ .= '<message>'.$_SESSION['gallery_'.DB_PREFIX]['message'].'</message>';
	unset($_SESSION['gallery_'.DB_PREFIX]['message']);
}

///// folder list (for moving ability) ////////////////////////////////////////////////////////////
$_ .= '<folderlist>';
$query = $DB->query('select id, caption from '.$tab_files.' where type = 1 order by caption');
while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
	$_ .= '<folder id="'.$data['id'].'">'.$data['caption'].'</folder>';
}
$_ .= '</folderlist>';

///// ok, generate ////////////////////////////////////////////////////////////////////////////////
$_ .= create_list_xml();
$_ .= create_path_xml();

echo XSLTransform('<root>'.$_.'</root>', 'main.xsl');
?>