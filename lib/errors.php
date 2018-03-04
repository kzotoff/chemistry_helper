<?php //>

function common_error_handler($errno, $msg, $file, $line) {

	$error_levels = array(
		E_ERROR           => 'E_ERROR',
		E_WARNING         => 'E_WARNING',
		E_PARSE           => 'E_PARSE',
		E_NOTICE          => 'E_NOTICE',
		E_CORE_ERROR      => 'E_CORE_ERROR',
		E_CORE_WARNING    => 'E_CORE_WARNING',
		E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR      => 'E_USER_ERROR',
		E_USER_WARNING    => 'E_USER_WARNING',
		E_USER_NOTICE     => 'E_USER_NOTICE',
		E_STRICT          => 'E_STRICT'
	);

	$debug_info = debug_backtrace();

	
	echo 'Runtime message '.$error_levels[$errno].' level at '.$file.' line '.$line.'.<br />';
	echo 'The message is: '.$msg.'<br />';

	$stack_level = 0;
	if (count($debug_info) > 1) {
		echo 'Call stack:<br />';
		
		foreach ($debug_info as $stack_element) {
			if (!isset($stack_element['file'])) {
				$stack_element['file'] = 'unknown source';
			}
			if (!isset($stack_element['line'])) {
				$stack_element['line'] = 'unknown line';
			}
			if ($stack_level > 0) {
				$args = '';
				for ($i = 0; $i < count($stack_element['args']); $i ++) {
					if (is_array($stack_element['args'][$i])) {
						foreach ($stack_element['args'][$i] as $argkey => $argvalue) {
							$args .= ($args>'' ? ', ' : '') . substr($argkey.'='.$argvalue, 0, 60);
						}
					} elseif (is_object($stack_element['args'][$i])) {
						$args .= ($args>'' ? ', ' : '') . '"'.$stack_element['class'].'" object';
					} else {
						$args .= ($args>'' ? ', ' : '') . substr($stack_element['args'][$i], 0, 60);
					}
				}
				echo $stack_level.' - '.$stack_element['function'].'('.htmlspecialchars($args).'), called at '.$stack_element['file'].', line '.$stack_element['line'].'<br />';
			}
			$stack_level ++;
		}
	} else {
		echo 'No call stack<br />';
	}
	echo '-- <br />';
	
}

?>