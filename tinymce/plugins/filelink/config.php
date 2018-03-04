<?php

// check the SESSION if main CMS admin is logged in
session_start();

if (!isset($_SESSION['module_auth_user_logged']) || ($_SESSION['module_auth_user_logged'] != 'admin')) {
	header('HTTP/1.1 403 Forbidden');
	echo '<h1>403 Forbidden</h1>';
	exit;
}

// path the the file storage relative to this directory
$path_for_php_works = '../../../userfiles/files/';

// path the storage located as it visible from JS scripts (usually relative to tinyMCE root)
$path_for_js_works = 'userfiles/files/';

?>