<?php

/**
 * Converts XML from file, string or DOMNode to array
 * All XML nodes become string-indexed arrays, excluding 2 special cases:
 *    - node has attribute "type" and its value is "array"
 *    - node has 2 or more children with the same names
 *
 * @package StrangeXMLParser
 */
class StrangeXMLParser {
	
	/**
	 * Converts DOMNode to array
	 *
	 * @param mixed &$array_elem array element to append elements or null
	 * @param $node DOMNode XML node to parse
	 */
	private static function nodeToArray(&$array_elem, $node) {
		
		// this shows should we use string indexes or numeric (see class description)
		$numbered_indexes = false;
		
		// <node type="array"> forces numeric indexes
		if ($node->hasAttributes()) {
			if ($type_attr = $node->attributes->getNamedItem('type')) {
				if (strtoupper($type_attr->nodeValue) == 'ARRAY') {
					$numbered_indexes = true;
				}
			}
		}
		
		// traverse all child nodes
		$children = $node->childNodes;
		
		// first detect empty nodes
		if ($children->length == 0) {
			$array_elem = '';
		} else {
			for ($i = 0; $i < $children->length; $i++) {
				
				$child = $children->item($i);
				switch (get_class($children->item($i))) {
					
					// dom children cause current element will be array
					case 'DOMElement':
						if (!is_array($array_elem)) {
							$array_elem = array();
						}

						// if indexes are numeric, just add element to array and increase recursion
						if ($numbered_indexes) {
							self::nodeToArray($array_elem[], $child);				
						} else {
							// while string indexing, check if current index exists, switch to numeric indexes if yes
							if (isset($array_elem[$child->tagName])) {
								$numbered_indexes = true;                     // ok, change mode
								$array_elem = array_values($array_elem);      // clean existing indexes
								self::nodeToArray($array_elem[], $child);     // get deeper
							} else {
								self::nodeToArray($array_elem[$child->tagName], $child);				
							}
						}
						break;

					// DOMText will be element value but only if no children for the moment
					case 'DOMText':
						if (!is_array($array_elem)) {
							switch ($value = $child->nodeValue) {
								case 'true':
									$array_elem = true;
									break;
								case 'false':
									$array_elem = false;
									break;
								default:
									$array_elem = is_null($value) ? '' : $value;
									break;
							}
						}
						break;
					
				}
			}
		}
	}

	/**
	 * Converts XML from file to array
	 *
	 * @param string $filename file name to read XML from
	 * @return array
	 */
	public static function fromFile($filename) {
		$xml_string = file_get_contents($filename);
		return self::fromString($xml_string);
	}
	
	/**
	 * Converts XML from string to array
	 *
	 * @param string $xml_string string containing XML
	 * @return mixed false on error, array on success
	 */
	public static function fromString($xml_string) {
		$xml = new DOMDocument('1.0', 'utf-8');
		ob_start();
		$xml_parsed_ok = $xml->loadXML($xml_string);
		$result = ob_get_clean();
		
		if (!$xml_parsed_ok) {
			popup_message_add('oops!<br />'.$result, JCMS_MESSAGE_WARNING);
			return false;
		}
		return self::fromXML($xml);
	}
	
	/**
	 * Converts DOMNode to array
	 *
	 * @param string $xml_string string containing XML
	 * @return mixed false on error, array on success
	 */
	public static function fromXML($xml) {
		$array = array();
		self::nodeToArray($array, $xml);
		return $array;
	}

}

?>