<?php

// check the SESSION if main CMS admin is logged in
if (
	(!isset($_SESSION) || !isset($_SESSION['CMS_AUTH_USER']) || ($_SESSION['CMS_AUTH_USER'] != 'admin'))
		&&
	(!isset($_SESSION) || !isset($_SESSION['module_auth_user_logged']) || ($_SESSION['module_auth_user_logged'] == ''))
	){
	header('HTTP/1.1 403 Forbidden');
	echo '<h1>403 Forbidden</h1>';
	exit;
}

// database path, relative to current directory. SQLite only.
define('DB_PATH', '../../../userfiles/_data_common/tinymce.minigallery.sqlite');

// table prefix for multiple instances
define('DB_PREFIX', 'minigallery');

// storage for pictures (for php works)
define('RELATIVE_DIR', '../../../userfiles/images/');

// path to image storage from CMS root (will be appended to image links)
define('ABSOLUTE_DIR', 'userfiles/images/');


?>