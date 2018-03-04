<?php //> Й <- UTF mark

// some pre-production useful things
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

// utility for fast site upload. remove or comment this line after final production deployment
include_once('lib/uploader.php');

session_start();

// some hosters prohibit to use php_value at .htaccess
set_include_path('./lib' . PATH_SEPARATOR . './lib/PEAR');

// make sure of utf-8 browser encoding
header('Content-type: text/html; charset=utf-8');

// for the name of the correct sorting and uppercase!
mb_internal_encoding('UTF-8');

require_once('lib/Logger.class.php');

// logger is the first
Logger::instance(array(
	'target'         => 'file',
	'format'         => 'plain',
	'line_delimiter' => PHP_EOL,
	'filename'       => 'logs/cms.log',
	'flush'          => 'immediate',
	'min_level'      => Logger::LOG_LEVEL_MESSAGE,
	'output_format'  => '%date% %time% %time_delta_start% (%time_delta_prev%) %memory_color_start%%memory_delta_prev%%memory_color_end% [%level%] %message%'

));

// connect to config and useful things
require_once('userfiles/_data_common/conf.php');
require_once('lib/cms.php');
require_once('lib/xml_to_array.php');
require_once('lib/common.php');
require_once('lib/PDOWrapper.class.php');
require_once('lib/JuliaCMSModule.class.php');

// connect to the DB
CMS::$DB = new PDOWrapper('sqlite', DB_PATH);

// restore _GET (damaged by mod_rewrite) using REQUEST_URI
if (isset($_SERVER['REQUEST_URI'])) {
	parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $more_params);
	$_GET = array_merge($_GET, $more_params);
	Logger::instance()->log('GET restored: '.print_r($_GET, 1), Logger::LOG_LEVEL_DEBUG);
}

// convert CLI arguments to _GET if we're in CLI mode
if (isset($argv)) {
	foreach ($argv as $arg) {
		$pos = strpos($arg, '=');
		if ($pos === false) {
			$_GET[ $arg ] = '';
		} else {
			$_GET[ substr($arg, 0, $pos) ] = substr($arg, $pos+1);
		}
	}
//	var_dump($_GET);
//	exit;
}

// set default page if not specified in request
$_GET['p_id'] = get_array_value($_GET, 'p_id', DEFAULT_PAGE_ALIAS, '~.+~');
Logger::instance()->log('page alias to show: '.$_GET['p_id'], Logger::LOG_LEVEL_DEBUG);

// apply security limitations
require_once('lib/security.php');
login_logout();
Logger::instance()->log('security applied', Logger::LOG_LEVEL_DEBUG);

// user functions (not for DB module!)
// TAG_TODO сделать нормальный механизм для автоподключения всего барахла в той папке и в мануал написать
require_once('userfiles/php/common.php');

// check input for intersected keys
if (count(array_intersect_key($_POST, $_GET)) > 0) {
	terminate('POST and GET has duplicate keys', 'POST and GET has duplicate keys', 403);
}

// AJAX-proxy mode: just call special function and return its output, skipping normal flow
if ($module_name = isset($_POST['ajaxproxy']) ? $_POST['ajaxproxy'] : (isset($_GET['ajaxproxy']) ? $_GET['ajaxproxy'] : false)) {
	if (($init_result = module_init($module_name)) !== true) {
		terminate('module init error: '.$init_result, 500);
	}
	echo CMS::$cache[$module_name]['object']->AJAXHandler();
	terminate();
}

// init modules
foreach (CMS::$R['modules_apply_order'] as $module_name) {
	$init_result = module_init($module_name);
	if ($init_result !== true) {
		popup_messages_add($init_result, JCMS_MESSAGE_ERROR);
	}
}

// well, this is main template, we will transform it
$template = file_get_contents('userfiles/template/template.html');

// immediately add core libraries and stylesheets to ensure their minimal priority
add_JS(array(
	'lib/jquery.js',
	'lib/jquery-ui.js',
	'lib/jquery.combobox.js',
	'lib/jquery.tablesorter.min.js',
	'tinymce/tinymce.min.js',
	'tinymce/jquery.tinymce.min.js',
	'lib/jquery-ui-sliderAccess.js',
	'lib/jquery-ui-timepicker-addon.min.js',
	'lib/sfm/simplefilemanager.js',
	'lib/lib.js',
	'lib/base64.js',
));

add_CSS(array(
	'lib/jquery-ui.css',
	'lib/jquery.combobox.css',
	'lib/tablesorter.css',
	'lib/bootstrap.min.css',
	'lib/jquery-ui-timepicker-addon.min.css',
	'lib/sfm/simplefilemanager.css',
	'lib/core.css',
));

// first loop: add modules' CSS and JS links
foreach (CMS::$R['modules_apply_order'] as $module_name) {

	// check if module OK
	if (!isset(CMS::$cache[$module_name])) {
		Logger::instance()->log('module description not loaded: '.$module_name, Logger::LOG_LEVEL_WARNING);
		continue;
	}
	// also module may be disabled
	if (get_array_value(CMS::$cache[$module_name]['config'], 'disabled', false) === true) {
		continue;
	}

	Logger::instance()->log('connecting external files for module: '.$module_name, Logger::LOG_LEVEL_DEBUG);
	add_CSS(get_array_value(CMS::$cache[$module_name]['config'], 'css', array()), MODULES_DIR.$module_name.'/');
	add_JS(get_array_value(CMS::$cache[$module_name]['config'], 'js', array()), MODULES_DIR.$module_name.'/');
	
	// check if we should stop here
	if (get_array_value(CMS::$cache[$module_name]['config'], 'break_after', false)) {
		break;
	}

}

// second loop: input parsers
// look previous loop for comments
foreach (CMS::$R['modules_apply_order'] as $module_name) {
	Logger::instance()->log('trying input parser for module: '.$module_name, Logger::LOG_LEVEL_DEBUG);

	if (!isset(CMS::$cache[$module_name])) {
		Logger::instance()->log('module description not loaded: '.$module_name, Logger::LOG_LEVEL_WARNING);
		continue;
	}
	if (get_array_value(CMS::$cache[$module_name]['config'], 'disabled', false) === true) {
		continue;
	}
	$template = CMS::$cache[$module_name]['object']->requestParser($template);
	if ($template == '') {
		terminate('WARNING: empty template returned after input parser at module: '.$module_name, Logger::LOG_LEVEL_WARNING);
	}
	Logger::instance()->log('parser finished for module: '.$module_name, Logger::LOG_LEVEL_DEBUG);

	if (get_array_value(CMS::$cache[$module_name]['config'], 'break_after', false)) {
		break;
	}
}

// third loop: template processors
foreach (CMS::$R['modules_apply_order'] as $module_name) {
	Logger::instance()->log('trying template processor at module: '.$module_name, Logger::LOG_LEVEL_DEBUG);

	if (!isset(CMS::$cache[$module_name])) {
		Logger::instance()->log('module description not loaded: '.$module_name, Logger::LOG_LEVEL_WARNING);
		continue;
	}
	
	if (get_array_value(CMS::$cache[$module_name]['config'], 'disabled' === true)) {
		continue;
	}
	
	Logger::instance()->log('applying template processor at module: '.$module_name, Logger::LOG_LEVEL_DEBUG);
	$template = CMS::$cache[$module_name]['object']->ContentGenerator($template);
	Logger::instance()->log('template processor finished at module: '.$module_name, Logger::LOG_LEVEL_DEBUG);

	if ($template == '') {
		terminate('WARNING: empty template returned after template processor at module: '.$module_name);
	}
	
	if (get_array_value(CMS::$cache[$module_name]['config'], 'break_after', false)) {
		break;
	}
}

// remove unused templates
$template = preg_replace('~</?macro.*?>~', '', $template);
$template = preg_replace('~\[/?macro.*?\]~', '', $template);

// back-replace protected templates
$template = str_replace('<protected-macro', '<macro', $template);
$template = str_replace('[protected-macro', '[macro', $template);
$template = str_replace('</protected-macro', '</macro', $template);
$template = str_replace('[/protected-macro', '[/macro', $template);
Logger::instance()->log('unused templates removed', Logger::LOG_LEVEL_DEBUG);

$template = popup_messages_to_template($template);
Logger::instance()->log('popups added', Logger::LOG_LEVEL_DEBUG);

// sign it!
add_meta('name', 'generator', 'JuliaCMS Valenok Edition');
add_meta('charset', 'utf-8');

// flush meta, CSS and JS storages
$template = flush_CSS($template);
$template = flush_JS($template);
$template = flush_meta($template);

// yeah we did it!
Logger::instance()->log('page generation completed', Logger::LOG_LEVEL_DEBUG);

echo $template;

terminate();