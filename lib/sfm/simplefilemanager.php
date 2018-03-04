<?php

echo SimpleFileManager::doYourWork();

/**
 * @package SimpleFileManager
 * @version 0.5 devel
 *
 */

/******************************************************************************

TODO: ( marked with // TAG_TODO )
add check path regexp
directory sorting

NOTES:
here and below, "master" means the main application using this cool tool.

******************************************************************************/

class SimpleFileManager {
	
	/**
	 * Debug feature
	 */
	private static $__full_drive = false;
	
	/**
	 * master root location relative to calling file
	 * @const PATH_TO_ROOT_MASTER
	 */
	private static $PATH_TO_ROOT_MASTER = '../../';
	
	/**
	 * Encoding used in the file system. *nix systems are mainly UTF-8 while Windows is locale-specific
	 * @name $FILESYSTEM_ENCODING
	 */
	private static $FILESYSTEM_ENCODING;

	/**
	 * Path to SFM directory from root
	 * @name $SFM_PATH_FROM_ROOT
	 */
	private static $SFM_PATH_FROM_ROOT;

	/*
	 * Category configurations
	 * @name $CATEGORIES
	 */
	private static $CATEGORIES = array(
		'pictures' => array(
			'caption' => 'images',
			'root'    => 'userfiles/images/', // path from master root. SFM will not allow to get higher.
		),
		'files' => array(
			'caption' => 'files',
			'root'    => 'userfiles/files/',
		),
		'css' => array(
			'caption' => 'css',
			'root'    => 'userfiles/css/',
		),
		'xsl' => array(
			'caption' => 'xsl',
			'root'    => 'userfiles/_data_modules/menu/templates/',
		),
	);
	
	/**
	 * Master's root directory
	 * @name $master_root
	 */
	private static $MASTER_ROOT;

	/**
	 * Global input storage (merged $_GET and $_POST, modified if required
	 *
	 */
	private static $_INPUT;

	/**
	 * Some default input values
	 * @name $input_defaults
	 */
	private static $input_defaults = array(
		'action' => '',
		'cat'    => '',
	);

	/**
	 * Global entry point
	 */
	public static function doYourWork() {
		
		if (self::$__full_drive) {
			self::$PATH_TO_ROOT_MASTER = '../../../../../';
			self::$CATEGORIES = array('pictures' => array('caption' => 'FULL', 'root' => ''));
		}

		// some init
		self::$FILESYSTEM_ENCODING = isset($_SERVER['WINDIR']) ? 'windows-1251' : 'utf-8';

		// get all input
		self::$_INPUT = array_merge(self::$input_defaults, $_GET, $_POST);

		if (self::relocate_me($relocate_data)) {
			$new_path = $relocate_data['new_path'];
			$category = $relocate_data['category'];
		} else {
			self::terminate('', $relocate_data['message'], $relocate_data['code']);
		}

		switch(@$_GET['action']) {

			// directory file list
			case 'list':

				// get directory contents, sort, remove inappropriate items
				$dir = self::scandir_improved(getcwd());

				// get it out, at last
				$items = array();
				foreach($dir as $elem) {
					if (in_array($elem, array('.'))) {
						continue;
					}
					if (($elem == '..') && ($new_path == '')) {
						continue; 
					}

					// choose icon and direct image link if it is image
					$file_ext = pathinfo($elem, PATHINFO_EXTENSION);

					$icon = self::$SFM_PATH_FROM_ROOT.'icons/default.png';
					if ($elem == '..') {
						$icon = self::$SFM_PATH_FROM_ROOT.'images/up.png';
					} elseif (is_dir($elem)) {
						$icon = self::$SFM_PATH_FROM_ROOT.'images/folder.png';
					} elseif (file_exists(self::patch_path(self::$MASTER_ROOT).self::$SFM_PATH_FROM_ROOT.'icons/'.$file_ext.'.png')) {
						$icon = self::$SFM_PATH_FROM_ROOT.'icons/'.$file_ext.'.png';
					}
					
					$image = in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico')) ? self::patch_path($category['root']).$new_path.$elem : '';
					
					if (self::$__full_drive) {
						$image = str_replace('soft/www/ven/', '', $image);
						$icon = str_replace('soft/www/ven/', '', $icon);
					}
					
					$items[] = array(
						'path'    => self::patch_path($category['root']).$elem,
						'type'    => is_file($elem) ? 'file' : ($elem == '..' ? 'updir' : 'dir'),
						'image'   => $image,
						'icon'    => $icon,
						'caption' => iconv(self::$FILESYSTEM_ENCODING, 'utf-8', $elem),
					);
				}
				$result = array(
					'caption'      => $category['caption'],
					'currentPath'  => $new_path,
					'categoryRoot' => $category['root'],
					'items'        => $items
				);
				return json_encode($result);
				break;

			case 'mkdir':
				if (!isset(self::$_INPUT['name']) || !preg_match('~.+~', self::$_INPUT['name'])) { // TAG_TODO
					self::terminate('', 'bad directory name', 400);
				}
				$new_name = self::$_INPUT['name'];
				if (!mkdir($new_name)) {
					self::terminate('', 'error creating directory', 500);
				}
				return 'OK';
				break;

			case 'remove':
				if (!isset(self::$_INPUT['list']) || (self::$_INPUT['list'] == '')) {
					self::terminate('', 'nothing to delete', 200);
				}

				$list = explode(',', self::$_INPUT['list']);
				foreach ($list as $filename) {
					self::delete_file_recursive($filename, $result);
				}
				return count($result) == 0 ? 'OK' : implode('<br />', $result);
				break;

			case 'upload':			
				$result = array();
				foreach($_FILES['files']['name'] as $index => $filename) {
					if (!preg_match('~.+~', $filename)) {
						$result[] = 'bad filename: '.$filename;
					}
					if (!move_uploaded_file($_FILES['files']['tmp_name'][$index], $filename)) {
						$result[] = 'error receiving file: '.$filename;
					}
				}					
				return count($result) == 0 ? 'OK' : implode('<br />', $result);
				break;

			default:
				self::terminate('', 'you must specify an action', 400);
				break;
		}
	}

	/**
	 * Deletes file or directory (recursively) in the current folder.
	 * All errors occured will be stored in $result array, one per failure.
	 *
	 * @param string $filename file or directory name to delete
	 * @param array &$result failure reasons, if any
	 * @param string $now_here path from deletion start point to current directory (for messages)
	 * @return bool true on success, false otherwise
	 */
	private static function delete_file_recursive($filename, &$result, $now_here = '') {
		if (!isset($result) || !is_array($result)) {
			$result = array();
		}

		if (!preg_match('~.+~', $filename)) {
			$result[] = 'bad filename '.$filename;
			return false;
		}

		if (in_array($filename, array('.', '..'))) {
			return true;
		}

		if (is_dir($filename)) {
			if (!@chdir($filename)) {
				$result[] = 'error moving to '.$now_here.'/'.$filename.'/';
				return false;
			}
			$more_files = scandir('.');
			foreach ($more_files as $one_more_file) {
				self::delete_file_recursive($one_more_file, $result, $filename.'/');
			}
			chdir('..');
			if (!@rmdir($filename)) {
				$result[] = 'error deleting '.$now_here.$filename;
			}
		}
		if (is_file($filename)) {
			if (!@unlink($filename)) {
				$result[] = 'error deleting '.$now_here.$filename;
			}
		}
		return count($result) == 0;			
	}


	/**
	 * Improved version of scandir - returns sorted array with filenames, types and more info.
	 * "." entry also removed.
	 *
	 * @param string $dir directory to read
	 * @param array $options
	 * @param string $sort_by 'name', 'extension', 'modified', 'size'
	 * @param string $dort_dir sort direction
	 */
	private static function scandir_improved($directory, $options = array(), $sort_by = 'name', $sort_dir = 'asc') {

		$default_options = array(
			'remove_2_dots   ' => false,
			'return_full_info' => false, // return entire array ('all') or standard scandir-style 1-dimensional array
		);
		$options = array_merge($default_options, $options);
		// force slash
		$directory = rtrim($directory, '\\/').'/';

		// get file list, retrieve full informaion
		$dir = scandir($directory);

		$result = array();
		foreach ($dir as $filename) {

			// skip some files
			if ($dir == '.') {
				continue;
			}
			if (($dir == '..') && ($options['remove_2_dots'])) {
				continue;
			}

			// get info
			$full_path = $directory.$filename;
			$path_parts = pathinfo($filename);
			$more = array(
				'filename'  => $filename,
				'name'      => $path_parts['filename'],
				'extension' => isset($path_parts['extension']) ? $path_parts['extension'] : null,
				'size'      => @filesize($full_path),
				'modified'  => @filemtime($full_path),
				'accessed'  => @fileatime($full_path),
				'type'      => is_dir($full_path) ? 'dir' : 'file',
			);

			array_push($result, $more);
		}

		// now sort
		if ((count($result) > 0) && isset($result[0][$sort_by])) {
			usort($result, function($a, $b) use ($sort_by, $sort_dir) {

				// .. is alwaws the first
				if ($a['filename'] == '..') { return -1; }
				if ($b['filename'] == '..') { return 1; }

				// move directories forward
				if (($a['type'] == 'dir') && ($b['type'] != 'dir')) { return -1; }
				if (($a['type'] != 'dir') && ($b['type'] == 'dir')) { return 1; }

				$order = $sort_dir == 'asc' ? 1 : -1 ;
				if ($a[$sort_by] > $b[$sort_by]) { return $order; }
				if ($a[$sort_by] < $b[$sort_by]) { return -$order; }
				return 0;
			});
		}

		// cut names only, if requested
		if ($options['return_full_info'] == false) {
			$names = array();
			foreach ($result as $info) {
				array_push($names, $info['filename']);
			}
			$result = $names;
		}

		return $result;
	}

	/**
	 * Changes current dir to category-relative offset. Category and path are taken from $_GET or $_POST
	 *
	 * @param array &$results array to store some return values:
	 *                        - 'category' and 'new_path' directory on success
	 *                        - 'message' and 'code' on fail
	 * @return bool true on success, false otherwise
	 */
	private static function relocate_me(&$result) {

		// first, get categury (is a must)
		if (!isset(self::$_INPUT['cat']) || !isset(self::$CATEGORIES[self::$_INPUT['cat']])) {
			$result = array('message' => 'bad category', 'code' => 400);
			return false;
		}
		$category = self::$CATEGORIES[self::$_INPUT['cat']];

		// carefully parse suggested path
		if (!isset(self::$_INPUT['path']) || !preg_match('~.*~', self::$_INPUT['path'])) { // TAG_TODO
			$result = array('message' => 'bad path', 'code' => 400);
			return false;
		}

		// if not ended with slash, get dir name, otherwise use entire string
		$current_path = substr($_GET['path'], -1) == '/' ? self::$_INPUT['path'] : dirname(self::$_INPUT['path']);
		$current_path = self::patch_path($current_path);

		// how watch for my hands. WWe will relocate to the master's root now, then to category root, then to
		// the suggested path, and check if we're still within category root. If all ok,
		// the newly current dir is allowed to be used. So, let's go.

		// go to master root, also store find path from root to the SFM directory. It will help us later
		$sfm_start_dir = self::patch_path(getcwd());

		if (!@chdir(self::$PATH_TO_ROOT_MASTER)) {
			$result = array('message' => 'bad root path', 'code' => 500);
			return false;
		}
		self::$MASTER_ROOT = self::patch_path(getcwd());
		self::$SFM_PATH_FROM_ROOT = self::patch_path(substr($sfm_start_dir, strlen(self::$MASTER_ROOT))); // "+1" is for stripping slash

		// move to category root
		if (($category['root'] > '') && !@chdir(iconv('utf-8', self::$FILESYSTEM_ENCODING, self::patch_path($category['root'])))) {
			$result = array('message' => 'bad category path', 'code' => 500);
			return false;
		}
		// and to the suggested path
		if (($current_path > '') && !@chdir(iconv('utf-8', self::$FILESYSTEM_ENCODING, $current_path))) {
//			$result = array('message' => 'bad current path', 'code' => 403);
//			return false;
		}

		// ... and check
		$suggested_dir = self::patch_path(getcwd());
		$must_be_in = self::patch_path(self::$MASTER_ROOT).self::patch_path($category['root']);
		if (strpos($suggested_dir, $must_be_in) !== 0) {
			$result = array('message' => 'not allowed', 'code' => 403);
			return false;
		}

		// all ok, get it. this will be path relative to the category root
		$new_path = strlen($suggested_dir) == strlen($must_be_in) ? '' : substr($suggested_dir, strlen($must_be_in));

		$result = array('category' => $category, 'new_path' => iconv(self::$FILESYSTEM_ENCODING, 'utf-8', $new_path));
		return true;
	}

	/**
	 * Change all shashes to unix style (/) and force one at the end
	 *
	 * @param string $path path to make cool
	 * @return string
	 */
	public static function patch_path($path) {
		if (trim($path) == '') {
			return '';
		}
		$path = str_replace('\\', '/', $path);
		$path = rtrim($path, '/').'/';
		return $path;
	}

	/**
	 * Script termination routine
	 *
	 * @param string $text message to display as response body (not in the headers!)
	 * @param string $http_code HTTP response to send. will automatically split into code and message,
	 *	                        so just use something like "403 get out!". Will be ignored without code
	 */
	private static function terminate($text = '', $header = '', $code = false) {

		// send header with optional HTTP code
		if ($code) {
			header('HTTP/1.1 '.$code.' '.($header ?: '-'));
		} elseif ($header > '') {
			header($header);
		}

		// user-readable data
		echo $text ?: $header;

		// finish him!
		exit;
	}


}

?>