<?php //> Й <- UTF mark

/**
 * Simple search engine for JuliaCMS
 *
 * @package J_Search
 */
class J_Search extends JuliaCMSModule {

	/**
	 * Char count to show around found patterns
	 *
	 * @name $chars_to_include;
	 */
	var $chars_to_include;

	/**
	 *
	 */
	public function requestParser($template) {

		if (!user_allowed_to_do('search')) {
			return $template;
		}

		if (!isset($_GET['module']) || ($_GET['module'] != 'search') || !isset($_GET['search'])) {
			return $template;
		}

		// generated HTML will be here
		// some init
		$this->chars_to_include = get_array_value($this->CONFIG, 'chars_to_include', 60);
		$wrap_tag = get_array_value($this->CONFIG, 'wrap_tag', 'b');

		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$root_node = $xml->createElement('search-results');
		$xml->appendChild($root_node);

		// parse search string
		$search_pattern = preg_replace('~[.,:;\(\)\-\\\/\'\"]+~', ' ', $_GET['search']);
		if (preg_match_all('~[^\s]{2,}~smui', $search_pattern, $matches) == 0) {
			$template = preg_replace(macro_regexp('content'), 'Некорректный запрос', $template);
			return $template;
		}
		$search = $matches[0];

		// enumarate all user pages if content module exists
		if (module_get_config('content', $content_module_config) === true) {

			$files = scandir('userfiles/pages/');
			foreach ($files as $file) {

				// skip some files (".", "..", .htaccess)
				if (substr($file, 0, 1) == '.') {
					continue;
				}

				// skip generator pages
				if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
					continue;
				}

				// ok, let's test this
				$content = file_get_contents('userfiles/pages/'.$file);

				if ($highlighted = $this->highlightPatternsItTheString($search, $content, $wrap_tag)) {
					// get title and link, skip if filename is not in the base (possibly means corrupted database)
					$query = CMS::$DB->query("select alias, title from `{$content_module_config['config']['table']}` where filename = '$file'");
					if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
						$root_node->appendChild($more_node = $xml->createElement('result'));
						$more_node->appendChild($xml->createElement('alias'))->nodeValue = $row['alias'];
						$more_node->appendChild($xml->createElement('title'))->nodeValue = $row['title'];
						$more_node->appendChild($xml->createElement('highlight'))->appendChild($xml->createCDATASection($highlighted));
					}
				}
			}
		}

		// also look up at news
		if (module_get_config('news', $news_module_config) === true) {

			$query = CMS::$DB->query("select * from `{$news_module_config['config']['table']}` order by stamp desc");
			while ($data = $query->fetch(PDO::FETCH_ASSOC)) {

				// ok, let's test this
				$content = $data['summary'];

				if ($highlighted = $this->highlightPatternsItTheString($search, $content, $wrap_tag)) {
					// get title and link, skip if filename is not in the base (possibly means corrupted database)
					$root_node->appendChild($more_node = $xml->createElement('result'));
					$more_node->appendChild($xml->createElement('alias'))->nodeValue = $data['page'];
					$more_node->appendChild($xml->createElement('title'))->nodeValue = $data['caption'];
					$more_node->appendChild($xml->createElement('highlight'))->appendChild($xml->createCDATASection($highlighted));
				}
			}



		}
		$root_node->appendChild($xml->createElement('pattern'))->nodeValue = implode($search, ' ');

		// final HTML
		$result = XSLTransform($xml->saveXML($root_node), __DIR__.'/output.xsl');

		// replace content with search results
		$template = preg_replace(macro_regexp('content'), $result, $template);

		// replace page title
		$template = preg_replace(macro_regexp('page_title'), 'Поиск: '.implode($search, ' '), $template);

		return $template;
	}

	/**
	 * Cuts and hightlights all patterns entries at the search haystack
	 *
	 * This function searches substrings in a big string, cuts some chars around entry
	 * and wraps the found sample in some tag
	 *
	 * @param array $patterns substring array to highlight
	 * @param string $string string to search in
	 * @param string $tag tag to wrap highlighted patterns. "b" is the default (will make <b></b> wraps)
	 *                    one may use classes and other attributes here
	 * @return mixed highlighted string if at least one pattern was found, false if none matched
	 */
	private function highlightPatternsItTheString($patterns, &$string, $tag) {

		// strip tags
		$string = preg_replace('~<.*?>~', ' ', $string);

		// remove double spaces, tabs and other garbage
		$string = preg_replace('~\s+~', ' ', $string);

		$highlight = '';
		$found = false;
		// check all search words, if found at least one, add to results and highlight
		foreach ($patterns as $search) {
			if (($pos = mb_stripos($string, $search)) !== false) {
				$found = true;
				$cut =
					'...'.
					mb_substr($string, max(0, $pos-$this->chars_to_include), min($pos, $this->chars_to_include)).
					'<'.$tag.'>'.
					mb_substr($string, $pos, mb_strlen($search)).
					'</'.array_shift(explode(' ', $tag)).'>'. // get only tag itself and skip possible class or other attributes
					mb_substr($string, $pos + mb_strlen($search), $this->chars_to_include).
					'...'
					;
				$highlight .= $cut;
			}
		}
		return $found ? $highlight : false;
	}





}

?>