<?php //> Й <- UTF mark

/**
 * Feedback provider for JuliaCMS
 *
 * @package J_Feedback
 */

require_once('Mail.php');
require_once('Mail/mime.php');

class J_Feedback extends JuliaCMSModule {


	/**
	 * prepares string for proper displaying in e-mail - replaces EOLs with BRs and so on
	 *
	 * @param string $source string to be prepared
	 * @return string good string
	 */
	private function prepareToInsert($source) {

		// replace specials
		$source = htmlspecialchars($source);

		// replace EOLs with BRs
		$source = str_replace("\n", '<br />', $source);

		return $source;
	}

	/**
	 * Send email using configuration supplied
	 *
	 * @param array $data array with message details. array must contain at least "to", "subject" and "text" items
	 * @param array $config array with mail transport configuration. Array must contain 'type' element, which will
	 *                      define transport system: 'sendmail' or 'smtp' (case-insensitive). If transport is "smtp",
	 *                      array must also have "server", "host", "auth", "username", "password", and "localhost"
	 *                      items. Refer to PEAR::Mail documentation for items meaning for detailed items description.
	 */
	private function mail_send($data, $config, &$send_log) {

		switch (strtoupper($config['type'])) {
			case 'SENDMAIL':
				$headers =
					'From: '.$config['from'].PHP_EOL.
					'To: '.$data['to'].PHP_EOL.
					'Content-Type: text/html; charset=utf-8';

				return mail(
					$data['to'],
					get_array_value($data, 'subject', '* no subject*'),
					get_array_value($data, 'text', ''),
					$headers
				);
				break;
			case 'SMTP':

				// suppress massive strict notices as PEAR's mailer
				$current_error_reporting = error_reporting();

				// catch errors, get log
				error_reporting(E_ALL ^ E_STRICT);
				ob_start();

				// init mailer, yeah
				$params = array(
					'host'      => $config['server'],
					'port'      => $config['port'],
					'auth'      => $config['auth'],
					'username'  => $config['username'],
					'password'  => $config['password'],
					'localhost' => $config['localhost'],
					'debug'     => true
				);
				$mailer = &Mail::factory('smtp', $params);

				$headers = array(
					'From'         => $config['from'],
					'Subject'      => get_array_value($data, 'subject', '* no subject*'),
					'Content-Type' => 'text/html; charset=utf-8',
					'To'           => $data['to']
				);

				$result = $mailer->send($data['to'], $headers, $data['text']);

				error_reporting($current_error_reporting);
				$send_log = ob_get_clean();

				if ($result === true) {
					return true;
				} else {
					popup_message_add('Mail failed: '.$result->message, JCMS_MESSAGE_ERROR);
					return false;
				}
				break;

		}
		trigger_error('mail_send: no transport defined, message not sent', E_USER_WARNING);
		return false;

	}

	/**
	 * Just adds an empty file with the given name in the template directory
	 *
	 * @param string $filename
	 * @return bool true on success, false on fail
	 */
	private function templateAddEmpty($filename) {

		if ($filename == '') {
			popup_message_add('Некорректное название', JCMS_MESSAGE_ERROR);
			return false;
		}

		// force .html extension
		if (substr($filename, -5) != '.html') {
			$filename .= '.html';
		}		
			
		$full_filename = __DIR__.'/templates/'.iconv('utf-8', filesystem_encoding(), $filename);
		if (file_exists($full_filename)) {
			popup_message_add('Файл уже существует', JCMS_MESSAGE_WARNING);
			return false;
		}
		if (file_put_contents($full_filename, '') === false) {
			popup_message_add('Не удалось создать файл '.$filename, JCMS_MESSAGE_ERROR);
			return false;
		};
		
		popup_message_add('Создан пустой файл '.$filename, JCMS_MESSAGE_OK);
		return true;
	}

	/**
	 * deletes template with the given name from the template directory
	 *
	 * @param string $filename
	 * @return bool true on success, false on fail
	 */
	private function templateDelete($filename) {
		$full_filename = __DIR__.'/templates/'.iconv('utf-8', filesystem_encoding(), $filename);
		if (!file_exists($full_filename) || !is_file($full_filename)) {
			popup_message_add('Файл не существует', JCMS_MESSAGE_ERROR);
			return false;
		}
		
		if (!unlink($full_filename)) {
			popup_message_add('Не удалось удалить файл', JCMS_MESSAGE_ERROR);
			return false;
		}
		
		popup_message_add('Файл удален', JCMS_MESSAGE_OK);
		return true;
	}

	/**
	 * updates template with the given name from the template directory
	 *
	 * @param string $filename
	 * @param string $content data to put in
	 * @return bool true on success, false on fail
	 */
	private function templateUpdate($filename, $content) {
		if ($filename == '') {
			popup_message_add('Некорректное название', JCMS_MESSAGE_ERROR);
			return false;
		}

		$full_filename = __DIR__.'/templates/'.iconv('utf-8', filesystem_encoding(), $filename);
		if (file_put_contents($full_filename, $content) === false) {
			popup_message_add('Ошибка при сохранении шаблона', JCMS_MESSAGE_ERROR);
			return false;
		}
		
		popup_message_add('Шаблон сохранен', JCMS_MESSAGE_OK);
		return true;	
	}

	/**
	 *
	 */
	function requestParser($template) {

		// some speed improvement
		if ((@$_POST['module'] != 'feedback') && (@$_GET['module'] != 'feedback')) {
			return $template;
		}

		$input_filter = array(
			'action'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-z_]+$~ui')),
			'filename'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9][a-zA-Zа-яА-Я0-9_\-\s]*(\.html|)$~ui')),
			'filecontent' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~smui')),
			'template'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_IDENTIFIER)),
			'module'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^feedback$~ui'))
		);
		$_INPUT = get_filtered_input($input_filter);
		
		// another check as both POST and GET may contain "module" parameter but with different values
		if ($_INPUT['module'] != 'feedback') {
			return $template;
		}
		
		switch ($_INPUT['action']) {
			case 'add_template':
				if (!user_allowed_to_admin('manage feedback templates')) {
					return $template;
				}
				if ($this->templateAddEmpty($_INPUT['filename'])) {
					terminate('', 'Location: ./?module=feedback&action=manage', 302);
				}
				break;
			case 'delete_template':
				if (!user_allowed_to_admin('manage feedback templates')) {
					return $template;
				}
				if ($this->templateDelete($_INPUT['filename'])) {
					terminate('', 'Location: ./?module=feedback&action=manage', 302);
				}
				break;
			case 'update_template':
				if (!user_allowed_to_admin('manage feedback templates')) {
					return $template;
				}
				if ($this->templateUpdate($_INPUT['filename'], $_INPUT['filecontent'])) {
					terminate('', 'Location: ./?module=feedback&action=manage', 302);
				}
				break;
			
			case 'send':

				// TAG_TODO вынести это в отдельный метод (или вообще использовать lib/common/...
				// get the first of: message template, failure template, hardcoded failure message
				if (($text = @file_get_contents(__DIR__.'/templates/'.$_INPUT['template'].'.html')) == '') {
					Logger::instance()->log('[feedback] template "'.$_INPUT['template'].'" specified, file not exists', Logger::LOG_LEVEL_ERROR);
					if (file_exists($filename_fail_message = __DIR__.'/templates/_error_detected.html')) {
						$text = file_get_contents($filename_fail_message);
					} else {
						$text = 'error reading message template ("'.$_INPUT['template'].'"), code FEEDBACK/001)'.PHP_EOL.PHP_EOL.'POST details:'.PHP_EOL.'%POST%';
						popup_message_add($text);
					}
					terminate('', 'Location: ./service_feedback_failed', 302);
				}

				// replace templates
				foreach ($_POST as $index => $value) {
					$text = str_replace('%'.$index.'%', $this->prepareToInsert($value), $text);
				}

				// also add POST and referer (may be useful on error handling)
				$text = str_replace('%POST%', '<pre>'.$this->prepareToInsert(print_r($_POST, 1)).'</pre>', $text);
				$text = str_replace('%REFERER%', '<pre>'.$this->prepareToInsert($_SERVER['HTTP_REFERER']).'</pre>', $text);

				// remove unused templates (note on lazy regexp)
				$text = preg_replace('~%.*?%~', '', $text);

				// try to use template <title> as message subject
				if (preg_match('~<title>(.*?)</title>~smui', $text, $match)) {
					$subject = $match[1];
				} else {
					$subject = $this->CONFIG['default_subject'];
				}

				// determine addressee - from config by index or first if not found
				$feedback_addresses = $this->CONFIG['addresses'];
				$recipient = isset($_POST['recipient']) && isset($feedback_addresses[$_POST['recipient']]) ? $feedback_addresses[$_POST['recipient']] : array_shift($feedback_addresses);

				// send message
				$result = $this->mail_send(
					array(
						'to'      => $recipient,
						'subject' => $subject,
						'text'    => $text
					),
					$this->CONFIG['transport'],
					$send_log
				);

				// add debug dialog if requested
				if ($this->CONFIG['transport']['debug']) {
					$template = str_insert_before('</body>', '<div class="debug-dialog">'.$send_log.'</div>', $template);
					CMS::$lock_redirect = true;
				}
				if ($result) {
					terminate('', 'Location: ./service_feedback_ok', 302);
				} else {
					terminate('', 'Location: ./service_feedback_failed', 302);
				}
				break;
		}
		return $template;
	}

	/**
	 * Places feedback form from the file
	 *
	 * Macro parameters available:
	 *  "form"     : specifies a file to get a form from (no extension, will be added automatically, "default" by default)
	 *  "target"   : get recipient from the config list (first item by default)
	 *  "template" : forces to add "template" hidden input to the form causing sender to use alternate email template
	 *
	 * @param string $template source template
	 * @return string
	 */
	function contentGenerator($template) {

		// look for macro
		while (preg_match(macro_regexp('feedback'), $template, $match) > 0) {

			$params = parse_plugin_template($match[0]);

			// now get form HTML. if no source found specified, try to use "default.html". Malformed values always generate an error
			if ($filename = get_array_value($params, 'form', 'default', REGEXP_IDENTIFIER)) {
				if (file_exists(__DIR__.'/forms/'.$filename.'.html')) {
					$form = file_get_contents(__DIR__.'/forms/'.$filename.'.html');
				} else {
					$form = '<b>[JuliaCMS][feedback] error:</b> form file &quot;'.$filename.'.html&quot; not found';
				}
			} else {
				$form = '<b>[JuliaCMS][feedback] error:</b> bad form name &quot;'.$params['form'].'&quot;';
			}

			// let's determine form target (source form's one will be deleted automatically)
			$target = get_array_value($params, 'target', false);
			$address_keys = array_keys($this->CONFIG['addresses']);
			$recipient = isset($this->CONFIG['addresses'][$target]) ? $target : array_shift($address_keys);

			// ok, implant recipient field into a form (first, cut existing if any)
			$form = preg_replace('~<input\s[^>]*?name="recipient"[^/>]*/?>~', '', $form);
			$form = str_insert_before('</form>', '<input type="hidden" name="recipient" value="'.$recipient.'" />', $form);

			// add (or replace) template identifier, if specified
			$message_template_name = get_array_value($params, 'template', '', REGEXP_IDENTIFIER);
			if ($message_template_name > '') {
				$form = preg_replace('~<input\s[^>]*?name="template"[^/>]*/?>~', '', $form);
				$form = str_insert_before('</form>', '<input type="hidden" name="template" value="'.$message_template_name.'" />', $form);
			}

			// form ready, add it to template!
			$template = str_replace($match[0], $form, $template);
		}
		return $template;
	}

	/**
	 *
	 *
	 */
	function AJAXHandler() {
		// фильтруем вход
		$input_filter = array(
			'filename' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9][a-zA-Zа-яА-Я0-9_\-\s]*\.html$~ui')),
			'action'   => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui'))
		);
		$_INPUT = get_filtered_input($input_filter);

		switch ($_INPUT['action']) {
			case 'edit_template' : 
				if ($_INPUT['filename'] == '') {
					break;
				}
				$xml = new DOMDocument('1.0', 'utf-8');
				$xml->appendChild($root = $xml->createElement('root'));
				$root->appendChild($xml->createElement('filename'))->nodeValue = $_INPUT['filename'];
				$root->appendChild($xml->createElement('content'))->nodeValue = file_get_contents(__DIR__.'/templates/'.$_INPUT['filename']);
				return XSLTransform($xml->saveXML($root), __DIR__.'/edit.xsl');
		}
		return 'unknown action';
	}
		
		
	/**
	 *
	 *
	 */
	function adminGenerator() {

		// init
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = true;
		$xml->formatOutput = true;

		$root = $xml->createElement('root');
		$xml->appendChild($root);

		$dir = __DIR__.'/templates/';
		if (file_exists($dir) && is_dir($dir)) {
			$root->appendChild($filelist_node = $xml->createElement('files'));
			$d = scandir($dir);
			foreach ($d as $filename) {
				if (substr($filename, 0, 1) == '.') {
					continue;
				}
				$content = file_get_contents(__DIR__.'/templates/'.$filename);
				$title = preg_match('~<title>([^<]*)</title>~ui', $content, $match) ? $match[1] : '*** no title ***';
				$filename = iconv(filesystem_encoding(), 'utf-8', $filename);
				$filelist_node->appendChild($file_node = $xml->createElement('file'));
				$file_node->appendChild($xml->createElement('filename'))->nodeValue = $filename;
				$file_node->appendChild($xml->createElement('title'))->nodeValue = $title;
			}
		}
		return XSLTransform($xml->saveXML($root), __DIR__.'/list.xsl');
	}

}

?>