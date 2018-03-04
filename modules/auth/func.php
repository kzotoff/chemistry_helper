<?php //> Й <- UTF mark

/**
 * Authentication module for JuliaCMS
 *
 * @package J_Auth
 */
class J_Auth extends JuliaCMSModule {
	
	private $superuser_login = 'admin';
	private $superuser_password = '__AA1199';
	
	/**
	 * Used for username correctness
	 */
	const REGEXP_USERNAME = '~^[a-zA-Z0-9а-яА-Я_@!().\-]+$~';

	/**
	 * HTML to send on successful login or password changing
	 */
	const HTML_MESSAGE_SUCCESS = '<div class="login_success">%s</div>';

	/**
	 * HTML to send on failed login or password changing
	 */
	const HTML_MESSAGE_FAIL = '<div class="login_fail">%s</div>';

	/**
	 * Standard request parser
	 *
	 * @param string $template HTML to modify
	 * @return string modified or not modified template
	 */
	public function requestParser($template) {

		// input filtering entirely moved out since it also needed in ajax handler
		$I = $this->getInput();

		$redirect_target = '';

		$template = $this->takeAction($template, $redirect_target);
		
		// check "require login" state - if no username stored or it's empty, just show login page instead normal template
		if (
			(
			(isset($this->CONFIG['require_login']) && $this->CONFIG['require_login'])
			||
			isset($_GET['login'])
			)
			&& (!isset($_SESSION[$this->CONFIG['session_username']]) || (trim($_SESSION[$this->CONFIG['session_username']] == '')))
		) {
			$template = content_replace_body($template, file_get_contents(__DIR__.'/login.html'));
			$template = content_replace_title($template, 'Вход');
		}

		if ($redirect_target > '') {
			terminate('', 'Location: '.$redirect_target, 302);
		}
		return $template;
	}

	/**
	 *
	 */
	public function contentGenerator($template) {
		preg_match_all(macro_regexp('auth'), $template, $matches);

		$displayed_username = get_array_value($_SESSION, $this->CONFIG['session_username'], '')>'' ? $_SESSION[$this->CONFIG['session_username']] : '';
		foreach ($matches[0] as $match) {
			$macro_data = parse_plugin_template($match);
			switch ($macro_data['mode']) {  // TAG_TODO TAG_DOC в мануал добавить все возможные ключи
			
				// insert logged username somewhere
				case 'auth-user':
					$template = str_replace($match, $displayed_username, $template);
					break;

				// special visibility for some elements - only for logged user (selected or any)
				case 'only-for-login':
					// this macro must have long syntax, wrapping something else so just clean if short form
					if (substr($match, -2, 1) == '/') {
						$template = str_replace($match, '', $template);
						break;
					}

					// if content should be hidden, remove entire macro with internal contents, else only macro and leave inner data in place
					if (
						(isset($macro_data['who']) && ($macro_data['who'] != security_get_username_auth())) 
						||
						(!isset($macro_data['who']) && (security_get_username_auth() == 'anonymous'))
					) {
						$template = str_replace($match, '', $template);
					} else {
						preg_match_all('~(<macro\s[^>]*>(.*?)</macro>|\[macro\s[^\]]*\](.*?)\[/macro\])~smui', $match, $results); 						
						$template = str_replace($match, $results[2][0].$results[3][0], $template);
					}
					break;

				// for unregistered users
				case 'only-for-anonymous':
					// this macro must have long syntax, wrapping something else so just clean if short form
					if (substr($match, -2, 1) == '/') {
						$template = str_replace($match, '', $template);
						break;
					}
					if (security_get_username_auth() != 'anonymous') {
						$template = str_replace($match, '', $template);
					}
					break;

				// hide entire pages
				case 'require-login':
					if ($macro_data['who'] != security_get_username_auth()) {
						terminate('', 'Location: ./?login', 302);
					}				
					break;
			}
		}
		return $template;
	}

	/**
	 * Just redirects to $this->takeAction
	 *
	 * @return string text result
	 */
	public function AJAXHandler() {
		return $this->takeAction('', $dummy);
	}

	/** TAG_EXPERIMENT LJ56489876KHJHGJFHJFJIRUJTY ! userland logout button uses this!
	 * [EXPERIMENTAL] Modifies menu structure according logged users, access levels and so on
	 *
	 * returns true if node is OK to appear at the menu, false otherwise
	 *
	 * @param array $item standard menu item definition
	 * @return bool
	 */
	public function checkMenuItem($item) {
		if (
			(strpos($item['alias'], 'menu-item-logout') !== false)
			&& (get_array_value($_SESSION, $this->CONFIG['session_username'], '') == '')
		) {
			return false;
		}
		//if (;
		return true;
	}

	/**
	 * This function parses input data for both requestParser and AJAXHandler
	 * and performs some actions if requested.
	 *
	 * @param string $template page template for calling from requestParser
	 * @param string &$redirect_target location to redirect to
	 * @return string|bool modified template or true/false
	 */
	private function takeAction($template, &$redirect_target) {
		$I = $this->getInput();
		if (($I['module'] != 'auth') && ($I['ajaxproxy'] != 'auth')) {
			return $template;
		}
		
		$proxy_mode = get_array_value($I, 'ajaxproxy', false) == 'auth';
		switch ($I['action']) {

			// login
			case 'login':

				// check login/password
				$ok = $this->tryLogin($I['username'], $I['password'], $I['rememberme'], $login_result_text);

				// different actions on different call methods (straight vs AJAX) // TAG_DOC описать способы возврата
				if (!$proxy_mode) {
					while (preg_match(macro_regexp('auth'), $template, $match)) {
						$params = parse_plugin_template($match[0]);
						if (get_array_value($params, 'mode', false) == 'login-message') {
							$template = str_replace($match, $login_result_text, $template);
						}
					}
					return $template;
				} else {
					switch (get_array_value($I, 'return', 'html')) { // TAG_DOC
						case 'text':
							return ($ok ? 'OK' : 'NO') . $login_result_text;
							break;
						default: 
							return ($ok ? 'OK' : 'NO') . ':' . sprintf( $ok ? self::HTML_MESSAGE_SUCCESS : self::HTML_MESSAGE_FAIL, $login_result_text);
							break;
					}
				}
				break;

			// logout. always returns true
			case 'logout':
				$this->logout();
				$redirect_target = '.';
				return 'OK';
				break;

			default:
//				return 'unknown action (did you forget to add it to the checking rule?)';
				break;
		}
		return $template;
	}

	/**
	 * Calculates hash of salted password
	 *
	 * @param string $password
	 * @return string
	 */
	private function generateHash($password, $secret) {
		return sha1($password.md5($secret));
	}

	/**
	 * Checks username and password against "users" table
	 *
	 * @param string $username user login for check
	 * @param string $password password
	 * @return bool true when login+password match stored data, false elsewhere
	 */
	private function checkPassword($login, $password) {
		
		// TAG_MOD всегда храним логины малыми буковками
		$login = mb_strtolower($login);

		// этот флажок обозначает режим глобального админа
		$_SESSION['god_mode'] = false;

		if (
			($login == $this->superuser_login) &&
			($password == $this->superuser_password)
		) {
			$_SESSION['god_mode'] = true;
			Logger::instance()->log('superuser login', array('source'=>'auth'));
			return true;
		}

		if (($login_result = checkAccessForUser($login, $password)) === true) {
			
//			$admins = CMS::$cache['db']['object']->DB->querySingle('select "value" from "settings" where "key" = \'web.admin\'');
//			if (in_array($login, preg_split('~[\s,;]+~', $admins))) {
//				$_SESSION['god_mode'] = true;
//			}
			
			Logger::instance()->log('login successful for user '.$login, array('source'=>'auth'));
			return true;			
		} else {
			Logger::instance()->log('login failed for user '.$login.' ('.$login_result.')', array('source'=>'auth'));
			return false;
		}
			

/*		
		$DB = new PDOWrapper(
			$this->CONFIG['database']['server_driver'],
			$this->CONFIG['database']['server_host'],
			$this->CONFIG['database']['server_login'],
			$this->CONFIG['database']['server_password'],
			$this->CONFIG['database']['server_db_name']
		);

		if (!preg_match(self::REGEXP_USERNAME, $login)) {
			return false;
		}

		// if field specified for "secret", use its value, constant else
		$secret_field = isset($this->CONFIG['secret_field']) && ($this->CONFIG['secret_field'] > '') ? $DB->lb.$this->CONFIG['secret_field'].$DB->rb : '\''.$this->CONFIG['secret_default'].'\'';
		try {
			// note that "{$secret_field}" is not wrapped with braces as it can contain either field name or direct string
			$query = $DB->query("select {$this->CONFIG['md5_field']}, {$secret_field} as secret from {$this->CONFIG['table']} where {$this->CONFIG['login_field']} = '{$login}'");
		} catch (Exception $e) {
			return false;
		}
		// if no data returned at all, no such user
		if (($query === false) || !($data = $query->fetch())) {
			return false;
		}

		// get stored password hash
		$saved_md5 = $data[$this->CONFIG['md5_field']];

		// calculate test hash. note again that $data['secret'] contains either stored secret or some default value
		$check_md5 = $this->generateHash($password, $data['secret']);
		if ($saved_md5 != $check_md5) {
			return false;
		}
*/
		// all ok, get if out
	}

	/**
	 * Checks login/pasword against the DB records, executes login routines,
	 * returns login result wrapped within HTML
	 *
	 * @param string $login
	 * @param string $password
	 * @return bool true is login succeeded, false elsewhere
	 */
	private function tryLogin($login, $password, $rememberme, &$result) {

		$login = mb_strtolower($login);

		// check if password ok
		if (!$this->checkPassword($login, $password)) {
			$result = 'Доступ запрещен';
			return false;
		}

		// yeah we're logged in
		$_SESSION[$this->CONFIG['session_username']] = $login;
		
		// here we can check if user locked

		// all ok, access granted!
		$result = 'Вход выполнен';
		return true;
	}

	/**
	 * Filters input arrays ($_GET and $_POST)
	 *
	 * @return array filtered GET and POST requests
	 */
	private function getInput() {

		$input_filter = array(
			'row_id'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9!@\-]+$~ui')),
			'username'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.+$~ui')),
			'password'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~ui')),
			'password1'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'password2'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'module'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'action'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(login|logout|change_password|chpass|user-register|user-reset)$~ui')),
			'ajaxproxy'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'rememberme'   => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^on$~ui')),
		);		
		return get_filtered_input($input_filter);
	}

	/**
	 * Logs current user out
	 */
	private function logout() {
		unset($_SESSION[$this->CONFIG['session_username']]);
		return true;
	}
	
}

?>
