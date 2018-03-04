<?php //> É <- UTF mark

/**
 * simple redirect engine
 *
 * @package J_Redirect
 */
class J_Redirect extends JuliaCMSModule {

	/**
	 * Default HTTP response if not set
	 *
	 * @const DEFAULT_HTTP_CODE
	 */
	const DEFAULT_HTTP_CODE = '200';

	/**
	 * HTTP responses texts
	 *
	 * @name $http_responses
	 */
	public $http_code_texts = array(
		'100' => 'Continue',
		'101' => 'Switching Protocols',
		'102' => 'Processing',
		'105' => 'Name Not Resolved',

		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'207' => 'Multi-Status',
		'226' => 'IM Used',

		'300' => 'Multiple Choices',
		'301' => 'Moved Permanently',
		'302' => 'Moved Temporarily',
		'302' => 'Found',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		'306' => 'You sould not see this',
		'307' => 'Temporary Redirect',

		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'407' => 'Proxy Authentication Required',
		'408' => 'Request Timeout',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URI Too Large',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested Range Not Satisfiable',
		'417' => 'Expectation Failed',
		'418' => 'I\',m a teapot',
		'422' => 'Unprocessable Entity',
		'423' => 'Locked',
		'424' => 'Failed Dependency',
		'425' => 'Unordered Collection',
		'426' => 'Upgrade Required',
		'428' => 'Precondition Required',
		'429' => 'Too Many Requests',
		'431' => 'Request Header Fields Too Large',
		'434' => 'Requested host unavailable',
		'449' => 'Retry With',
		'451' => 'Unavailable For Legal Reasons',
		'456' => 'Unrecoverable Error',
		'499' => 'Nginx says: client closed connection',

		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'502' => 'Bad Gateway',
		'503' => 'Service Unavailable',
		'504' => 'Gateway Timeout',
		'505' => 'HTTP Version Not Supported',
		'506' => 'Variant Also Negotiates',
		'507' => 'Insufficient Storage',
		'508' => 'Loop Detected',
		'509' => 'Bandwidth Limit Exceeded',
		'510' => 'Not Extended',
		'511' => 'Network Authentication Required'
	);
	
	function requestParser($template) {

		// now some bunch of samples if you don',t want to modify config file
		//
        // ! absolute redirect area, take care !
		//
        ////////////////////////////////////////////////////////
        // common version
        ////////////////////////////////////////////////////////
        //if (
        //        (($_GET['key1',] == 'value1',) && ($_GET['key2',] == 'value2',))
        //        || ($_SERVER['QUERY_STRING',] == 'key1=value1&key2=value2',)
        //
        //) {
        //        header('HTTP/1.1 301 Moved Permanently',);
        //        header('Location: chillers',);
        //        terminate();
        //}
        ////////////////////////////////////////////////////////
		// use this if you need only some GET keys to match
        ////////////////////////////////////////////////////////
        //if (($_GET['key1',] == 'value1',) && ($_GET['key2',] == 'value2',)) {
        //        header('HTTP/1.1 301 Moved Permanently',);
        //        header('Location: chillers',);
        //        terminate();
        //}
        ////////////////////////////////////////////////////////
		// full match version
        ////////////////////////////////////////////////////////
        //if ($_SERVER['QUERY_STRING',] == 'key1=value1&key2=value2',) {
        //        header('HTTP/1.1 301 Moved Permanently',);
        //        header('Location: chillers',);
        //        terminate();
        //}
        ////////////////////////////////////////////////////////

		// absolute redirect, ignoring module call method
		if (isset($this->CONFIG['redirect_rules']) && is_array($this->CONFIG['redirect_rules'])) {
			foreach ($this->CONFIG['redirect_rules'] as $rule) {

				$redirect = false;

				if (!isset($rule['check'])) {
					popup_message_add('Redirect: no "check" section found, skipping rule', JCMS_MESSAGE_WARNING);
					continue;
				}
				$check_this = $rule['check'];

				// if rule is string, check entire query string
				if (is_string($check_this)) {
					$redirect = ($_SERVER['QUERY_STRING'] == $check_this);
				}

				// if rule is array, check all pairs
				if (is_array($check_this)) {
					$redirect = (count(array_diff($check_this, $_GET)) == 0);
				}
				
				// ok, we need to redirect
				if ($redirect) {

					// first use default code as default ;-)
					$http_code = self::DEFAULT_HTTP_CODE;
					
					// if location specified, use 301
					if (isset($rule['location'])) {
						$http_code = 301;
					}

					// if code set explicitly, use if correct
					if (isset($rule['code']) && isset($this->http_code_texts[$rule['code']])) {
						$http_code = $rule['code'];
					}
					
					// send special headers for special cases
					switch ($http_code) {
						case '301':
							header('Location: '.$rule['location']);
							break;
					}
					terminate($this->http_code_texts[$http_code], '', $http_code);
				}
			}
		}

		if ((@$_POST['module'] == 'redirect') || (@$_GET['module'] == 'redirect')) {
			// yeah filter input
			$input_filter = array(
				'action'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(redirect|no_redirect)$~ui')),
				'target'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS))
			);

			$R = get_filtered_input($input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));

			switch ($R['action']) {
				// explicit redirect
				case 'redirect':
					header('Location: ./'.$R['target']);
					terminate();
					break;
			}
		}
		return $template;
	}

}

?>