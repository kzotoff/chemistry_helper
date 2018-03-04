<?php //> Й <- UTF mark

require_once(__DIR__.'/html.php');

class J_Content extends JuliaCMSModule {

	/**
	 * template to cut out page contents. Must be greedy as tag may occur inside page contents.
	 *
	 * @const REGEXP_HTML_BODY
	 */
	const REGEXP_HTML_BODY = '~<body>(.*)</body>~smui';

	/**
	 *
	 */
	function requestParser($template) {
		
		$USERFILES_DIRS = CMS::$R['USERFILES_DIRS'];

		// в некоторых ситуациях требуется прервать дальнейшую обработку и перенаправить на админку снова
		$redirect_target = './?module=content&action=manage';
		$redirect_status = false;

		// фильтруем вход
		$input_filter = array(
			'id'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'alias'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'title'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9а-яА-Я\s\!\@\#\$\%\^\&\*\(\)\-\=\+\,\.\?\:\№"]+$~ui')),
			'meta'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~smui')),
			'filename'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.(html|php|xml)$~ui')),
			'css'         => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.css$~ui')),
			'js'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.js$~ui')),
			'generator'   => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_][a-zA-Z0-9\_]*$~ui')),
			'p_id'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'xsl'         => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.xslt?$~ui')),
			'pagecontent' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~smui')),
			'action'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'module'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui'))
		);

		$_INPUT = get_filtered_input($input_filter);

		// обновление информации о странице
		if ((@$_INPUT['module'] == 'content') && (@$_INPUT['action'] == 'update')) {
			if (!user_allowed_to_admin('manage pages')) {
				terminate('Forbidden', '', 403);
			}

			$sql_insert = 'insert into `'.$this->CONFIG['table'].'` (alias, title, filename, custom_css, custom_js, generator, xsl, meta) values (\'%2$s\', \'%3$s\', \'%4$s\', \'%5$s\', \'%6$s\', \'%7$s\', \'%8$s\', \'%9$s\')';
			$sql_update = 'update `'.$this->CONFIG['table'].'` set alias=\'%2$s\', title=\'%3$s\', filename=\'%4$s\', custom_css=\'%5$s\', custom_js=\'%6$s\', generator=\'%7$s\', xsl=\'%8$s\', meta=\'%9$s\' where id=%1$s';

			// проверим, нет ли такого алиаса с другим ID
			if (CMS::$DB->querySingle('select count(*) from `'.$this->CONFIG['table'].'` where alias=\''.$_INPUT['alias'].'\' and id<>\''.$_INPUT['id'].'\'') <> '0') {
				popup_message_add('Такой псевдоним уже существует', JCMS_MESSAGE_WARNING);
				return $template;
				//terminate('', 'Location: '.$redirect_target, 302);
			}
			
			// выбираем нужный шаблон SQL
			$sql = $_INPUT['id'] == -1 ? $sql_insert : $sql_update;

			// вытащим имеющиеся значения, на случай, если неправильный ввод породит пустое значение, а в базе уже непустое
			$q = CMS::$DB->query('select * from `'.$this->CONFIG['table'].'` where id='.$_INPUT['id']);
			if ($current = $q->fetch(PDO::FETCH_ASSOC)) {
				foreach($current as $index=>$value) {
					if (                                                   // заменяем входное значение на имеющееся, если:
						isset($_INPUT[$index]) &&                          // входное поле есть в базе
						($_INPUT[$index] == '') &&                         // И в отфильтрованном значении пусто
						(($_POST[$index] > '') || ($_GET[$index] > '')) && // И в запросе НЕ пусто
						($value > '')                                      // И в базе НЕ пусто
						) {
						$_INPUT[$index] = $value;
					}
				}
			}

			// готовим данные
			$filename = $_INPUT['filename'] > '' ? $_INPUT['filename'] : $_INPUT['alias'].'.html';

			// добавляем файлик с контентом, если страничка создается и странички еще нет
			if (($_INPUT['id'] == '-1') && !file_exists($USERFILES_DIRS['pages']['dir'].$filename)) {
				switch (pathinfo($filename, PATHINFO_EXTENSION)) {
					case 'php':
						// проверим generator, если пустой - придумаем
						if ($_INPUT['generator'] == '') {
							$_INPUT['generator'] = 'get_page_content_'.$_INPUT['alias'];
						}
						$new_content = sprintf(MODULE_CONTENT_NEW_PHP_PAGE, $_INPUT['generator']);
						break;
					case 'xml':
						$new_content = '<root>New page</root>';
						break;
					default:
						$new_content = 'New page';
						break;
				}
				file_put_contents($USERFILES_DIRS['pages']['dir'].$filename, $new_content);
			}

			// готовим и засылаем запрос
			// (!) порядок следования аргументов всегда фиксированный: id, alias, title, filename, custom_css, custom_js, generator, xsl, meta
			// (!) должно следовать после куска с записью файла, потому что там задается generator по умолчанию
			$sql = sprintf($sql, $_INPUT['id'], $_INPUT['alias'], $_INPUT['title'], $filename, $_INPUT['css'], $_INPUT['js'], $_INPUT['generator'], $_INPUT['xsl'], $_INPUT['meta']);
			CMS::$DB->query($sql);

			$redirect_status = true;
		}

		// удаление страницы
		if ((@$_INPUT['module'] == 'content') && (@$_INPUT['action'] == 'delete')) {
			if (!user_allowed_to_admin('manage pages')) {
				terminate('Forbidden', '', 403);
			}

			// get filename
			$filename = CMS::$DB->querySingle('select filename from `'.$this->CONFIG['table'].'` where id='.$_INPUT['id']);

			// удаляем запись
			CMS::$DB->query('delete from `'.$this->CONFIG['table'].'` where id='.$_INPUT['id']);

			// перемещаем файлик в помойку, предварительно проверим наличие помойки
			if (!file_exists($USERFILES_DIRS['trash']['dir'])) {
				mkdir($USERFILES_DIRS['trash']['dir']);
			}
			rename($USERFILES_DIRS['pages']['dir'].$filename, $USERFILES_DIRS['trash']['dir'].$filename);
			$redirect_status = true;
		}

		// обработка сохранения страницы
		if ((@$_INPUT['module'] == 'content') && (@$_INPUT['action'] == 'savepage')) {
			if (!user_allowed_to_admin('edit pages')) {
				terminate('Forbidden', '', 403);
			}

			$try_content = $_INPUT['pagecontent'];

			// check for <p>[macro /]</p> entries (tinyMCE likes to wrap anything with it)
			if ($this->CONFIG['cut_paragraphs']) {
				$try_content = preg_replace('~<p>(\[[^\]]+\])</p>~', '$1', $try_content);
				$try_content = preg_replace('~<p>(\<[^\>]+\>)</p>~', '$1', $try_content);
			}
			$page_id = $_INPUT['p_id'];

			$q = CMS::$DB->query('select * from `'.$this->CONFIG['table'].'` where alias=\''.$page_id.'\'');
			if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
				file_put_contents($USERFILES_DIRS['pages']['dir'].$row['filename'], $try_content);
			}
			
			// при сохранении тоже надо делать редирект, хоть и на самого себя - чтобы post не делался повторно по F5
			$redirect_target = './'.$page_id;
			$redirect_status = true;
		}

		// редирект, если кто-то выше затребовал
		if ($redirect_status) {
			terminate('', 'Location: '.$redirect_target, 302);
		}

		return $template;
	}

	function contentGenerator($template) {

		$USERFILES_DIRS = CMS::$R['USERFILES_DIRS'];
	
		// если этот флажок есть, будет вызван редактор вместо отображения контента
		$edit_mode = isset($_GET['edit']);

		// идентификатор странички, которую надо вставить в шаблон. валидация не нужна - делается поиск в массиве

		// собираем список имеющихся страниц
		$pages = array();
		$query = CMS::$DB->query("select * from `{$this->CONFIG['table']}`");
		while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
			$pages[$row['alias']] = $row;
		}
		
		$page_id = isset($_GET['p_id']) ? $_GET['p_id'] : DEFAULT_PAGE_ALIAS;

		// ок, берем стандартную страницу, если таковая есть
		$page_found = false;
		if (isset($pages[$page_id])) {
			$page_found = true;
			$page_info = $pages[$page_id];
		} else {
			// если нужного идентификатора нет в страницах, посмотрим в меню, если там найдется - пускай сами разбираются
			if (module_get_config('menu', $menu_module_config) === true) {
				$query = CMS::$DB->query("select alias from `{$menu_module_config['config']['table_menu']}` where alias > ''");
				while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
					if ($page_id == $row['alias']) {
						$page_found = true;
						return $template;
					}
				}
			}
		}

		// если страница не найдена, попробуем использовать то, что указано в настройках для страницы 404,
		// если и там нету - тупо заменяем макрос на строку и вываливаемся
		if (!$page_found) {
			header('HTTP/1.1 404 Not found');
			if (isset($pages[$this->CONFIG['page_404']])) {
				$page_info = $pages[$this->CONFIG['page_404']];
			} else {
				$template = preg_replace(macro_regexp('content'), 'Страница не найдена', $template);
				return $template;
			}
		}

		// имя файла с контентом
		$content_filename =
			isset($page_info['filename']) && file_exists($USERFILES_DIRS['pages']['dir'].$page_info['filename'])
			? $USERFILES_DIRS['pages']['dir'].$page_info['filename']
			: $this->CONFIG['page_404'] ;
			
			
		// в режиме редактирования текст/xml не генерируем, а показываем в редакторе (textarea)
		if ($edit_mode && user_allowed_to_admin('edit pages')) {
			switch (pathinfo($page_info['filename'], PATHINFO_EXTENSION)) {
				case 'php':
				case 'xml':
					$pagehtml = sprintf(MODULE_CONTENT_TEXTAREA_WRAPPER_PHP, $page_id, @file_get_contents($content_filename));
					break;
				default:
				
					$pagecontent = @file_get_contents($content_filename);
					
					// при редактировании заменим макросы на защищенную версию, иначе следующие модули на них среагируют
					// и заменят на свой контент, что наи не нужно. ядро само заменит их обратно потом
					$pagecontent = str_replace('<macro', '<protected-macro', $pagecontent);
					$pagecontent = str_replace('[macro', '[protected-macro', $pagecontent);
					$pagecontent = str_replace('</macro', '</protected-macro', $pagecontent);
					$pagecontent = str_replace('[/macro', '[/protected-macro', $pagecontent);
					$pagehtml = sprintf(MODULE_CONTENT_TEXTAREA_WRAPPER, $page_id, $pagecontent);
					break;
			}
		} else {
			// если страница php, инклюдим и вызываем юзер-функцию, иначе тащим как есть
			if (pathinfo($content_filename, PATHINFO_EXTENSION) == 'php') {
				include_once($content_filename);
				$pagehtml = call_user_func($page_info['generator']);
			} else {
				(($pagehtml = file_get_contents($content_filename)) !== false) or ($pagehtml = 'error reading page content (code CONTENT/001)');
			}
			// если указан транфсорматор - трансформируем
			if ($page_info['xsl'] > '') {
				$pagehtml = XSLTransform($pagehtml, $USERFILES_DIRS['xsl']['dir'].$page_info['xsl']);				
			}
		}

		// если есть BODY, берем его внутреннее содержимое, иначе весь файл целиком
		if (preg_match(self::REGEXP_HTML_BODY, $pagehtml, $page_body) > 0) {
			$replace = $page_body[1];
		} else {
			$replace = $pagehtml;
		}
		if (isset($_GET['print'])) {
			$template = str_replace('%content%', $replace, MODULE_CONTENT_PRINT_FORM);
		} else {
			$template = preg_replace(macro_regexp('content'), $replace, $template);
		}

		// userland functions
		while (preg_match(macro_regexp('user-function'), $template, $match) > 0) {
			$params = parse_plugin_template($match[0]);
			if (is_callable($params['function'])) {
				$html = $params['function']($params);
			} else {
				$html = '[content] user function '.$params['function'].' does not exist';
			}
			$template = str_replace($match[0], $html, $template);
		}
		
		// мета в заголовке. если только буквы-цифры, делаем мету keywords
		if (preg_match('~^[a-zA-Zа-яА-Я0-9,.\-\s]+$~ui', $page_info['meta'], $match)) {
			add_meta('name', 'keywords', $match[0]);
		} elseif (preg_match_all('~(\(([a-zA-Z\-]*)\|([a-zA-Z\-0-9]+)\|([a-zA-Z\-0-9а-яА-Я.,;:\s+=!@#$%^&*\(\)]*)\))~smui', $page_info['meta'], $matches)) { // не прокатило, попробуем структуру со скобками и пайпами
			for ($i = 0; $i < count($matches[0]); $i++) {
				add_meta($matches[2][$i], $matches[3][$i], $matches[4][$i]);
			}
		} elseif (preg_match_all('~<meta\s[^>]+>~smui', $page_info['meta'], $matches)) { // проверим, возможно вписали сырые теги
			for ($i = 0; $i < count($matches[0]); $i++) {
				$template = str_insert_before('</head>', $matches[0][$i].PHP_EOL, $template);
			}
		}

		// заменяем залоговок страницы, если определен
		if (isset($page_info['title']) && (($replace = $page_info['title']) > '' )) {
			$template = preg_replace(macro_regexp('page_title'), $replace, $template, 1);
		}

		// кастомный CSS, если указан
		if (isset($page_info['custom_css']) && (($css = $page_info['custom_css']) > '' )) {
			add_CSS(CMS::$R['USERFILES_DIRS']['css']['dir'].$css);
		}

		// кастомный JS, если указан
		if (isset($page_info['custom_js']) && (($js = $page_info['custom_js']) > '' )) {
			add_JS(CMS::$R['USERFILES_DIRS']['js']['dir'].$js);
		}

		return $template;
	}

	function AJAXHandler() {

		// фильтруем вход
		$input_filter = array(
			'id'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui'))
		);
		$_INPUT = get_filtered_input($input_filter);

		// ответ по умолчанию
		$response = 'unknown function';

		switch ($_INPUT['action']) {

			// содержимое диалога редактирования/добавления ///////////////////////////////////////////
			case 'edit_elem':
				// элемент, который редактировать будем (-1, если новый)
				if (($elem_id = $_INPUT['id']) == '') {
					return 'bad ID';
				}
				$q = CMS::$DB->query('select * from `'.$this->CONFIG['table'].'` where id='.$elem_id);
				$row = $q->fetch(PDO::FETCH_ASSOC);
				$row['id'] = $elem_id; // set "-1" when creating new page as there comes empty array
				$xml = array_to_xml($row, array('page-edit-data'));
				$response = XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/edit.xsl');
				break;
		}

		return $response;
	}

	function adminGenerator() {
		if (!user_allowed_to_admin('manage pages')) {
			terminate('Forbidden', '', 403);
		}
		$q = CMS::$DB->query('select * from `'.$this->CONFIG['table'].'`');
		$data = $q->fetchAll(PDO::FETCH_ASSOC);
		foreach ($data as $index => $row) {
			$data[$index]['file_status'] = file_exists(CMS::$R['USERFILES_DIRS']['pages']['dir'].$row['filename']) ? 'OK' : 'файл отсутствует';
			$data[$index]['default-page'] = $data[$index]['alias'] == DEFAULT_PAGE_ALIAS ? 'yes' : 'no';		
		}
		$xml = array_to_xml($data, array('pages-list', 'page-data'));
//echo $xml->saveXML($xml->documentElement); exit;
		return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/list.xsl');
	}
}

?>