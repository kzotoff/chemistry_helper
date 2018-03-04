<?php //> Й <- UTF mark

/**
 * SMS works through sms.ru
 *
 * @package J_SMS_ru
 */
class J_SMS_ru extends JuliaCMSModule {

	/**
	 * sender name as it will be shown to addressee
	 *
	 * @name $api_id
	 */
	private $from = false;

	/**
	 * server response text values
	 *
	 * @name $status_messages
	 */
	private static $status_messages = array(
		100	=> 'Сообщение находится в нашей очереди',
		101	=> 'Сообщение передается оператору',
		102	=> 'Сообщение отправлено (в пути)',
		103	=> 'Сообщение доставлено',
		104	=> 'Не может быть доставлено: время жизни истекло',
		105	=> 'Не может быть доставлено: удалено оператором',
		106	=> 'Не может быть доставлено: сбой в телефоне',
		107	=> 'Не может быть доставлено: неизвестная причина',
		108	=> 'Не может быть доставлено: отклонено',
		200 => 'Неправильный api_id',
		201 => 'Не хватает средств на лицевом счету',
		202 => 'Неправильно указан получатель',
		203 => 'Нет текста сообщения',
		204 => 'Имя отправителя не согласовано с администрацией',
		205 => 'Сообщение слишком длинное (превышает 8 СМС)',
		206 => 'Будет превышен или уже превышен дневной лимит на отправку сообщений',
		207 => 'На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей',
		208 => 'Параметр time указан неправильно',
		209 => 'Вы добавили этот номер (или один из номеров) в стоп-лист',
		210 => 'Используется GET, где необходимо использовать POST',
		211 => 'Метод не найден',
		212 => 'Текст сообщения необходимо передать в кодировке UTF-8 (вы передали в другой кодировке)',
		220 => 'Сервис временно недоступен, попробуйте чуть позже.',
		230 => 'Сообщение не принято к отправке, так как на один номер в день нельзя отправлять более 60 сообщений.',
		300 => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
		301 => 'Неправильный пароль, либо пользователь не найден',
		302 => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)',
	);

	/**
	 * GET template for sending
	 *
	 * @name $template_send
	 */
	private $template_send = 'http://sms.ru/sms/send?api_id=%api_id%&to=%to%&text=%text%&from=%from%';

	/**
	 * Sends a single SMS
	 *
	 * @param mixed $to string or string array, phone(s) to send message to
	 * @param string $from sender's name as it will be visible
	 * @param string $text message text, in UTF-8 encoding
	 * @param string &$text_result server response (error text on error, sms ID on success)
	 * @param array $options various options:
	 * @return true on success, false on error. Text explanation will be contained in $text_result
	 */
	public function sendSingle($phone, $text, &$text_result, $options = array()) {

		$phone_filtered = preg_replace('~[^0-9]~', '', $phone);
		if (!preg_match('~^[0-9]{11}$~', $phone_filtered)) {
			$text_result = 'bad number';
			return false;
		}

		$url = str_replace(
			array('%api_id%',    '%to%',          '%text%',         '%from%'  ),
			array($this->CONFIG['api_id'], $phone_filtered, urlencode($text), get_array_value($this->CONFIG, 'from', '')),
			$this->template_send
		);
		$result = file_get_contents($url);
//		$result = "100\n201531-1000005\nbalance=0";

		$result_strings = preg_split('~[\x0A\x0D]+~smui', $result);
		file_put_contents(__DIR__.'/log.log', '--- '.date('Y.m.d H:i:s').' ---'.PHP_EOL.'to: '.$phone.PHP_EOL.$result.PHP_EOL, FILE_APPEND);
		if ($result_strings[0] == '100') {
			$text_result = $result_strings[1];
			return true;
		} else {
			$text_result = self::$status_messages[$result_strings[0]];
			return false;
		}
	}

	/**
	 * Multisend wrapper for sendSingle
	 *
	 * @param mixed $to string or string array, phone(s) to send message to
	 * @param string $from sender's name as it will be visible
	 * @param string $text message text, in UTF-8 encoding
	 * @param string &$text_result server response
	 * @param array $options various options, refer setSingle for details
	 * @return bool false if any error occured, true when all ok
	 */
	private function sendMulti($to, $text, &$text_results, $options = array()) {
		if (is_array($to)) {
			$all_ok = true;
			$text_results = array();
			foreach($to as $phone) {
				$all_ok = $this->sendSingle($phone, $text, $text_result, $options) && $all_ok; // only one false result
				$text_results[] = $text_result;
			}
			return $all_ok;
		}
	}

	/**
	 * sends a message using database record
	 *
	 * @param string $row_id record identifier
	 */
	private function sendFromJ_DB($row_id) {
		
		$r = module_init('db');

		$DB = CMS::$cache['db']['object']->DB;

		$text_result = '* unknown result *';
		// formal check
		if (!preg_match('~^[a-zA-Z0-9_\-]+$~ui', $row_id)) {
			return 'ERROR: bad ID';
		}

		// try to query
		if (!($query = $DB->query('select phone, text, sent from sms where id=\''.$row_id.'\''))) {
			return 'ERROR: bad ID';
		};

		// check if any data came back
		if (!($data = $query->fetch(PDO::FETCH_ASSOC))) {
			return 'ERROR: record not exists';
		}

		// check if already sent
		if ($data['sent'] > '') {
			return 'ERROR: message already sent';
		}

		// yeah all OK, send it now
		if ($this->sendSingle($data['phone'], $data['text'], $text_result, array())) {
			$status = 'OK '.$text_result;
			$sent = date('Y.m.d H:i:s');
			$sms_id = $text_result;
			$DB->query("update `sms` set `status_text`='Отправлено', `sent`='$sent', `sms_id`='$sms_id' where `id`='$row_id'");
		} else {
			$status = 'ERROR: '.$text_result;
			$DB->query("update `sms` set `status_text`='$text_result' where `id`='$row_id'");
		}

		// update table
		return $status;
		break;
	}
	
	/**
	 * deletes all records
	 */
	private function deleteFromJ_DB() {

		module_init('db');
		$DB = CMS::$cache['db']['object']->DB;

		$DB->query('delete from `sms`');
		return 'OK';
		
	}
	
	/**
	 * SMS.ru notification service responder
	 *
	 */
	private function parseNotificatorMessage($data) {

		if (!is_array($data)) {
			return false;
		}

		module_init('db');
		$DB = CMS::$cache['db']->DB;
		
		$DB->beginTransaction();
		
		$statement = $DB->prepare("update sms set status_text = :status_text where sms_id = :sms_id");
		foreach ($data as $string) {
			$result = preg_split('~[\x0A\x0D]+~smui', $string);
			if (!is_array($result)) {
				continue;
			}
			if (($result[0] == 'sms_status') && preg_match('~^[0-9]+\-[0-9]+$~', $result[1]) && preg_match('~^[0-9]+$~', $result[2])) {
				$statement->bindValue(':sms_id', $result[1]);
				$statement->bindValue(':status_text', $result[2].' '.isset(self::$status_messages[$result[2]]) ? self::$status_messages[$result[2]] : '*** неизвестный статус ***');
				$statement->execute();
				if ($result[2] == '103') {
					$delivered = date('Y.m.d H:i:s');
					$DB->exec("update sms set delivered = '$delivered' where sms_id = '{$result[1]}'");
				}
			}
		}
		$DB->commit();
		
	}

	// TAG_TODO
	// нужно разобраться, как разделить отправляющую часть и БД-шную, т.к. один кусок модуля работает с базой, т.е. должен быть
	// оформлен как часть модуля J_DB, а второй кусок по идее самодостаточен и должен уметь обходиться без наличия модуля J_DB вообще
	public function AJAXHandler() {

		// фильтруем вход
		$input_filter = array(
			'row_id' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\-]+$~ui')),
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui')),
		);
		$_INPUT = get_filtered_input($input_filter);

		// ответ по умолчанию
		$response = 'unknown function';
		switch ($_INPUT['action']) {

			case 'send_all':
				break;

			case 'send':
				return $this->sendFromJ_DB($_INPUT['row_id']);
				break;

			case 'delete_all':
				return $this->deleteFromJ_DB();
				break;

			case 'update_status':
				if (isset($_POST['data'])) {
					$this->parseNotificatorMessage($_POST['data']);
				}
				return '100';
				break;
		}
	}


}




?>