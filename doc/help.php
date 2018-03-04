<?php

chdir('..');

set_include_path('./lib' . PATH_SEPARATOR . './lib/PEAR');

include('userfiles/_data_common/conf.php');
include_once('lib/Logger.class.php');
include_once('lib/cms.php');
include_once('lib/common.php');

$_ = '';

$check_regexp = '~^[a-zA-Z0-9_\-][a-zA-Z0-9_\-.]*$~';

// whether we should get something from module's help or from main
$path = (isset($_GET['path']) && preg_match($check_regexp, $_GET['path'])) ? MODULES_DIR.$_GET['path'].'/help/' : '';

// proxy mode - just redirect file content to the output (useful for images)
$proxy = (isset($_GET['proxy']) && preg_match($check_regexp, $_GET['proxy'])) ? $_GET['proxy'] : false;
if ($proxy) {
	file_to_output( ($path>'' ? $path : 'doc/') . $proxy );
	exit;
}


// check for filename to include
$get = (isset($_GET['get']) && preg_match($check_regexp, $_GET['get'])) ? $_GET['get'] : '';

// if both path and get are empty, display generated content and FAQ, otherwise get content
if ($path.$get == '') {
	$_ =
		file_get_contents('doc/useful.html').
		'<h3>Справка по модулям</h3>'.
		create_module_help_links();
} else {
	$full_filename = ($path>'' ? $path : 'doc/') . ($get>'' ? $get : 'help.html');
	$_ = 
		'<div class="btn-group top-right">'.
			'<a href="./help.php" class="btn btn-info btn-sm" data-button-action="help-to-content">Содержание</a>'.
		'</div>'.
		(file_exists($full_filename) ? file_get_contents($full_filename) : '<h3>sorry, no file :-(</h3>');
}

?><!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Справка</title>
	<script src="../lib/jquery.js" type="text/javascript"></script>
	<script src="../lib/jquery-ui.js" type="text/javascript"></script>
	<script src="help.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="../lib/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="../lib/jquery-ui.css" />
	<link rel="stylesheet" type="text/css" href="help.css" />
</head>
<body>
<?php echo $_; ?>
</body>
</html>
<?php

// defines module order in main help page
function module_sorter($a1, $a2) {
	$module_order = array(
		'content',
		'menu',
		'news',
		'filemanager',
		'feedback',
		'search',
		'backup',
		'auth',
		'admin',
		'redirect',
		'db',
		'sms',
	);
	return array_search($a1, $module_order) - array_search($a2, $module_order);
}

// creates modules' help link HTML
function create_module_help_links() {
	include_once('lib/JuliaCMSModule.class.php');
	include_once('lib/xml_to_array.php');

//	usort($modules_apply_order, 'module_sorter');
	$result = '';
	foreach(CMS::$R['modules_apply_order'] as $module) {
		if (module_init($module)) {
			$link = MODULES_DIR.$module.'/help/help.html';
			if (file_exists($link)) {
				$caption = CMS::$cache[$module]['config']['comment'];
				$result .= '<a class="big-link" href="./help.php?path='.$module.'" alt="'.$module.'">'.$caption.' ('.$module.')</a><br />';
			}
		}
	}
	return $result;
}

?>