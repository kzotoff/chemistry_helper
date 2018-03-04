<?php

include_once('lib/cms.php');

/**
 * Regular expression for alias checking
 *
 * @const REGEXP_ALIAS
 */
const REGEXP_ALIAS = '~^[a-zA-Z0-9\_\-]+$~ui';

/**
 * Regular expression for multifunctional use
 * one may check identifiers, filenames (without extensions) and may other useful things
 *
 * @const REGEXP_ALIAS
 */
const REGEXP_IDENTIFIER = '~^[a-zA-Z\_\-][a-zA-Z0-9\_\-]+$~ui';

/**
 * Standard e-mail address
 *
 * @const REGEXP_EMAIL
 */
const REGEXP_EMAIL = '~[a-zA-Z0-9._\-]+@[a-zA-Z0-9._\-]{3,500}~';

/**
 * Input filtering option: use _GET, return only fields that are in filter array
 *
 * @const FILTER_GET_BY_LIST
 */
const FILTER_GET_BY_LIST = 1;

/**
 * Input filtering option: use _POST, return only fields that are in filter array
 *
 * @const FILTER_POST_BY_LIST
 */
const FILTER_POST_BY_LIST = 2;

/**
 * Input filtering option: use _GET, return filtered values wich keys are in filter array, the rest values come "as is"
 *
 * @const FILTER_GET_FULL
 */
const FILTER_GET_FULL = 3;

/**
 * Input filtering option: use _POST, return filtered values wich keys are in filter array, the rest values come "as is"
 *
 * @const FILTER_POST_FULL
 */
const FILTER_POST_FULL = 4;

/**
 * Userland files (pages contents, stylesheets, scripts and so on)
 *
 */
CMS::$R['USERFILES_DIRS'] = array(
	'css' => array(
		'caption'         => 'CSS',                                            // caption at the list
		'dir'             => 'userfiles/css/',                                 // storage directory
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.css',  // upload/edit/delete checkout
		'extensions'      => array('css'),                                     // possible file extensions
	),
	'js' => array(
		'caption'         => 'javascripts files',
		'dir'             => 'userfiles/js/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.js',
		'extensions'      => array('js'),
	),
	'images' => array(
		'caption'         => 'images',
		'dir'             => 'userfiles/images/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.(png|gif|jpg|bmp)',
	),
	'pages' => array(
		'caption'         => 'pages',
		'dir'             => 'userfiles/pages/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.(html|htm|php|xml)',
		'extensions'      => array('html'),
	),
	'xsl' => array(
		'caption'         => 'XSL stylesheets',
		'dir'             => 'userfiles/xsl/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.(xsl|xml)',
		'extensions'      => array('xsl', 'xml'),
	),
	'php' => array(
		'caption'         => 'PHP scripts',
		'dir'             => 'userfiles/php/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-s_.]*\.(php|xsl)',
		'extensions'      => array('php'),
	),
	'special' => array( // CMS root
		'caption'         => 'Main files',
		'dir'             => 'userfiles/template/',
		'regexp_filename' => '(?!(jquery|tablesorter))[a-zA-Zа-яА-Я0-9\-_]+\.(html|css|js|ico)',
		'extensions'      => array('html'),
	),
	'files' => array( // must be after all others but before trash
		'caption'         => 'files',
		'dir'             => 'userfiles/files/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_\s.]*\.[a-zA-Zа-яА-Я0-9]+',
	),
	'trash' => array( // must be the last
		'caption'         => 'trash',
		'dir'             => 'userfiles/trash/',
		'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_\s.]*\.[a-zA-Zа-яА-Я0-9]+',
	),
);

/**
 * Move modules list to global space
 */
CMS::$R['modules_apply_order'] = $modules_apply_order;

/**
 * Popup message types
 *
 * @const JCMS_MESSAGE_INFO
 * @const JCMS_MESSAGE_OK
 * @const JCMS_MESSAGE_WARNING
 * @const JCMS_MESSAGE_ERROR
 * @const JCMS_MESSAGE_FATAL
 */
const JCMS_MESSAGE_INFO    = 0;
const JCMS_MESSAGE_OK      = 1;
const JCMS_MESSAGE_WARNING = 2;
const JCMS_MESSAGE_ERROR   = 3;
const JCMS_MESSAGE_FATAL   = 4;


/**
 * Returns configuration path and filename, relative to the CMS root
 *
 * @param string $module_name
 *
 */
function get_module_config_filename($module_name) {
	return 'userfiles/_data_modules/'.$module_name.'/config.xml';
}

/**
 * parsed module configuration file and returns result
 *
 * @param string $module_name module name to get config from
 * @param mixed &$config config array will be stored here on success, false on failure
 * @return true on success, error message on failure
 */
function module_get_config($module_name, &$config) {

	$config_filename = get_module_config_filename($module_name);

	if (file_exists($config_filename)) {
		$config = get_array_value(StrangeXMLParser::fromFile($config_filename), 'JuliaCMS_module_definition');
		if (!$config) {
			return 'Parsing &quot;'.$config_filename.'&quot; failed';
		}
	} else {
		$config = false;
		return 'module definition file not found for module &quot;'.$module_name.'&quot;';
	}
	return true;
}

/**
 * Initializes the module and adds it to cache
 *
 * @param string $module_name module to init
 * @return bool
 */
function module_init($module_name) {
	if (!in_array($module_name, CMS::$R['modules_apply_order'])) {
		trigger_error('[JuliaCMS] module_init: no such module: '.$module_name, E_USER_ERROR);
		return false;
	}
	// check if module config exists
	if (!isset(CMS::$cache[$module_name]['config'])) {
		if (($config_result = module_get_config($module_name, $config)) !== true) {
			return $config_result;
		}
		CMS::$cache[$module_name]['config'] = $config;
	}

	// init the module
	include_once(MODULES_DIR.$module_name.'/'.CMS::$cache[$module_name]['config']['main_script']);
	if (!isset(CMS::$cache[$module_name]['object'])) {
		$module_class_name = CMS::$cache[$module_name]['config']['class_name'];
		CMS::$cache[$module_name]['object'] = new $module_class_name();
		CMS::$cache[$module_name]['object']->CONFIG = get_array_value(CMS::$cache[$module_name]['config'],'config', array());
	}
	return true;
}

/**
 * Generates search regexp for module
 *
 * @param string $type type="somevalue" value
 * @return string
 */
function macro_regexp($type) {
	return '~(<macro\s[^>]*?type="'.$type.'"[^>]*(/>|(?<!/)>.*?</macro>)|\[macro\s[^\]]*?type="'.$type.'"[^\]]*(/\]|(?<!/)\].*?\[/macro\]))~smui';
}


/**
 * Searches string for a substring, inserts some string before matched one
 *
 * @param string $search string to look up
 * @param string $insert what to insert before $search
 * @param string $text text to find at
 * @return string modified text
 */
function str_insert_before($search, $insert, $text) {
	$pos = strpos($text, $search);
	if ($pos === false) {
		return $text;
	}
	$text = substr_replace($text, $insert, $pos, 0);
	return $text;
}


/**
 * regexp version of str_insert_before
 *
 * @param string $search string to look up
 * @param string $insert what to insert before $search
 * @param string $text text to find at
 * @return string modified text
 */
function preg_insert_before($search, $insert, $text) {
	if (preg_match($search, $text, $match)) {
		$text = preg_replace($search, $insert.$match[0], $text);
	}
	return $text;
}


/**
 * Searches a string for a substring, inserts some string after matched one
 *
 * @param string $search string to look up
 * @param string $insert what to insert after $search
 * @param string $text text to find at
 * @return string modified text
 */
function str_insert_after($search, $insert, $text) {
	$pos = strpos($text, $search);
	if ($pos === false) {
		return $text;
	}
	$text = substr_replace($text, $search.$insert, $pos, strlen($search));
	return $text;
}


/**
 * regexp version of str_insert_after
 *
 * @param string $search string to look up
 * @param string $insert what to insert after $search
 * @param string $text text to find at
 * @return string modified text
 */
function preg_insert_after($search, $insert, $text) {
	if (preg_match($search, $text, $match)) {
		$text = preg_replace($search, $match[0].$insert, $text);
	}
	return $text;
}


/**
 * Adds a CSS stylesheet link to the storage for the future flushing in the template
 *
 * @param array|string $link CSS href (one as string or many as array
 * @param string $prefix string to prepend every link. Useful for array links:
 *                       if $link is array($link1, $link2, ...) and $prefix is "./css/", links
 *                       will be generated as <link href="./css/$link1" ... and so on
 */
function add_CSS($link, $prefix = '') {

	// force slash
	if ($prefix > '') {
		$prefix = rtrim($prefix, '/').'/';
	}

	// make'em similar
	if (!is_array($link)) {
		$link = array($link);
	}

	foreach($link as $href) {
		if ($href > '') {
			array_push(CMS::$R['CSS_list'], '<link rel="stylesheet" href="'.$prefix.$href.'" type="text/css" />');
			Logger::instance()->log('CSS added: '.$href, Logger::LOG_LEVEL_DEBUG);
		}
	}
}


/**
 * Flushes pre-created CSS array into the template
 *
 * @param string $template
 * @return string modified template
 */
function flush_CSS($template) {
	if (preg_match('~<head[^>]*>~smui', $template, $match)) {
		$template = preg_replace('~<head[^>]*>~smui', $match[0].PHP_EOL.implode(PHP_EOL, CMS::$R['CSS_list']), $template, 1);
	} else {
		$template = preg_insert_after('~<html[^>]*>~', PHP_EOL.implode(PHP_EOL, CMS::$R['CSS_list']), $template);
	}
	return $template;
}


/**
 * Adds a javascript link to the storage for the future flushing in the template
 *
 * @param string $link script href
 * @param string $prefix string to prepend every link. See "add_CSS" comments for details.
 */
function add_JS($link, $prefix = '') {
	// force slash
	if ($prefix > '') {
		$prefix = rtrim($prefix, '/').'/';
	}

	// make'em similar
	if (is_string($link)) {
		$link = array($link);
	}

	// push to the storage
	foreach($link as $href) {
		if ($href > '') {
			array_push(CMS::$R['JS_list'], '<script src="'.$prefix.$href.'" type="text/javascript"></script>');
			Logger::instance()->log('JS added: '.$href, Logger::LOG_LEVEL_DEBUG);
		}
	}
}


/**
 * Flushes pre-created JS array into the template
 *
 * @param string $template
 * @return string modified template
 */
function flush_JS($template) {
	if (preg_match('~<head[^>]*>~smui', $template, $match)) {
		$template = preg_replace('~<head[^>]*>~smui', $match[0].PHP_EOL.implode(PHP_EOL, CMS::$R['JS_list']), $template, 1);
	} else {
		$template = preg_insert_after('~<html[^>]*>~', PHP_EOL.implode(PHP_EOL, CMS::$R['JS_list']), $template);
	}
	return $template;
}


/**
 * Adds a meta to the queue
 *
 * if $attr is empty, "name" is used by default
 *
 *
 * @param string $template HTML page
 * @param string $attr meta attribute
 * @param string $value attribute value
 * @param string $content meta content, will be transformed into 'content="something"'
 * @return modified HTML
 */
function add_meta($attr, $value, $content = '') {
	if (trim($value) > '') {
		$add = '<meta '.($attr ? $attr : 'name').'="'.$value.'" '.($content>''? 'content="'.$content.'" ' : '').'/>';
		CMS::$R['meta_list'][] = $add;
	}
}

/**
 * Flushes meta queue
 */
function flush_meta($template) {
	foreach (CMS::$R['meta_list'] as $meta) {
		$template = str_insert_after('<head>', $meta.PHP_EOL, $template);
	}
	return $template;
}

/**
 * Prepares a service message to be added to a page
 *
 * When page is loaded, message elements will be shown as popups
 *
 * @param string $message anything to store into the box
 * @param string $type popup type (warning, info, error etc.)
 */
function popup_message_add($message, $type = JCMS_MESSAGE_INFO) {
	array_push(CMS::$R['J_CMS_messages'], array('message'=>$message, 'type'=>$type));
}


/**
 * Converts pre-stored messages to special div's (invisible, JS will display them later)
 *
 * Note that NO CHECK performed on parameters, so use tags carefully
 *
 * @param $string current page template
 * @return string modified template
 */
function popup_messages_to_template($template) {

	$classes = array(
		JCMS_MESSAGE_INFO    => 'popup-info',
		JCMS_MESSAGE_OK      => 'popup-ok',
		JCMS_MESSAGE_WARNING => 'popup-warning',
		JCMS_MESSAGE_ERROR   => 'popup-error',
		JCMS_MESSAGE_FATAL   => 'popup-fatal'
	);

	// first, get older popups stored at $_SESSION
	$all_popups = array_merge(get_array_value($_SESSION, 'popups', array()), CMS::Get('J_CMS_messages', 'array'));

	// prevent popups to be displayed one again
	$_SESSION['popups'] = array();

	foreach ($all_popups as $message) {
		$html = '<div style="display: none;" class="popup-message '.$classes[$message['type']].'">'.htmlspecialchars($message['message']).'</div>';
		$template = str_insert_before('</body>', $html, $template);
	}

	// clean queue to prevent forwarding its content to $_SESSION at terminate()
	CMS::$R['J_CMS_messages'] = array();

	return $template;
}


/**
 * Replaces entire HTML body with given content
 *
 * @param string $template HTML page
 * @param string $content HTML to replace with
 * @return modified HTML
 */
function content_replace_body($template, $content) {
	return preg_replace('~<body(.*?)>.*</body>~smui', '<body$1>'.$content.'</body>', $template, 1);
}

/**
 * Replaces HTML header title with given content
 *
 * @param string $template HTML page
 * @param string $content title to replace with
 * @return modified HTML
 */
function content_replace_title($template, $content) {
	// note that here tag is lazy
	return preg_replace('~<title(.*?)>.*?</title>~smui', '<title$1>'.$content.'</title>', $template, 1);
}


/**
 * parser string like <macro module="menu" id="1" type="standard" /> into separate params
 *
 * TAG_BUG: will replace title inside body if no real title exists (really invalid markup)
 *
 * @param string $str string to parse
 * @return array parsed data
 */
function parse_plugin_template($str) {
	$result = array();

	// разберем на атомы, если где-то есть знак "равно", это для нас
	preg_match_all('~\s([a-zA-Z\-_]+)="([^"]*)"~', $str, $params);
	if (count($params[1]) > 0) {
		$result = array_combine($params[1], $params[2]);
	}
	return $result;
}

/**
 * logger wrapper - output
 *
 * @param int $level minimal event level display, see logger.php for possible values
 * @param array $options output level to override initial
 */
function logger_out($level = Logger::LOG_LEVEL_MESSAGE, $options = array()) {
	return Logger::singleton()->flushAll($level, $options);
}

/**
 * Converts array to XML-node. Helper function for array_to_xml
 *
 * @param DOMNode $node XML node to append data
 * @param array $array data to add
 * @param $tags tag list for numeric indexes
 */
function array_to_node($node, $array, $tags) {

	// tagname to use in any bad indexes
	$tag_name = array_shift($tags);

	// first, check if we'll use tag from input array or explicitly set with $tags
	$use_explicit_tag = false;
	foreach ($array as $index=>$value) {
		if (is_numeric($index) || !preg_match('~^[a-zA-Z_][a-zA-Z0-9_\-]*$~', $index)) {
			$use_explicit_tag = true;
			break;
		}
	}

	// check if any tags left to use
	if ($use_explicit_tag && is_null($tag_name)) {
		trigger_error('array_to_node: no tags left but bad index arrived', E_USER_ERROR);
		return false;
	}

	// now add items
	foreach ($array as $index=>$value) {

		$new_node = $node->ownerDocument->createElement($use_explicit_tag ? $tag_name : $index);
		if (is_array($value)) {
			array_to_node($new_node, $value, $tags);
		} else {
			$new_node->nodeValue = $value;
		}
		$node->appendChild($new_node);
	}
	return false;
}

/**
 * Converts array to XML
 *
 * @param array $array array to convert
 * @param mixed $tags tag names to use instead numeric indexes. first tag will be used as
 *                    root, second as first level sub-nodes, and so on.
 *                    required if array contains numeric indexes.
 */
function array_to_xml($array, $tags) {

	$xml = new DOMDOcument('1.0', 'utf-8');

	// get the first element of tags array, to be a root node name
	if (is_array($tags)) {
		$root_tag = array_shift($tags);
	} else {
		$root_tag = $tags;
	}

	// get very first element if multi-array came
	while (is_array($root_tag)) {
		$root_tag = array_shift($root_tag);
	}

	// checkout
	if (!preg_match('~^[a-zA-Z_][a-zA-Z0-9_\-]*$~', $root_tag)) {
		return false;
	}

	// make XML!
	$xml->appendChild($root_node = $xml->createElement($root_tag));

	// simple case
	if (!is_array($array)) {
		$root_node->nodeValue = $array;
		return $xml;
	}

	// normal state
	array_to_node($root_node, $array, $tags);
	return $xml;

}

/**
 * XSL tranform wrapper
 *
 * @param string $xml source data to transform or filename to load from
 * @param string $xslt transformation XML or filename to load from
 * @param bool $xml_is_string true if $xml is XML data (default), false if filename
 * @param bool $xsl_is_string true if $xslt is XML data, false (default) if filename
 */
function XSLTransform($xml_source, $xsl_source, $xml_is_string = true, $xsl_is_string = false) {

	// load source stylesheet
	$xml_doc = new DOMDocument('1.0', 'utf-8');

	$xml_content = $xml_is_string ? $xml_source : file_get_contents($xml_source);
	
	// prepare a bit
	$xml_content = str_replace(
		array('&mdash;', '&nbsp;'),
		array('&#8212;', '&#160;'),
		$xml_content
	);
	$loaded_ok = $xml_doc->loadXML($xml_content);
	if (!$loaded_ok) {
		trigger_error('Loading XML ' . (!$isString ? 'from file "' . $xmlSource . '" ' : '') . ' failed!', E_USER_ERROR);
	}

	// load transformer stylesheet
	$xsl_doc = new DOMDocument('1.0', 'utf-8');

	$loaded_ok = $xsl_is_string ? $xsl_doc->loadXML($xsl_source) : $xsl_doc->load($xsl_source);
	if (!$loaded_ok) {
		trigger_error('Loading XSL ' . (!$isString ? 'from file "' . $xmlSource . '" ' : '') . ' failed!', E_USER_ERROR);
	}

	// transformer instance
	$processor = new XSLTProcessor();
	if (!($processor->importStylesheet($xsl_doc))) {
		trigger_error('Importing stylesheet has failed Failed!', E_USER_ERROR);
	}

	// transform and return
	return $processor->transformToXml($xml_doc);
}


/**
 * Script termination routine:
 *  send header if specified
 *  closes database connection
 *  pushed popups to the $_SESSION so they can be displayed after redirection
 *  write log
 *
 * @param string $text message to display as response body (not in the headers!)
 * @param string $http_code HTTP response to send. will automatically split into code and message,
 *	                        so just use something like "403 get out!". Will be ignored without code
 */
function terminate($text = '', $header = '', $code = '') {

	if (CMS::$lock_redirect) {
		return;
	}

	// push popups into session to display later
	$_SESSION['popups'] = array_merge(get_array_value($_SESSION, 'popups', array()), CMS::Get('J_CMS_messages', 'array'));

	// send header with optional HTTP code
	if ($code > '') {
		header($header, true, $code);
	} elseif ($header > '') {
		header($header);
	}

	// user-readable data
	if ($text > '') {
		echo $text;
	} elseif ($header > '') {
		echo '<h1>'.$header.'</h1>';
	}

	Logger::instance()->log('terminating: '.$text, Logger::LOG_LEVEL_DEBUG);
	exit;
}


/**
 * GET / POST filtering
 *
 * uses filter_input_array to filter GET and POST data
 * POST data with the same key will override GET one
 *
 * values that are in filter list but not in the input, become null
 * input params that don't match filter, become empty strings
 * non-null values are:
 *   * either match filter
 *   * or don't listed in the filter array AND full mode is on
 *
 * resulting array has keys in the following order:
 *   1) all filter keys as listed
 *   2) while full-get mode, all GET values not listed in filter
 *   3) while full-post mode, all POST values not listed in filter
 * if key exists both in GET and POST, it will appear among GET part BUT with POST value
 *
 *
 * @param array $filter filter data to pass to filter_input_array
 * @param array $sources what to filter. Array containing INPUT_GET or INPUT_POST or both.
 *
 * @return array filtered data
 */
function get_filtered_input($filter, $options = array(FILTER_GET_BY_LIST, FILTER_POST_BY_LIST)) {

	// check if both _GET and _POST have the same keys
	/* commented out as now it performed by CMS core
	if (count($test_intersect = array_intersect_key($_POST, $_GET)) > 0) {
		Logger::instance()->log('get_filtered_input: GET and POST have duplicate keys: '.print_r(array_keys($test_intersect), 1), Logger::LOG_LEVEL_WARNING);
	}
	*/

	// first, get all keys from filter list and fill new array with nulls (null means no input value for the key)
	$r = array_fill_keys(array_keys($filter), null);

	// if full options are used, merge _GET and _POST "as is". POST goes second as merging replaces same key values
	if (in_array(FILTER_GET_FULL, $options)) {
		$r = array_merge($r, $_GET);
	}
	if (in_array(FILTER_POST_FULL, $options)) {
		$r = array_merge($r, $_POST);
	}
	// now keys which are in filter list should be checked against their filters
	if ((in_array(FILTER_GET_BY_LIST, $options) || in_array(FILTER_GET_FULL, $options)) && ($t = filter_var_array($_GET,  $filter))) {
		foreach ($t as $index => $value) {
			// null means no input at all for the key, don't update it
			if (!is_null($value)) {
				$r[$index] = $value === false ? '' : $value;
			}
		}
	}

	// and filtered _POST
	if ((in_array(FILTER_POST_BY_LIST, $options) || in_array(FILTER_POST_FULL, $options)) && ($t = filter_var_array($_POST,  $filter))) {
		foreach ($t as $index => $value) {
			// it also helps to prevent GET part re-updating
			if (!is_null($value)) {
				$r[$index] = $value === false ? '' : $value;
			}
		}
	}
	return $r;
}


/**
 * creates yet another GUID
 *
 * @return string newly created GUID
 */
function create_guid() {
	if (function_exists('com_create_guid') === true) {
		return trim(com_create_guid(), '{}');
	}
	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


/**
 * checks if array contains value with the key given, then tests it against sample array or regexp.
 * returns:
 * 	the value if found and test passed
 * 	default value if key does not exist (FALSE by default)
 * 	false if element exists, but test fails
 *
 * @param array $array values array
 * @param string $key array key to find
 * @param mixed $default_value value to return if lookup or test failed
 * @param string|array $filter array of test values (value must exactly match one of them) or regexp to test against
 *
 * @return mixed|bool
 */
function get_array_value($array, $key, $default_value = false, $filter = null) {

	// return default if no such key at all
	if (!isset($array[$key])) {
		return $default_value;
	}

	$test_value = $array[$key];

	// if filter not set, return "as is"
	if (!isset($filter)) {
		return $test_value;
	}

	// if $filter is array, try to find value
	if (is_array($filter) && in_array($test_value, $filter)) {
		return $test_value;
	}

	// if $filter is a string, make regexp test
	if (is_string($filter) && preg_match($filter, $test_value)) {
		return $test_value;
	}

	// burp, bad value!
	return false;
}


/**
 * Get MIME type of the file specified. Really just a wrapper for finfo
 *
 * @param string $filename filename to get type of
 * @return string MIME file type
 */
function get_file_mime_type($filename) {
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime_type = finfo_file($finfo, $filename);
	finfo_close($finfo);
	return $mime_type;
}

/**
 * Sends a file to standard output, supplied with mime type
 *
 * @param string $filename
 */
function file_to_output($filename, $additional_headers = array()) {

	if (!file_exists($filename)) {
		header('HTTP/1.1 404 Not found');
		return false;
	}

	if (($current_buffer = ob_get_clean()) > '') {
		$current_buffer .= '<br />cannot send file: buffer not empty';
		terminate($current_buffer, 'Internal server error', 500);
	}

	header('HTTP/1.1 200 OK');
	header('Content-Type: '.get_file_mime_type($filename), true);
	header('Content-Transfer-Encoding: 8bit');
	foreach($additional_headers as $header) {
		header($header);
	}
	readfile($filename);
	return true;
}

/**
 * Returns filesystem encoding for cyrillic letters (windows-1251 on windows, utf-8 on modern *nix)
 *
 * @return string
 */
function filesystem_encoding() {
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		return 'windows-1251';
	}
	return 'utf-8';
}

/**
 * Returns all email addresses found in the string, comma-separated
 *
 */
function extract_email_addresses($src) {
	preg_match_all(REGEXP_EMAIL, $src, $matches);
	return implode(',', $matches[0]);
}

/**
 * Executes command at the background
 * tnx Arno van den Brink from PHP help contrib team
 */
function exec_in_background($cmd, $redirect = false) {
    if (substr(php_uname(), 0, 7) == 'Windows'){
        pclose(popen('start /B '. $cmd . ($redirect ? ' > '.$redirect : ''), 'r'));
    }
    else {
        exec($cmd . ' > ' .($redirect ?: '/dev/null') . ' &');
    }
}

/**
 * TAG_TODO: написать очень, очень подробный комментарий сюда
 *
 */
function send_email($mailer, $from, $to, $subject, $body, $headers = array(), $attachments = array()) {

	Logger::instance()->log('[send_email] : sending email "'.$subject.'" from "'.$from.'" to "'.$to.'"');

	// extract emails
	if (!preg_match('~[a-zA-Z0-9.\-_]+@[a-zA-Z0-9.\-_]+~', $to, $mail_addresses)) {
		Logger::instance()->log('[send_email] : no addresses found!', Logger::LOG_LEVEL_ERROR);
		return false;
	}

	// $to may contain such structure: Julia (julia@example.com). Round brackets should be replaced with angle brackets
	$to = preg_replace('~[\<\[\(]*([a-zA-Z0-9.\-_]+@[a-zA-Z0-9.\-_]+)[\>\]\)]*~', '<$1>', $to);

	// encoding data for mail_mime
	$encoding_parameters = array(
		'head_encoding' => 'base64',
		'text_encoding' => 'base64',
		'html_encoding' => 'base64',
		'head_charset'  => 'utf-8',
		'text_charset'  => 'utf-8',
		'html_charset'  => 'utf-8'
	);

	// add some important headers
	$headers_primary = array(
		'From'    => $from,
		'To'      => $to,
		'Subject' => $subject
	);
	$headers = array_merge($headers_primary, $headers);

	// create mail body generator
	$mime = new Mail_mime($encoding_parameters);

	// by default, no text part
	$mime->setTXTBody('');

	$alarm = 0;
	// replace image links with attached images
	if ($image_count = preg_match_all('~<img[^>]+src="(?!cid:)([^"]+)"[^>]*>~', $body, $img_data)) {
		for ($img_index = 0; $img_index < $image_count; $img_index++) {

			// generate new CID
			$cid = strtolower(str_replace('-', '', create_guid()));

			// image full CID, must contain sender domain to be displayed inline instead as attachment
			$cid_full = $cid.'@'.preg_replace('~[^@]*@~', '', $from);

			// add image
			$mime->addHTMLImage($img_data[1][$img_index], get_file_mime_type($img_data[1][$img_index]), '', true, $cid);

			// replace local image link to inline
			$new_image_link = str_replace($img_data[1][$img_index], 'cid:'.$cid_full, $img_data[0][$img_index]); // new image link
			$body = str_replace($img_data[0][$img_index], $new_image_link, $body);

		}
	}
	// ok, HTML part is ready now
	$mime->setHTMLBody($body);

	// add attachments
	foreach($attachments as $attachment) {
		$attachment_filename = $attachment['filename'];
		$attachment_realname = $attachment['realname'];
		$mime->addAttachment(
			$attachment_filename,                         // filename to get content from
			get_file_mime_type($attachment_filename),     // MIME-type
			$attachment_realname,                         // name to display
			true,                                         // yes, filename is really filename but not content
			'base64',                                     // transfer encoding to use for the file data
			'attachment',                                 // content-disposition of this file
			'',                                           // character set of attachment's content
			'',                                           // language of the attachment
			'',                                           // RFC 2557.4 location of the attachment
			'base64',                                     // encoding of the attachment's name in Content-Type
			'utf-8',                                      // encoding of the attachment's filename
			'',                                           // Content-Description header
			'utf-8'                                       // The character set of the headers e.g. filename
		);
	}

	// generate final headers
	$headers_ready = $mime->headers($headers);

	// get full message body
	$body_ready = $mime->get();

	// now send
	$mail_result = $mailer->send($mail_addresses, $headers_ready, $body_ready);

	// free mem as messages are big
	unset($mime);

	// log result
	if ($mail_result === true) {
		Logger::instance()->log('[send_email] : ok');
	} else {
		Logger::instance()->log('[send_email] : failed mailing to ' . $to.' : '.($mail_result->getMessage()), Logger::LOG_LEVEL_ERROR);
		trigger_error($mail_result->getMessage(), E_USER_WARNING);
	}

	return $mail_result;
}

/**
 * Creates XML structure with all modules which can respond to "p_id" parameter in _GET (such as content and menus in catalog mode)
 *
 * @param array $options XML options:
 *                       root (string) : root node name
 *                       use (array)   : modules to scan (items or "*")
 *                       skip (array)  : filter array with modules names to use
 * @return DOMDocument
 */
function aliasCatchersAsXML($options = array('root' => 'alias-catchers', 'use' => array('*'), 'skip' => array())) {

	$xml = new DOMDOcument('1.0', 'utf-8');
	$root_node_name = get_array_value($options, 'root', 'alias-catchers');
	$xml->appendChild($root_node = $xml->createElement($root_node_name));

	$skip_modules = get_array_value($options, 'skip', array());
	$use_modules = get_array_value($options, 'skip', array('*'));
	// content module
	if ((in_array('*', $use_modules) || in_array('content', $use_modules)) && !in_array('content', $skip_modules)) {
		if (
			($content_config_ok = module_get_config('content', $content_config))
			&& (($pages_table = get_array_value($content_config['config'], 'table', false, REGEXP_IDENTIFIER)) != false)
		) {
			$root_node->appendChild($module_node = $xml->createElement('module'))->setAttribute('name', 'Страницы');
			$query = CMS::$DB->query("select alias, title from `{$pages_table}` order by title");
			while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$module_node->appendChild($catcher_node = $xml->createElement('catcher'));
				$catcher_node->appendChild($xml->createElement('title'))->nodeValue = $row['title'];
				$catcher_node->appendChild($xml->createElement('alias'))->nodeValue = $row['alias'];
			}
		}
	}

	// menu module
	if ((in_array('*', $use_modules) || in_array('menu', $use_modules)) && !in_array('menu', $skip_modules)) {
		if (
			($menu_config_ok = module_get_config('menu', $menu_config))
		) {
			$root_node->appendChild($module_node = $xml->createElement('module'))->setAttribute('name', 'Каталоги');

			if (($menu_table = get_array_value($menu_config['config'], 'table_menu', false, REGEXP_IDENTIFIER)) != false) {
				$query = CMS::$DB->query("select alias, caption from `{$menu_table}` where alias > ''order by caption");
				while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
					$module_node->appendChild($catcher_node = $xml->createElement('catcher'));
					$catcher_node->appendChild($xml->createElement('title'))->nodeValue = $row['caption'];
					$catcher_node->appendChild($xml->createElement('alias'))->nodeValue = $row['alias'];
				}
			}
		}
	}
	return $xml;
}

/**
 * web-server replaces '+' with ' ' in _POST/_GET. One should make em back
 */
function base64_decode_despace( $str ) {
	return base64_decode( str_replace(' ', '+', $str));
	
}

?>