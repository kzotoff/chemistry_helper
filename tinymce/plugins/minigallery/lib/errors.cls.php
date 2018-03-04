<?php
	// E_RECOVERABLE_ERROR since PHP 5.2 only
	if(!defined('E_RECOVERABLE_ERROR'))
	{
		define('E_RECOVERABLE_ERROR', 4096, true);
	}

	/**
	 * ErrorHandler class, used for handling/logging/displaying php errors, notices and exceptions.
	 *
	 */
	class ErrorHandler
	{
		private $logPath, $bDisplayNotice;

		private $errortype = array
		(
			E_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_PARSE => 'Parsing Error',
			E_NOTICE => 'Notice',
			E_CORE_ERROR => 'Core Error',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_ERROR => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Runtime Notice',
			E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
		);

		private $user_errors = array
		(
			E_USER_ERROR,
			E_USER_WARNING,
			E_USER_NOTICE
		);


		/**
		 * ErrorHandler constructor; if $logFile defined, errors will be logged there, additionally, if $bDisplayNotice = true - the html notice will be printed.
		 *
		 * @param string $logPath
		 * @param bool $bDisplayNotice
		 */
		public function __construct($logPath = false, $bDisplayNotice = false)
		{
			set_error_handler(array(&$this, 'processError'), E_ALL | E_STRICT);
			error_reporting(E_ALL | E_STRICT);

			$this->logPath = $logPath;
			$this->bDisplayNotice = $bDisplayNotice;
		}

		/**
		 * Method for handling php errors.
		 *
		 * @param int $errno
		 * @param string $errmsg
		 * @param string $filename
		 * @param int $linenum
		 * @param object $vars
		 */
		public function processError($errno, $errmsg, $filename, $linenum, $vars)
		{
			if($this->logPath)
			{
				$date = date('d M Y, H:i:s (T)');

				$errorEntry =
					'<errorentry>' . "\n" .
					"\t" . '<datetime>' . $date . '</datetime>' . "\n" .
					"\t" . '<errornum>' . $errno . '</errornum>' . "\n" .
					"\t" . '<errortype>' . $this->errortype[$errno] . '</errortype>' . "\n" .
					"\t" . '<errormsg>' . $errmsg . '</errormsg>' . "\n" .
					"\t" . '<filename>' . $filename . '</filename>' . "\n" .
					"\t" . '<filelinenum>' . $linenum . '</filelinenum>' . "\n";

				if(in_array($errno, $this->user_errors))
				{
					$errorEntry .= "\t" . '<vartrace>' . "\n";

					foreach($vars as $key => $var)
					{
						$errorEntry .= "\t\t" . '<var name="' . $key . '">';

						if(is_array($var))
						{
							$errorEntry .= "\n" . $this->arrayWalk($var, "\t\t", "\t") . "\t\t";
						}
						else
						{
							$errorEntry .= $var;
						}

						$errorEntry .= '</var>' . "\n";
					}

					$errorEntry .= "\t" . '</vartrace>' . "\n";
				}
				$errorEntry .= '</errorentry>' . "\n\n";

				error_log($errorEntry, 3, $this->logPath);

				if($this->bDisplayNotice && $errno !== E_NOTICE && $errno !== E_WARNING && $errno !== E_STRICT)
				{
					$this->displayErrorNotice($errno, ($this->bDisplayNotice == 1 ? array($filename, $linenum, $errmsg) : false));
				}
			}
			elseif($errno !== E_NOTICE && $errno !== E_WARNING && $errno !== E_STRICT)
			{
				$this->displayErrorNotice($errno, array($filename, $linenum, $errmsg));
			}

			if($errno !== E_NOTICE && $errno !== E_WARNING && $errno !== E_STRICT)
			{
				exit(0);
			}
		}

		/**
		 * Displays HTML error notice.
		 *
		 * @param string $errno
		 * @param int $showError
		 */
		private function displayErrorNotice($errno, $error = false)
		{
			print
				'<div style="border: 2px dashed #f00; width: 350px; padding: 5px; font: normal 11px Verdana; background: #eee;">' .
				'<strong>Internal Application Error!</strong><br />' .
				'Error type: <em>' . $this->errortype[$errno] . '</em>';

			if($error)
			{
				print
					'<br />' .
					'File: <strong>' . $error[0] . '</strong><br />' .
					'Line: <strong>' . $error[1] . '</strong><br />' .
					'Message: <em>' . $error[2] . '</em>';
			}

			print
				'</div>';
		}

		/**
		 * Prints stack trace vars array as nested XML.
		 *
		 * @param array $array
		 * @param string $offset
		 * @return string
		 */
		private function arrayWalk($array, $offset)
		{
			$returnValue = "";
			foreach($array as $itemKey => $item)
			{
				$returnValue .=  $offset . "\t" . '<item key="' . $itemKey . '">';
				if(is_array($item))
				{
					$returnValue .=  "\n" . $this->arrayWalk($item, $offset . "\t") . $offset . "\t";
				}
				else
				{
					if(!is_object($item))
					{
						$returnValue .=  $item;
					}
					else
					{
						$returnValue .=  '[[[is object of type "' . gettype($item) . '"]]]';
					}
				}
				$returnValue .=  '</item>' . "\n";
			}

			return $returnValue;
		}
	}

	/**
	 * ErrorMessage class (extends Exception), used for creating custom errors with custom messages; php error can be raised and passed to ErrorHandler class.
	 *
	 */
	class ErrorMessage extends Exception
	{
		private $struct = array('func', 'error', 'errno' => '', 'type' => '', 'vars');

		/**
		 * ErrorMessage constructor; at least two parameters expected - function name and error message.
		 *
		 * @param string $func
		 * @param string $error
		 * @param mixed $vars
		 * @param int $type
		 * @param int $errno
		 */
		public function __construct($func, $error, $vars = false, $type = E_USER_ERROR, $errno = -1)
		{
			$this->struct['func'] = $func;
			$this->struct['error'] = $error;
			$this->struct['vars'] = $vars;
			$this->struct['errno'] = $errno;
			$this->struct['type'] = $type;

			parent::__construct($error, $errno);
		}

		/**
		 * Available paramters: func, error, errno, type, vars.
		 *
		 * @param string $propertyName
		 * @return mixed
		 */
		public function __get($propertyName)
		{
			switch($propertyName)
			{
				case 'line':
					return parent::getLine();
				case 'file':
					return parent::getFile();
				case 'trace':
					return parent::getTrace();
				case 'traceAsString':
					return parent::getTraceAsString();
				default:
					return $this->struct[$propertyName];
			}

		}

		/**
		 * Print html error report.
		 *
		 */
		public function toHTML()
		{
			print
				'<div style="border: 2px dashed #f00; width: 350px; padding: 5px; font: normal 11px Verdana; background: #eee;">' .
				'<strong>Exception raised:</strong><br />' .
				'Function "' . $this->func . '" reports:<br />' .
				'<em>"' . $this->error . '"</em><br />' .
				'</div>';
		}

		/**
		 * Raise php error; $userVars will be added to stack trace.
		 *
		 * @param mixed $userVars
		 * @param int $errorType
		 */
		public function raise($userVars = false, $errorType = false)
		{
			if($errorType === false)
			{
				$errorType = $this->struct['type'];
			}
			if($userVars === false)
			{
				unset($userVars);
			}
			if($this->struct['vars'])
			{
				$exceptionVars = $this->struct['vars'];
			}

			$http_user_agent = $_SERVER['HTTP_USER_AGENT'];
			$remote_addr = $_SERVER['REMOTE_ADDR'];

			if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
			{
				$http_x_forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			if(array_key_exists('HTTP_REFERER', $_SERVER))
			{
				$http_referer = $_SERVER['HTTP_REFERER'];
			}

			$filename = $this->file;
			$line = $this->line;
			$trace = $this->trace;

			$get = $_GET;
			$post = $_POST;

			switch($errorType)
			{
				case E_USER_NOTICE:
					unset($errorType);
					trigger_error('Function "' . $this->func . '" reports: ' . $this->error, E_USER_NOTICE);
				case E_USER_WARNING:
					unset($errorType);
					trigger_error('Function "' . $this->func . '" reports: ' . $this->error, E_USER_WARNING);
				default:
					unset($errorType);
					trigger_error('Function "' . $this->func . '" reports: ' . $this->error, E_USER_ERROR);
			}
		}
	}

	class DBErrorMessage extends ErrorMessage
	{
	}
?>