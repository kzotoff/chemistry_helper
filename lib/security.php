<?php

/*

CMS login/logout routines. Note that it doesn't intersect auth module

*/
function login_logout() {

	// display login page instead any content, if requested
	if (isset($_GET['cmslogin']) && (!isset($_SESSION['CMS_AUTH_USER']) || ($_SESSION['CMS_AUTH_USER'] == ''))) {
		readfile('lib/login.html');
		terminate();
	}

	// check login/password if any
	if (isset($_POST['action']) && ($_POST['action'] == 'checklogin')) {
		if ((@$_POST['userlogin'] == 'admin') && (@$_POST['userpassword'] == CMS_ADMIN_PASSWORD)) {
			$_SESSION['CMS_AUTH_USER'] = 'admin';
		}
	}

	// or logout?
	if (isset($_GET['cmslogout'])) {
		$_SESSION['CMS_AUTH_USER'] = '';
		unset($_SESSION['CMS_AUTH_USER']);
		terminate('', 'Location: ./', 302);
	}
}

// CMS functions access username
function security_get_username_cms() {
	if (isset($_SESSION['CMS_AUTH_USER'])) {
		return $_SESSION['CMS_AUTH_USER'];
	}
	return '';
}

// modules' functions access username
// TAG_TODO как-то увязать с наличием модуля авторизации в целом
function security_get_username_auth() {
	if (isset($_SESSION['module_auth_user_logged'])) {
		return $_SESSION['module_auth_user_logged'];
	}
	return 'anonymous';
}

/**
 * Removes item not available for current user from array
 *
 * @param array $array any data to filter
 * @param string $array_key key to be the item ID
 * @return array filtered array
 */
function filter_array_for_user($array, $array_id_key) {

	// настройка видимости элементов меню
	// TAG_MOD project TOM2
	return $array;
	
	// yeah, it's superuser, return as-is
	if (is_superuser()) {
		return $array;
	}

	// тут перечисляем, что можно не-админу
	foreach ($array as $index=>$item) {
		if (!in_array($item['alias'], array(
		))) {
			unset($array[$index]);
		}
	}
	return $array;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// internal CMS user rights check
// list all actions here, even not secured by now.
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
function user_allowed_to_admin($action, $params = array()) {

	// this list should include all available actions to ensure nothing is forgotten
	// will report on unknown action even if admin logged in
	$cms_action_list = array(
		'manage site',
		'manage modules',
		'backup works',
		'manage pages',
		'edit pages',
		'manage files',
		'manage menu',
		'manage news',
		'search',
		'manage feedback templates',
		'chat',	
	);

	if (!in_array($action, $cms_action_list)) {
		popup_message_add('action not secured: "'.$action.'"', JCMS_MESSAGE_WARNING);
	}
	
	$username = security_get_username_cms();
	
	// admin godmode
	if ($username == 'admin') {
		return true;
	}
	
	switch ($action) {
			case 'search': return true; break;
	}
	return false;
}

function is_superuser() {
	
	// есть ли логин вообще
	if (
		!isset(CMS::$cache['auth']) ||
		!isset(CMS::$cache['auth']['config']) ||
		!isset(CMS::$cache['auth']['config']['config']) ||
		!isset(CMS::$cache['auth']['config']['config']['session_username']) ||
		!isset($_SESSION[ CMS::$cache['auth']['config']['config']['session_username'] ])
	) {
		return false;
	}
	return get_array_value($_SESSION, 'god_mode', false) == true;
}

function user_allowed_to_do($action, $params = array()) {

	// TAG_MOD project TOM2 start
	return true;
	// TAG_MOD project TOM2 end

	$username = security_get_username_auth();
	
	$userland_action_list = array(
		'auth.change_other_passwords',
		'db.record.insert',
		'db.record.update',
		'db.record.delete',
		'db.comment.delete',
		'userapi.group.nodes',
	);

	if (!in_array($action, $userland_action_list)) {
		popup_message_add('action not secured: "'.$action.'"', JCMS_MESSAGE_WARNING);
	}

	// superman
	if ($username == 'admin') {
		return true;
	}

	switch ($action) {
			// module: db
//			case 'db.record.update': break;
//			case 'db.record.delete': break;
//			case 'db.comment.delete': break;
	}	
	
	// "no" by default
	return false;
}

?>