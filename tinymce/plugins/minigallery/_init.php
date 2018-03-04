<?php

session_start();

setlocale(LC_ALL, 'ru_RU.UTF-8');
ini_set('magic_quotes_gpc', 'off');

// db connect & init ////////////////////////////////////////////////////////////////////
require_once('_config.php');
require_once('pdowrapper.php');

$DB = new PDOWrapper('sqlite', DB_PATH);

// constants ////////////////////////////////////////////////////////////////////////////
$tab_files = (DB_PREFIX > '' ? DB_PREFIX.'_' : '') . 'files';

// general init
$re_guid  = '~^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$~';
$re_name  = '~^[a-zA-Zа-яёА-ЯЁ0-9_\.\-\s]{1,250}$~u';
$re_alias = '~^[a-zA-Z0-9]{1,100}$~';
$re_url   = '~^(?:(?:ht|f)tps?://)?(?:[\\-\\w]+:[\\-\\w]+@)?(?:[0-9a-z][\\-0-9a-z]*[0-9a-z]\\.)+[a-z]{2,6}(?::\\d{1,5})?(?:[?/\\\\#][?!^$.(){}:|=[\\]+\\-/\\\\*;&\~#@,%\\wА-Яа-я]*)?$~';

$root_guid = '00000000-0000-0000-0000-000000000000';

$msg_err_bad_filename    = 'Неправильное имя файла';
$msg_err_bad_url         = 'Корявая ссылка';
$msg_err_bad_file_format = 'Неизвестный формат файла';
$msg_err_alias_exists    = 'Алиас уже используется';


///////////////////////////////////////////////////////////////////////////////////////////////////
function create_guid() {
	if (function_exists('com_create_guid') === true) {
		return strtolower(trim(com_create_guid(), '{}'));
	}
	return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function get_as_regexp($str, $regexp) {
	return (preg_match($regexp, $str)!=1?'':$str);
}

// first try _GET id as id, then as alias /////////////////////////////////////////////////////////
function get_album_id($try = '') {
	global $DB, $tab_files, $re_guid, $re_alias, $root_guid;
	if ($DB->querySingle('select count(`id`) from `'.$tab_files.'` where `id`=\''.get_as_regexp($try, $re_guid).'\'') != 0) {
		return $try;
	}
	if (($id = $DB->querySingle('select `id` from `'.$tab_files.'` where `name`=\''.get_as_regexp($try, $re_alias).'\'')) > '') {
		return $id;
	}
	if (isset($_GET['id'])) {
		return $root_guid;
	}
	return '';
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function create_list_xml() {
	global $DB, $id, $tab_files;
	
	$r = '';
	$query = $DB->query("select * from `$tab_files` where `parent_id`='$id' order by `type` desc, `caption`");
	while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
		$r .= '<albumitem id="'.$data['id'].'">'
			.'<type>' . $data['type'] . '</type>'
			.'<preview>' . ($data['type'] == 1 ? '' : 'preview/'.$data['name']) . '</preview>'
			.'<name>' . ($data['type'] == 1 ? $data['name'] : 'gallery/'.$data['name']) . '</name>'
			.'<caption><![CDATA[' . $data['caption'] . ']]></caption>'
			.'<full_path>' . ABSOLUTE_DIR.'gallery/'.$data['name'] . '</full_path>'
			.'<full_path_preview>' . RELATIVE_DIR.'gallery/preview/'.$data['name'] . '</full_path_preview>'
			.'</albumitem>';
	}
	return '<list>'.$r.'</list>';
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function create_path_xml() {
	global $DB, $tab_files, $root_guid;
	$r = '';
	$folder_id = $_SESSION['gallery_'.DB_PREFIX]['folder_id'];
	while ($folder_id != $root_guid) {
		$q = $DB->query("select caption, name, parent_id from $tab_files where id='$folder_id'");
		if (!($data = $q->fetch(PDO::FETCH_NUM))) {
			break;
		}		
		list ($folder_caption, $name, $parent_id) = $data;
		$more = '';
		$more .= '<folder'.($folder_id == $_SESSION['gallery_'.DB_PREFIX]['folder_id'] ? ' current="yes"' : '').'>';
		$more .= '<id>'.$name.'</id>';
		$more .= '<caption><![CDATA['.$folder_caption.']]></caption>';
		$more .= '</folder>';
		$r = $more . $r;
		$folder_id = $parent_id;
	}
	$r = '<folder root="yes"'.($_SESSION['gallery_'.DB_PREFIX]['folder_id'] == $root_guid ?' current="yes"':'').'><id>0</id><caption>Начало</caption></folder>' . $r;
	return '<folderpath>'.$r.'</folderpath>';
}
?>