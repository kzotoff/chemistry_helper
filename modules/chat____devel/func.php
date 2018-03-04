<?php //> Й <- UTF mark

/**
 * Chat module for JuliaCMS
 *
 * @package J_Chat
 */
class J_Chat extends JuliaCMSModule {

	/**
	 *
	 */
	public function requestParser($template) {

		if (!user_allowed_to_do('chat')) {
			return $template;
		}

		$input_filter = array(
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(post)+$~ui')),
		);

		$_INPUT = get_filtered_input($input_filter);
		return $template;
	}

	/**
	 *
	 */
	public function contentGenerator($template) {

		while (preg_match(macro_regexp('chat'), $template, $match) > 0) {
			
			// parse template parameters into array
			$params = parse_plugin_template($match[0]);

			// generate menu HTML
			$html = '<div class="j-chat"><div>loading...</div><form><input type="text" /></form></div>';

			// replace it
			$template = str_replace($match[0], $html, $template);
		}
		return $template;
	}

	/**
	 *
	 */
	public function AJAXHandler() {
		return 'YO!';
	}

	/**
	 *
	 */
	public function adminGenerator() {
		/*
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		$root = $xml->createElement('root');
		$xml->appendChild($root);
		$root->appendChild($xml->createElement('filename'))->nodeValue = iconv('windows-1251', 'utf-8', $filename);
		return XSLTransform($xml->saveXML($root), __DIR__.'/form.xsl');
		*/
		return 'YO!';
	}

}

?>