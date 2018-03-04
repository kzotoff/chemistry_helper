<?php //> Й <- UTF mark

/**
 * Admin manager module for JuliaCMS
 *
 * @package J_Admin
 */
class J_Admin extends JuliaCMSModule {


	/**
	 * XQuery path to configurable part of config files
	 * @name $config_xml_path
	 */
	private $config_xml_path = '/JuliaCMS_module_definition/config';
	
	/**
	 * This string is used while performing get/set requests to access CMS settings instead of modules'
	 *
	 * @const CMS_SETTINGS_MODULE_PHANTOM
	 */
	const CMS_SETTINGS_MODULE_PHANTOM = 'cms_settings';
	
	/**	
	 *
	 */
	function contentGenerator($template) {

		if (!user_allowed_to_admin('manage site')) {
			return $template;
		}

		// create XML with admin panel description
		$xml = new DOMDocument('1.0', 'utf-8');

		$root_node = $xml->createElement('admin-buttons');
		$xml->appendChild($root_node);

		// detect active module_definition. no active module means some content is displayd
		$root_node->appendChild($xml->createElement('active-module'))->nodeValue = (isset($_GET['module']) ? $_GET['module'] : '');
		$root_node->appendChild($xml->createElement('cms-settings-phantom'))->nodeValue = self::CMS_SETTINGS_MODULE_PHANTOM;
		$root_node->appendChild($xml->createElement('active-page'))->nodeValue = (isset($_GET['p_id']) ? $_GET['p_id'] : '');
		$root_node->appendChild($xml->createElement('edit-mode'))->nodeValue = (isset($_GET['edit']) ? 'yes' : 'no');
		$root_node->appendChild($xml->createElement('show-config-link'))->nodeValue = empty($_GET['module']) || empty(CMS::$cache[$_GET['module']]['config']['config']) ? 'no' : 'yes';

		// get all modules' admin buttons, where exists
		foreach (CMS::$cache as $module_name => $module) {
			if ((in_array($module_name, CMS::$R['modules_apply_order'])) && isset($module['config']['admin_caption']) && ($module['config']['admin_caption'] > '')) {
				$root_node->appendChild($button_node = $xml->createElement('button'));
				$button_node->appendChild($xml->createElement('caption'))->nodeValue = $module['config']['admin_caption'];
				$button_node->appendChild($xml->createElement('module-name'))->nodeValue = $module_name;
			}
		}

		// if any module requests admin part, replace all the content with module's admin code and add CSS/JS
		// otherwise, display page editorial buttons // TAG_TODO move them to content module
		if (isset($_GET['module']) && isset(CMS::$cache[$_GET['module']]) && isset($_GET['action']) && ($_GET['action'] == 'manage')) {
			$module_name = $_GET['module'];
			
			$module = CMS::$cache[$module_name];

			// replace content
			$template = preg_replace('~<body(.*?)>.*</body>~smui', '<body$1><div class="admin-content">'.($module['object']->AdminGenerator()).'</div></body>', $template, 1);
			$template = preg_replace(macro_regexp('page_title'), 'администрирование: &quot;'.CMS::$cache[$_GET['module']]['config']['comment'].'&quot;', $template, 1);

			
			// remove user's CSS from template
			$template = preg_replace('~<link[^>]*rel="stylesheet"[^>]*href="(\./|)userfiles[^">]*"[^>]*>~', '', $template);
			$template = preg_replace('~<link[^>]*href="(\./|)userfiles[^">]*"[^>]*rel="stylesheet"[^>]*>~', '', $template);
			
			// also add module's admin CSSes and scripts
			add_CSS(get_array_value($module['config'], 'admin_css', array()), MODULES_DIR.$module_name.'/');
			add_JS(get_array_value($module['config'], 'admin_js', array()), MODULES_DIR.$module_name.'/');

		}

		// add button box to the template
		$admin_box_html = XSLTransform($xml->saveXML($root_node), __DIR__.'/admin_box.xsl');
		$template = preg_replace('~<body(.*?)>~', '<body$1>'.$admin_box_html, $template, 1);

		return $template;
	}
	
	/**
	 * Here generated data for module configuration dialog
	 *
	 */
	public function AJAXHandler() {
		
		if (!user_allowed_to_admin('manage modules')) {
			terminate('Forbidden', '', 403);
		}
		
		// фильтруем вход
		$input_filter = array(
			'target' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui')),
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui')),
			'value'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[\sa-zA-Zа-яА-Я0-9\_\-%!@$^*\(\)\[\]&=.,/\\\\:;]+$~ui')),
			'hash'   => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9]+$~ui')),
		);
		$_INPUT = get_filtered_input($input_filter);
		
		switch ($_INPUT['action']) {
			case 'get_settings':

				if (($module_name = $_INPUT['target']) == '') {
					terminate('Unknown module [from:admin]', '', 404);
				}
				// get config XML, mark nodes, transform and return
				$xml = new DOMDocument('1.0', 'utf-8');
				if ($module_name == self::CMS_SETTINGS_MODULE_PHANTOM) {
					$xml->loadXML($this->CMSSettingsXML());
				} else {
					$xml->load(get_module_config_filename($module_name));
				}
				$this->iterateAndMark($xml);
				return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/settings_box.xsl');
				break;

			case 'save_setting':
				
				if (($module_name = $_INPUT['target']) == '') {
					terminate('Unknown module [from:admin]', '', 404);
				}
				
				// first, get right XML
				$xml = new DOMDocument('1.0', 'utf-8');
				if ($module_name == self::CMS_SETTINGS_MODULE_PHANTOM) {
					$xml->loadXML($this->CMSSettingsXML());
				} else {
					$filename = get_module_config_filename($module_name);
					$xml->load($filename);
				}

				// traverse and find the node to change
				$config_xml_path = $this->config_xml_path;
				$found = false; // means that node found
				$this->iterateXMLFromNode($xml->documentElement, function($element) use (&$found, $_INPUT, $config_xml_path) {
					$node_path = $element->getNodePath();
					if ((md5($node_path) == $_INPUT['hash']) && (substr($node_path, 0, strlen($config_xml_path)) == $config_xml_path)) {
						$found = $element->nodeName;
						if ($element->getAttribute('type') == 'select') {
							$found = false;
							foreach ($element->childNodes as $childnode) {
								if ($childnode->nodeName == 'value') {
									$element = $childnode;
									$found = true;
									break;
								}
							}
						}
						$element->nodeValue = htmlspecialchars($_INPUT['value']);
						
					}
				});
				
				// if all OK, update file and return good
				if ($found) {
					if ($module_name == self::CMS_SETTINGS_MODULE_PHANTOM) {
						if (!($this->updateConstInFile('./userfiles/_data_common/conf.php', $found, $_INPUT['value']))) {
							terminate('Error updating file', '', 500);
						}
					} else {
						if (!($xml->save($filename))) {
							terminate('Error updating file', '', 500);
						}
					}
					return 'OK';
				} else {
					terminate('Config file changed', '', 403);
				}
				break;
				
			default:
				terminate('Unknown action [from: admin]', '', 404);
				break;
		}
	
		
		
	}
	
	/**
	 * Marks every DOM node with md5 of its root-relative path
	 */
	private function iterateAndMark($xml) {
		$path_start = $this->config_xml_path;
		$this->iterateXMLFromNode($xml->documentElement, function($element) use ($path_start) {
			$node_path = $element->getNodePath();
			
			// mark only nodes inside configurable part
			if (substr($node_path, 0, strlen($path_start)) == $path_start) {
				$element->setAttribute('data-path-hash', md5($node_path));
			}
		});
			
	}

	/**
	 * Traverses entire XML structure, checks for condition and performs operation
	 * 
	 * @param DOMDocument $xml
	 * @param callable $perform function to apply to every node
	 */
	private function iterateXMLFromNode($node, $perform) {
		$has_iterable_nodes = false;
		if ($node->hasChildNodes()) {
			for ($i = 0; $i < $node->childNodes->length; $i ++) {
				if ($node->childNodes->item($i)->nodeType == XML_ELEMENT_NODE) {
					$has_iterable_nodes = true;
					$this->iterateXMLFromNode($node->childNodes->item($i),  $perform);
				}
			}
		}
//		if (!$has_iterable_nodes) {
			$perform($node);
//		}
	}
	
	/**
	 * Generates XML for main CMS settings_box
	 *
	 * CMS settings stored in common .php file to improve performance at direct API requests,
	 * so using separate mechanism for configuration
	 */
	private function CMSSettingsXML() {
		$cms_config_xml = '<?xml version="1.0" encoding="utf-8"?>
			<JuliaCMS_module_definition>
				<version caption="Версия">'.CMS_VERSION.'</version>
				<config caption="Основные настройки CMS">
					<CMS_ADMIN_PASSWORD caption="Пароль администратора">'.CMS_ADMIN_PASSWORD.'</CMS_ADMIN_PASSWORD>
					<DEFAULT_PAGE_ALIAS caption="Страница по умолчанию">'.DEFAULT_PAGE_ALIAS.'</DEFAULT_PAGE_ALIAS>
				</config>
			</JuliaCMS_module_definition>
			';
		return $cms_config_xml;
	}
	
	/**
	 * replaces const definition in file (used for update main config file), constant must be single-string
	 *
	 * @param string $filename file to update
	 * @param string $const_name constant
	 * @param string $new_value
	 * @return bool true on success, false on fail
	 */
	private function updateConstInFile($filename, $const_name, $new_value) {
		$contents = file_get_contents($filename);

		// check if string exists
		$find_pattern = "~const\s+$const_name\s*=\s*.*;~ui";
		if (!preg_match($find_pattern, $contents)) {
			return false;
		}

		// ok, replace and update
		$contents = preg_replace($find_pattern, "const $const_name = '$new_value';", $contents);
		if (file_put_contents($filename, $contents) === false) {
			return false;
		}

		// all ok!
		return true;
	}
}

?>