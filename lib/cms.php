<?php


/**
 * Multipurpose toolset
 *
 * ArrayAccess is not used as it doesn't support multidimensional arrays
 *
 */
class CMS {
	
	/**
	 * Globale storage and some pre-defined elements
	 *
	 * @name $R
	 */
	public static $R = array(
		'CSS_list'       => array(),
		'JS_list'        => array(),
		'meta_list'      => array(),
		'J_CMS_messages' => array(),
	);

	/**
	 * Separate cache storage for modules' descriptions and objects
	 *
	 * @name $modules
	 */
	public static $cache = array();
	
	/**
	 * Flag to prevent redirection
	 *
	 * @name $lock_redirect
	 */
	public static $lock_redirect = false;

	
	/**
	 * Database connection
	 *
	 * @name $DB
	 */
	public static $DB;
	
	/**
	 * Key-checking storage array access, able to return desired type
	 *
	 * @param string $key array key to look up
	 * @param string $type type to convert result to
	 * @return mixed
	 */
	public static function Get($key, $type = '') {
		if (isset(self::$R[$key])) {
			$result = self::$R[$key];
		} else {
			$result = null;
		}
		if ($type > '') {
			settype($result, $type);
		}
		return $result;
	}

}