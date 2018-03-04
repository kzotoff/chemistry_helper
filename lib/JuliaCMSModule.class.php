<?php //> Й <- UTF mark

/**
 * base module class
 *
 * @package JuliaCMSModule
 */

/*************************************************************************************************************************

base module class
has 4 primary public functions that accessed from CMS engine:
	* request parser. main function - read _GET and _POST data and handle them if required
	* content generator. adds module-generated content into template page (or replaces entire page with own data)
	* ajax handler. called by engine if special parameter exists in the GET request.
	  All other modules are ignored in this case, request parser and content generator are also passed by
	* admin page content. In admin mode, engine will replace entire page content with admin-function generated content
	
class provides some common variables:
	* $R - global storage facility
	* $CONFIG - parsed module config from config.json (section "config")
	
базовый класс модуля
имеет 4 основных public функции, по которым к нему обращается основной движок:
	* обработчик запросов. Основная функция - обработать GET и POST и сделать какие-то действия
	* генератор содержимого. На вход подается страничка в текущем состоянии, функция как-то его меняет
	  и возвращает обратно в движок.
	* обработчик AJAX-запросов. Вызыывается движком при наличии специального параметра в GET-запросе.
	  В таком случае остальные модули игнорируются, обработчик запросов и генератор контента тоже пропускаются.
	* генератор админки. В режиме администрирования движок заменит весь код странички на то, что выдаст эта функция.

*************************************************************************************************************************/

class JuliaCMSModule {
	
	/**
	 * Module configuration (part of full definition)
	 * 
	 * @name $CONFIG
	 */
	public $CONFIG;
	
	/**
	 * Event notification observers
	 * !!! NOT IMPLEMENTED ANYWHERE !!!
	 *
	 * observers should be descripted with the following array:
	 *
	 * 'auth.update_users_table' => array(  // just some identifier, should be globally unique TAG_TODO сделать рспознавание дубликатов, в целях безопасности
	 * 	'module' => 'db',                   // module to observe
	 * 	'event'  => 'before-save',          // event to watch on (however, triggered by observed module)
	 * 	'call'   => 'testOne',              // function to call in oberving module
	 * ),
	 *
	 * @name $OBSERVERS
	 */
	public static $OBSERVERS;
	
	/**
	 * _GET and _POST handler
	 *
	 * @param string $template page HTML, modified or not with previous modules
	 * @return string $template new page HTML version
	 */
	public function requestParser($template) {

		return $template;
	}
	
	
	/**
	 * Primary module content generator. Provided with page HTML code,
	 * modifies it for own needs.
	 *
	 * @param string $template page HTML
	 * @return string new version
	 */
	public function contentGenerator($template) {
		
		return $template;		
	}
	
	/**
	 * AJAX calls handler
	 *
	 * @return string response body
	 */
	public function AJAXHandler() {
		
		return 'OK';
	}
	
	/**
	 * Admin page content generator
	 *
	 * @return string response body
	 */
	public function adminGenerator() {
		
		return '<div class="no_special_administration">*** тут ничего нет ***</div>';
	}

}

?>