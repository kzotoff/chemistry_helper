<?php //> Й <- UTF mark

/**
 * News handling for JuliaCMS
 *
 * @package J_News
 */
class J_News extends JuliaCMSModule {

	/**
	 * Input parser 
	 *
	 */
	function requestParser($template) {

		if (($table = get_array_value($this->CONFIG, 'table', false, '~^[a-zA-Z_][a-zA-Z_0-9]*$~')) == false) {
			popup_message_add('[ NEWS ] table not defined or configuration error', JCMS_MESSAGE_ERROR);
			return $template;
		}

		$input_filter = array(
			'id'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^\-?[0-9]+$~')),
			'module'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'action'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'caption' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9\s\-_\\!@#$%^&*()=+.,:;"]+$~ui')),
			'summary' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9\s\-_\\!@#$%^&*()=+.,:;<>"/?]+$~ui')),
			'page'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'link'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z:.%а-яА-Я0-9]+$~')),
			'streams' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\-\s]+$~'))
		);
		$_INPUT = get_filtered_input($input_filter);

		// anyone called for me?
		if ((@$_INPUT['module'] != 'news') || !user_allowed_to_admin('manage news')) {
			return $template;
		}

		// redirect here again sometime
		$redirect_target = './?module=news&action=manage';
		$redirect_status = false;

		switch (@$_INPUT['action']) {
			case 'edit_item':

				// every minute we must know the way to go
				$insert_mode = $_INPUT['id'] < 0;
				
				$sql =
					$insert_mode
					? "insert into `{$table}` (stamp, caption, link, page, streams, summary) values (datetime('now'), :caption, :link, :page, :streams, :summary)"
					: "update `{$table}` set caption=:caption, link=:link, page=:page, streams=:streams, summary=:summary where id=:id"
				;
				$prepared = CMS::$DB->prepare($sql);

				// retrieve current values for the case of incorrect input
				$q = CMS::$DB->query("select stamp, caption, link, page, streams, summary from `{$table}` where id={$_INPUT['id']}");
				if ($current = $q->fetch(PDO::FETCH_ASSOC)) {
					foreach($current as $index=>$value) {
						if (                                                   // keep original value if :
							isset($_INPUT[$index]) &&                          // the field is really in the base
							($_INPUT[$index] == '') &&                         // AND filtered input is empty (wich means empty or incorrect input)
							(                                                  // AND non-filtered input is NOT empty (this surely means incorrect input)
								isset($_POST[$index]) && ($_POST[$index] > '') ||
								isset($_GET[$index])  && ($_GET[$index]  > '')
							) &&
							($value > '')                                      // AND already non-empty value in the base
						) {
							$_INPUT[$index] = $value;
						}
					}
				}

				// when inserting, if no link specified, get it from the page (don't remember the meaning of such action)
				if ($insert_mode && ($_INPUT['link'] == '')) {
					$_INPUT['link'] = $_INPUT['page'];
				}
				$query_params = array(
					'id'      => $_INPUT['id'],
					'caption' => $_INPUT['caption'], 
					'link'    => $_INPUT['link'], 
					'page'    => $_INPUT['page'], 
					'streams' => $_INPUT['streams'], 
					'summary' => $_INPUT['summary']
				);
				
				// don't need ID while inserting, remove it (PDO hates when parameter count doesn't match to SQl-defined)
				if ($insert_mode) {
					unset($query_params['id']);
				}
				
				// yeah save this
				$prepared->execute($query_params);

				$redirect_status = true;
				break;
			case 'delete':
				$sql = "delete from `{$table}` where id={$_INPUT['id']}";
				CMS::$DB->query($sql);
				$redirect_status = true;
				break;
		}

		if ($redirect_status) {
			terminate('', 'Location: '.$redirect_target, 302);
		}
		return $template;
	}

	/**
	 * Generates HTML for a news using database data and HTML template supplied
	 *
	 * Template should contain following placeholders:
	 * 		timestamp : for date/time mark
	 * 		header    : news suppary
	 * 		text      : detailed info
	 * 		link      : link to an our internal page of somewhere else. should have attrubute: data-meaning="primary-news-link"
	 *
	 * @param string $news_template single news template
	 * @param int $stream selects stream for output. Will be shown only news with this stream
	 * @param int $count limits count for output
	 * @return string HTML code
	 */
	function newsGenerate($news_template, $stream, $count = -1) {

		if (($table = get_array_value($this->CONFIG, 'table', false, '~^[a-zA-Z_][a-zA-Z_0-9]*$~')) == false) {
			popup_message_add('[ NEWS ] table not defined or configuration error', JCMS_MESSAGE_ERROR);
			return '';
		}
		
		$_html = '';

		// ok, get data and transform into cool HTML
		$limit = ($count >= 0 ? 'limit 0,'.$count : '');
		$q = CMS::$DB->query("select * from `{$table}` where ' '||streams||' ' like '% {$stream} %' or trim(streams) = '' order by stamp desc {$limit}");

		while ($row = $q->fetch(PDO::FETCH_ASSOC)) {

			// timestamp, yeah. SQLite hasn't special datetime format, inventing another bycicle
			$stamp = substr($row['stamp'], 8, 2).'.'.substr($row['stamp'], 5, 2).'.'.substr($row['stamp'], 0, 4);

			// header. nothing special
			$caption =$row['caption'];

			// summary
			$summary = $row['summary'];

			// link, try page if empty and page is set
			$link = $row['link'] ? $row['link'] : $row['page'];

			// remove link if still nowhere to point
			if ($link == '') {
				$copy = preg_replace('~<a[^>]*data-meaning="primary-news-link"[^>]*>(.*?)</a>~ui', '$1', $copy, 1);
			}

			// append to others
			$_html .= str_replace(
				array('%stamp%', '%caption%', '%text%', '%link%'),
				array($stamp   , $caption   , $summary, $link   ),
				$news_template
			);
		}
		return $_html;
	}

	/**
	 * Main content generator
	 *
	 */
	function contentGenerator($template) {

		// iterate all macros
		while (preg_match(macro_regexp('news'), $template, $match) > 0) {

			$menu_params = parse_plugin_template($match[0]);

			// determine template
			$template_name = get_array_value($menu_params, 'template', false, '~^[a-zA-Zа-яА-Я_][a-zA-Zа-яА-Я_0-9]*$~u') ?: 'default';
			
			$full_filename = __DIR__.'/templates/'.$template_name.'.html';
			if (!file_exists($full_filename)) {
				popup_message_add('[ NEWS ] template not found (name: '.get_array_value($menu_params, 'template', 'not set').', filename: '.$full_filename.')', JCMS_MESSAGE_ERROR);
				return $template;
			}

			// get news as HTML
			$str = $this->newsGenerate(file_get_contents($full_filename), get_array_value($menu_params, 'stream', 'default'), get_array_value($menu_params, 'count', -1));

			$template = str_replace($match[0], $str, $template);
		}

		return $template;
	}

	/**
	 * AJAX!
	 *
	 */
	function AJAXHandler() {

		$input_filter = array(
			'id'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui'))

		);
		$_INPUT = get_filtered_input($input_filter, array(FILTER_GET_BY_LIST));

		// default responce
		$response = 'unknown function';

		switch ($_INPUT['action']) {

			// add/edit dialog
			case 'edit_elem':
				if (!user_allowed_to_admin('manage news')) {
					terminate('Forbidden', '', 403);
				}

				// what to edit
				if ($_INPUT['id'] == '') {
					return 'bad ID';
				}
			
				module_init('menu');

				// get element description
				$q = CMS::$DB->query("select id, caption, link, page, streams, summary from `{$this->CONFIG['table']}` where id={$_INPUT['id']}");
				$row = $q->fetch(PDO::FETCH_ASSOC);
				$row['id'] = $_INPUT['id'];
				$xml = array_to_xml($row, array('news-edit-data'));
				
				// add pages list
				$xml->documentElement->appendChild(
					$xml->importNode( aliasCatchersAsXML(array('root'=>'page-list'))->documentElement, true)
				);
				return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/edit.xsl');
				break;
		}

		return $response;

	}

	/**
	 * Admin!
	 *
	 */
	function adminGenerator() {

		if (($table = get_array_value($this->CONFIG, 'table', false, '~^[a-zA-Z_][a-zA-Z_0-9]*$~')) == false) {
			popup_message_add('[ NEWS ] table not defined or configuration error', JCMS_MESSAGE_ERROR);
			return false;
		}
		
		// get all news
		$query = CMS::$DB->query("select stamp, id, caption, link, page, streams, summary from `{$table}`");
		
		if ($query == false) {
			popup_message_add('Query error: '.get_array_value(CMS::$DB->errorInfo(), 2), JCMS_MESSAGE_ERROR);
			return false;
		}

		// format all items at a time into XML and then transform to HTML
		$xml = array_to_xml( $query->fetchAll(PDO::FETCH_ASSOC), array('all-news-list', 'news'));
		return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/list.xsl');

	}
}

?>