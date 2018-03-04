<?php //> Й <- UTF mark

/*

CMS configuration file.
modules has their own config files

*/


/**
 * module apply order.
 * modules will be processed in the order specified here.
 * every module can be used several times.
 * module must reside in the folder with the same name
 * 
 */
$modules_apply_order = array(
	'auth',
	'content',
	'menu',
	'admin',
//	'auth',
	'filemanager',
//	'redirect',
	'db',
	'feedback',
//	'search',
//	'news',
	'sms',
	'backup',
);

/**
 * just a CMS version
 * @const CMS_VERSION
 */
const CMS_VERSION = '1.0 beta';

/**
 * CMS admin password. Note that it is not depends with "auth" module
 *
 * @const CMS_ADMIN_PASSWORD
 */
const CMS_ADMIN_PASSWORD = '111';

/**
 * module directory. slash is required at the end.
 */
const MODULES_DIR = 'modules/';

/**
 * CMS DB storage path
 * modules may use this database or their own
 *
 * @const DB_PATH
 */
const DB_PATH = 'userfiles/_data_common/cms.sqlite';

/**
 * default page to display if nothing requested
 *
 * @const DEFAULT_PAGE_ALIAS
 */
const DEFAULT_PAGE_ALIAS = 'periodic_compact';

?>