<?php
/**
 * simple logger utility
 *
 * @package Logger
 * @version 1.2 / 2015-10-08 11:20
 */


/*************************************************************************************************************************
This tool logs call-time memory usage and current time then displays data collected, to stdout, file or as return value.


To start, just create it at any time you need (see below for the parameters supported). Also you may just call
any other method, the logger singleton instance will be created automatically with the default parameters.

	$logger = Logger::singleton(array $options);
	or simple variant to use default parameters
	$logger = Logger::singleton(int $min_level);


Log your messages with the code like this:

	$logger::instance->log('your message', Logger::LOG_LEVEL_MESSAGE);
	$logger::instance->log('your message');
	Logger::instance->log('your message'); // if you don't want to create separate variable or call globally


Any time you need, make a dump with minimal level to show and some options (nothing at the moment)
	$logger::flushNow(Logger::LOG_LEVEL_MESSAGE, array('option_name'=>'value));
	or, for "return" output option,
	$result = $logger::flushNow(Logger::LOG_LEVEL_MESSAGE, array('option_name'=>'value'));
	or
	$logger::flushNow(Logger::LOG_LEVEL_MESSAGE);
	or even
	$logger::flushNow();


At any time, the entire log can be output with

	$logger->flushAll(Logger::LOG_LEVEL_MESSAGE, array('option_name'=>'value'));
	where array values will temporarily override initial ones. Return target also supported.


At any point you may also start again with memory and/or time

	$logger->resetCounters(array('memory', 'time'));
	to reset all, just use
	$logger->resetCounters();



At the first call, you may set parameters to the logger.
All subsequent calls will not affect options except flush routines.
Possible init options:
	locale = EN (default), RU, ... - language.
	target = stdout (default), file (writes to file), return (just returns as string).
	format = html - colored, styles allowed (default), or plain - tags will be stripped.
	filename = file to put logs. Ignored if stdout target selected.
	line_delimiter = this string will be added to every output string. Can be "<br />" (default) or PHP_EOL.
	flush = immediate (default), finished - output log messages immediately after receiving or by command (usually at the end of script).
	min_level = minimal event level to be collected. Also will be used as flush level if not specified while dumping.
	mem_unit = byte, kilo (default), mega, auto - unit to use (byte, kilobyte, megabyte) while displaying memory counters
	rewrite_log = true, false (default) - indicates whether or not should logger erase previous file if any

Output format templates:
	%message%               - text message logged
	%level%                 - textual representation of the event level

	%time%                  - timestamp.
	%time_delta_start%      - time from log start.
	%time_delta_prev%       - time from previous message (with the same or higher level).

	%memory%                - memory usage.
	%memory_delta_start%    - memory usage change, from logging start.
	%memory_delta_prev%     - memory usage change, from prev point.

*************************************************************************************************************************/

// define log level constants and using recommendations
/**
 * Z-logger
 *
 */
class Logger {

	/**
	 * Log level - paradnoid debugging
	 */
	const LOG_LEVEL_CRAZY   = 10; // 

	/**
	 * Log level - standard debug-level catch
	 */
	const LOG_LEVEL_DEBUG   = 20; // 

	/**
	 * Log level - standard message
	 */
	const LOG_LEVEL_MESSAGE = 30; // 

	/**
	 * Log level - not valuable messages (but indicates something unusual)
	 */
	const LOG_LEVEL_NOTICE  = 40; // 

	/**
	 * Log level - bad configs, missing parameters and so on, but we may continue
	 */
	const LOG_LEVEL_WARNING = 50; // 

	/**
	 * Log level - something that prevents some actions
	 */
	const LOG_LEVEL_ERROR   = 60;

	/**
	 * Log level - so bad that we shall to terminate script
	 */
	const LOG_LEVEL_FATAL   = 70;

	/**
	 * Log level - something even more bad
	 */
	const LOG_LEVEL_CRASH   = 80;

	/**
	 * Log level - oops...
	 */
	const LOG_LEVEL_ALARM   = 90;

	/**
	 * logger inself
	 *
	 * @var Logger
	 */
	private static $instance;

	/**
	 * all log records will be stored here
	 *
	 * @var mixed[]
	 */
	private $log = array();

	/**
	 * logger start time
	 *
	 * @var int
	 */
	private $time_start = 0;

	/**
	 * memory usage as start
	 *
	 * @var int
	 */
	private $memory_start = 0;

	/**
	 * all user-visible strings, consolidated for easy localization
	 *
	 * @var string[]
	 */
	private $locale = array();

	/**
	 * all logger options
	 *
	 * @var mixed[]
	 */
	private $options = array();

	/**
	 * bad configuration indicator
	 *
	 * this flag indicates bad configuration options (e.g. empty filename) and suppresses any actions,
	 * all public functions will just return immediately
	 *
	 * @var bool
	 */
	private $bad_config = false;

	/**
	 * flushed rows counter
	 *
	 * @var int
	 */
	private $flushed_to;

	/**
	 * output formats
	 *
	 * @var mixed[]
	 */
	private $var_formats = array(
		'time_current'        => '',
		'time_delta_start'    => '%7.3f',
		'time_delta_prev'     => '%+7.3f',
		'memory'              => '%\' 6.0f',
		'memory_delta_start'  => '%+\' 6.0f',
		'memory_delta_prev'   => '%+\' 6.0f',
	);
	
	/**
	 * User-defined function to call on every log event.
	 * Must accept 2 parameters: message and level
	 *
	 * @var Closure
	 */
	private $user_function;

	/**
	 * prevent creating new instances
	 */
	private function __construct() {}

	/**
	 * prevent creating new instances
	 */
	private function __clone() {}

	/**
	 * prevent creating new instances
	 */
	private function __wakeup() {}

	/**
	 * creates a singleton object
	 *
	 * @param array|int $options  options or just minimal log level
	 * @return Logger
	 */
	public static function instance($options = array()) {

		if (empty(self::$instance)) {
			self::$instance = new self();

			// possible options (first is default)
			$option_control = array(
				'min_level'      => array(self::LOG_LEVEL_DEBUG, self::LOG_LEVEL_MESSAGE, self::LOG_LEVEL_CRAZY, self::LOG_LEVEL_NOTICE, self::LOG_LEVEL_WARNING, self::LOG_LEVEL_ERROR, self::LOG_LEVEL_FATAL, self::LOG_LEVEL_CRASH, self::LOG_LEVEL_ALARM),
				'target'         => array('stdout', 'file', 'return'),
				'locale'         => array('EN', 'RU'),
				'format'         => array('html', 'plain'),
				'line_delimiter' => array('<br />', PHP_EOL),
				'flush'          => array('manual', 'immediate'),
				'mem_unit'       => array('auto', 'kilo', 'byte', 'mega'),
				'rewrite_log'    => array(false, true),
				'default_level'  => array(self::LOG_LEVEL_MESSAGE, self::LOG_LEVEL_DEBUG, self::LOG_LEVEL_CRAZY, self::LOG_LEVEL_NOTICE, self::LOG_LEVEL_WARNING, self::LOG_LEVEL_ERROR, self::LOG_LEVEL_FATAL, self::LOG_LEVEL_CRASH, self::LOG_LEVEL_ALARM)
			);

			// some options are unrestricted, so we only need to check if they are set
			$option_defaults = array(
				'filename'       => 'default.log',
				'output_format'  => '%time_delta_start% (%time_delta_prev%) %memory_color_start%%memory_delta_prev%%memory_color_end% [%level%] %message%'
			);

			// simple call detection
			if (!is_array($options)) {
				$options = array('min_level'=>$options);
			}

			// set default values to unrestricted options if not set
			foreach ($option_defaults as $option_name => $value) {
				if (!isset($options[$option_name])) {
					$options[$option_name] = $value;
				}
			}

			// parse init values and set runtime options
			foreach ($option_control as $option_name => $values) {
				if (!isset($options[$option_name]) || !in_array($options[$option_name], $values)) {
					$options[$option_name] = $values[0];
				}
			}

			// some more checks
			if (($options['target'] == 'file') && ($options['filename'] == '')){
				self::$instance->bad_config = true;
				trigger_error(__CLASS__.': Unable to create logger: target is file, but filename was not specified. No log will be collected.', E_USER_WARNING);

				return self::$instance;
			}

			self::$instance->options = $options;

			// apply language
			self::$instance->setLocale(self::$instance->options['locale']);

			// initial start values
			self::$instance->time_start   = microtime(true);
			self::$instance->memory_start = memory_get_usage();
			self::$instance->flushed_to   = -1;

			// get absolute path to the log (master script may change working directory directories and relative path will fail)
			// only if relative path is given
			if ((self::$instance->options['target'] == 'file') && (self::$instance->options['filename'] > '')) {
				if (!preg_match('~^([A-Za-z]:\\\\|\\\/|\\\\)~', self::$instance->options['filename'])) {
					self::$instance->options['filename'] = getcwd().'/'.self::$instance->options['filename'];
				}
			}

			// drop prev log if requested
			if (self::$instance->options['rewrite_log'] && (self::$instance->options['target'] == 'file') && (self::$instance->options['filename'] > '')) {
				if (file_exists(self::$instance->options['filename'])) {
					if (!unlink(self::$instance->options['filename'])) {
						trigger_error(__CLASS__.': An error occured while deleting previous log ('.(self::$instance->options['filename']).'). Check if file exists and writable.', E_USER_WARNING);
					}
				}
			}
			
			// get selfie
			self::$instance->log('******************************************', self::LOG_LEVEL_DEBUG);
			self::$instance->log(self::$instance->locale['logger_started'], self::LOG_LEVEL_DEBUG);
			foreach (self::$instance->options as $option_name => $value) {
				self::$instance->log('option set: '.$option_name.' = '.$value, self::LOG_LEVEL_DEBUG);
			}

		}
		return self::$instance;
	}

	/**
	 * Sets internal user-defined function
	 *
	 * @return void
	 */
	public function setUserFunction( $user_function ) {
		$this->user_function = $user_function;
	}
	
	/**
	 * puts an event to the log
	 *
	 * @param string $message message to log
	 * @param int|array $level message level or array containing element 'level'
	 *
	 * @return void
	 */
	public function log($message = '', $additional = false) {
		
		// if bad config was detected, do nothing
		if ($this->bad_config) {
			return false;
		}

		// set default value
		if ($additional === false) {
			$additional = $this->options['default_level'];
		}
		
		// if array came, extract level. other data will be passed to user function later
		if (is_array($additional)) {
			$level = isset($additional['level']) ? $additional['level'] : $this->options['default_level'];
		} else {
			$level = $additional;
		}

		// if min_level is set, do not collect event at all
		if ($level < $this->options['min_level']) {
			return 0;
		}

		$log_elem = array();

		// these elements are always logged
		$log_elem['time']         = microtime(true);
		$log_elem['time_start']   = $this->time_start;
		$log_elem['memory']       = memory_get_usage();
		$log_elem['memory_start'] = $this->memory_start;
		$log_elem['level']        = $level;
		$log_elem['message']      = $message;

		// here some additional data will be stored
		// ...

		// store and forget
		array_push($this->log, $log_elem);

		// output immediately if option present
		if ($this->options['flush'] == 'immediate') {
			self::$instance->outputOne(count($this->log)-1, $this->options['min_level']);
			// don't output these line anymore
			$this->flushed_to = count($this->log) - 1;
		}

		if (isset($this->user_function) && is_a($this->user_function, 'Closure')) {
			$call_this = $this->user_function;
			$call_this($message, $additional);
		}
	}

	/**
	 * flushes yet another log portion
	 *
	 * @param int $min_level minimal event level to output
	 * @param array $options
	 *
	 * @return void|false
	 */
	public function flushNow($min_level = self::LOG_LEVEL_MESSAGE, $override_options = array()) {

		// if bad config was detected, do nothing
		if ($this->bad_config) {
			return false;
		}

		// temporarily change options if needed
		$old_options = $this->options;
		$this->options = array_merge($this->options, $override_options);

		// iterate all lines and send them out
		$result = ''; // total string for "return" output method
		foreach($this->log as $record_id => $log_record) {
			if ($record_id > $this->flushed_to) { // prevent double lines
				$result .= self::$instance->outputOne($record_id, $min_level);
			}
		}

		// don't output these lines anymore
		$this->flushed_to = count($this->log) - 1;

		// restore options
		$this->options = $old_options;
	}

	/**
	 * Simply clears all log events. Counters are not affected
	 *
	 */
	public function clearLog() {
		$this->log = array();		
	}

	/**
	 * outputs entire log
	 *
	 * unlike flushNow, this method doesn't use and affect already-flushed marker and always shows all events
	 *
	 * @param int $min_level minimal event level to output
	 * @param array $options
	 *
	 * @return string
	 */
	public function flushAll($min_level = self::LOG_LEVEL_MESSAGE, $override_options = array()) {

		// if bad config was detected, do nothing
		if ($this->bad_config) {
			return false;
		}

		// temporarily change options if needed
		$old_options = $this->options;
		$this->options = array_merge($this->options, $override_options);

		// iterate all lines and send them out
		$return = '';
		foreach ($this->log as $record_id => $log_record) {
			$return .= self::$instance->outputOne($record_id, $min_level);
		}

		// restore options
		$this->options = $old_options;
		return $return;
	}

	/**
	 * resets internal start counters like just started. usual for loop profiling
	 *
	 * @param array $reset counters to reset
	 *
	 * @return void|false
	 */
	public function resetCounters($reset = array('time', 'memory')) {

		// if bad config was detected, do nothing
		if ($this->instance->bad_config) {
			return false;
		}

		if (in_array('time', $reset)) {
			self::$instance->time_start  = microtime(true);
			self::$instance->log($this->instance->locale['reset_time'], self::LOG_LEVEL_NOTICE);
		}
		if (in_array('memory', $reset)) {
			self::$instance->memory_start = memory_get_usage();
			self::$instance->log($this->instance->locale['reset_memory'], self::LOG_LEVEL_NOTICE);
		}
	}

	/**
	 * outputs one log record to target, specified while init
	 *
	 * @param int $record_id
	 * @param int $min_level minimal event level to output
	 *
	 * @return string
	 */
	private function outputOne($record_id, $min_level) {

		// some default for lazy user
		if (!isset($min_level)) {
			$min_level = $this->options['min_level'];
		}

		// no output for non-valuable events
		if ($this->log[$record_id]['level'] < $min_level) {
			return '';
		}

		// preformatting
		$str = self::$instance->logRecordToString($record_id, $min_level);
		if ($this->options['format'] == 'plain') {
			$str = strip_tags($str);
		}
		
		// now make it out!
		switch ($this->options['target']) {
			case 'file':
				if (!file_put_contents($this->options['filename'], $str . $this->options['line_delimiter'], FILE_APPEND)) {
					trigger_error('ZLOG: An error occured while writing log to '.($this->options['filename']).'. Check if file exists and writable', E_USER_WARNING);
				}
				$return = '';
				break;
			case 'return':
				$return = $str . $this->options['line_delimiter'];
				break;
			case 'stdout':
			default:
				echo $str . $this->options['line_delimiter'];
				$return = '';
				break;
		}
		return $return;
	}

	/**
	 * converts given log record to output string using template given
	 *
	 * @param array $record_id log record id
	 * @param int $min_level minimal event level to use
	 * @return string
	 *
	 * @return string
	 */
	private function logRecordToString($record_id, $min_level) {

		// this record will be used as data source
		$log_record = $this->log[$record_id];

		// now we must find previous event with min level requested. current will be used if nothing will be found
		$prev_record = $log_record;

		$i = $record_id - 1;
		while ($i > 0) {
			if ($this->log[$i]['level'] >= $min_level) {
				$prev_record = $this->log[$i];
				break;
			}
			$i --;
		}

		// default message text
		$text_result = $this->options['output_format'];

		// note that this replacement made first and further replacents will affect message too
		$text_result = str_replace('%message%',            $log_record['message'], $text_result);

		$text_result = str_replace('%date%',               date('Y.m.d', $log_record['time']), $text_result);

		$text_result = str_replace('%time%',               self::timeToString($log_record['time']),                                                         $text_result);
		$text_result = str_replace('%time_delta_start%',   sprintf($this->var_formats['time_delta_start'], $log_record['time'] - $log_record['time_start']), $text_result);
		$text_result = str_replace('%time_delta_prev%',    sprintf($this->var_formats['time_delta_prev'],  $log_record['time'] - $prev_record['time']),      $text_result);

		$text_result = str_replace('%memory%',             self::bytesToUnit($log_record['memory'],                               $this->var_formats['memory']),             $text_result);
		$text_result = str_replace('%memory_delta_start%', self::bytesToUnit($log_record['memory'] - $log_record['memory_start'], $this->var_formats['memory_delta_start']), $text_result);
		$text_result = str_replace('%memory_delta_prev%',  self::bytesToUnit($log_record['memory'] - $prev_record['memory'],      $this->var_formats['memory_delta_prev']),  $text_result);

		$text_result = str_replace('%memory_color_start%', (($log_record['memory'] - $prev_record['memory']) > 0 ? '<span style="color:red;">' : '<span style="color:green;">'), $text_result);
		$text_result = str_replace('%memory_color_end%',   '</span>', $text_result);

		switch ($log_record['level']) {
			case self::LOG_LEVEL_DEBUG:   $text_result = str_replace('%level%', $this->locale['level_debug'],   $text_result); break;
			case self::LOG_LEVEL_NOTICE:  $text_result = str_replace('%level%', $this->locale['level_notice'],  $text_result); break;
			case self::LOG_LEVEL_MESSAGE: $text_result = str_replace('%level%', $this->locale['level_message'], $text_result); break;
			case self::LOG_LEVEL_WARNING: $text_result = str_replace('%level%', $this->locale['level_warning'], $text_result); break;
			case self::LOG_LEVEL_ERROR:   $text_result = str_replace('%level%', $this->locale['level_error'],   $text_result); break;
			case self::LOG_LEVEL_FATAL:   $text_result = str_replace('%level%', $this->locale['level_fatal'],   $text_result); break;
			case self::LOG_LEVEL_CRASH:   $text_result = str_replace('%level%', $this->locale['level_crash'],   $text_result); break;
			case self::LOG_LEVEL_ALARM:   $text_result = str_replace('%level%', $this->locale['level_alarm'],   $text_result); break;
			default:                       $text_result = str_replace('%level%', $this->locale['level_unknown'], $text_result); break;
		}

		return $text_result;
	}

	/**
	 * converts timestamp to user-friendly string
	 *
	 * @param float $time timestamp to convert
	 *
	 * @return string
	 */
	private function timeToString($time) {
		return date('H:i:s', $time) . '.' . substr('000'.((int)($time*1000)), -3);
	}

	/**
	 * converts bytes value to a kilobytes or megabytes, according to options. Returns value with unit sign (B, K, M).
	 *
	 * @param int $value
	 * @param string $format
	 *
	 * @return string
	 */
	private function bytesToUnit($value, $format) {
		switch ($this->options['mem_unit']) {
			case 'byte':
				$letter = 'b';
				break;
			case 'mega':
				$value = $value / 1024 / 1024;
				$letter = 'm';
				break;
			case 'auto':
				$letter = 'b';
				foreach (array('k', 'm', 'g') as $try_more) {
					if (abs($value) > 1024) {
						$value = $value / 1024;
						$letter = $try_more;
					}
				}
				break;
			case 'kilo':
			default:
				$value = $value / 1024;
				$letter = 'k';
				break;
		}
		return sprintf($format, $value) . $letter;
	}

	/**
	 * user-visible messages init
	 *
	 * @param string $set_locale locale code
	 *
	 * @return string
	 */
	private function setLocale($set_locale = 'en') {

		// EN is the default language and initialized anyway. Some/all messages can be replaced with the localized versions
		$this->locale['logger_started'] = 'logger started at %date% %time%';
		$this->locale['logger_already_started'] = 'logger instance already activated, options not redefined';
		$this->locale['default_message'] = '* no message *';

		// event level marks
		$this->locale['level_debug']   = 'DEBUG  ';
		$this->locale['level_notice']  = 'NOTICE ';
		$this->locale['level_message'] = 'MESSAGE';
		$this->locale['level_warning'] = 'WARNING';
		$this->locale['level_error']   = 'ERROR  ';
		$this->locale['level_fatal']   = 'FATAL  ';
		$this->locale['level_crash']   = 'CRASH  ';
		$this->locale['level_alarm']   = 'ALARM * ALARM * ALARM';

		$this->locale['reset_time']   = 'time counter was reset';
		$this->locale['reset_memory']   = 'memory counter was reset';

		switch (strtoupper($set_locale)) {
			case 'RU':
				$this->locale['logger_start_message'] = 'Логгер запущен, %time%';
				break;

			case 'EN':
				// already defined, yeah
				break;

			default:
				$this->log('no localization found for "'.$set_locale.'"', self::LOG_LEVEL_WARNING);
				break;
		}
	}

	/**
	 * returns default log level
	 *
	 * @return int
	 */
	public function getDefaultLogLevel() {
		return $this->options['default_level'];
	}

}

?>