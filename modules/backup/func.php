<?php //> Й <- UTF mark

/**
 * Backup module for JuliaCMS
 *
 * @package J_Backup
 */
class J_Backup extends JuliaCMSModule {

	/**
	 *
	 */
	public function requestParser($template) {

		if (!user_allowed_to_admin('backup works')) {
			return $template;
		}

		$merged_post_get = array_merge($_GET, $_POST);

		if (!isset($merged_post_get['module']) || ($merged_post_get['module'] != 'backup')) {
			return $template;
		}

		$input_filter = array(
			'backup_name'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\-]+(|\.zip)$~ui')),
			'backup_description' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\s\-а-яА-Я.:;"]+$~ui')),
			'action'             => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(create|restore|delete|download)+$~ui')),
			'result'             => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_]+$~ui'))
		);

		$_INPUT = get_filtered_input($input_filter);
		$result_text = 'Неизвестное действие';
		$result_class = 'backup_result_bad';
		switch ($_INPUT['action']) {
			case 'create':
				if ($_INPUT['backup_name'] == '') {
					popup_message_add('Некорректное имя файла', JCMS_MESSAGE_ERROR);
					break;
				}
				// force extension
				if (substr($_INPUT['backup_name'], -4) != '.zip') {
					$_INPUT['backup_name'] .= '.zip';
				}
				if (($result = $this->createBackup($_INPUT['backup_name'], $_INPUT['backup_description'])) === true) {
					popup_message_add('Резервная копия создана', JCMS_MESSAGE_OK);
				} else {
					popup_message_add('Не удалось создать резервную копию', JCMS_MESSAGE_ERROR);
				}	
				terminate('', 'Location: ./?module=backup&action=manage', 302);
				break;

			case 'restore':
				if (($result = $this->restoreBackup($_INPUT['backup_name'])) === true) {
					popup_message_add('Резервная копия восстановлена', JCMS_MESSAGE_OK);
				} else {
					popup_message_add('Не удалось восстановить резервную копию ('.$result.')', JCMS_MESSAGE_ERROR);
				}
				terminate('', 'Location: ./?module=backup&action=manage', 302);
				break;

			case 'delete':
				if ($this->deleteBackup($_INPUT['backup_name'])) {;
					popup_message_add('Резервная копия удалена', JCMS_MESSAGE_OK);
				} else {
					popup_message_add('Не удалось удалить резервную копию ('.$result.')', JCMS_MESSAGE_ERROR);
				}
				terminate('', 'Location: ./?module=backup&action=manage', 302);
				break;

			case 'download':
				header('HTTP/1.1 200 OK');
				header('Content-Length: '.filesize(__DIR__.'/data/'.$_INPUT['backup_name']));
				header('Content-Type: octet/stream');
				header('Content-Transfer-Encoding: 8bit');
				header('Content-Disposition: attachment; filename*=UTF-8\'\''.str_replace('+', '%20', urlencode(iconv('windows-1251', 'utf-8', $_INPUT['backup_name']))).'');
				readfile(__DIR__.'/data/'.$_INPUT['backup_name']);
				exit;
				break;
		}
		return $template;
	}

	/**
	 *
	 */
	public function adminGenerator() {

		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$root = $xml->createElement('backups');
		$xml->appendChild($root);
		$root->appendChild($xml->createElement('suggest'))->nodeValue = date('YmdHis');

		$list = scandir(__DIR__.'/data/');
		$zip = new ZipArchive();
		foreach ($list as $filename) {
			if (pathinfo($filename, PATHINFO_EXTENSION) == 'zip') {
				$zip->open(__DIR__.'/data/'.$filename);
				$description = $zip->getArchiveComment();
				$zip->close();

				$desc_node = $xml->createElement('backup');
				$desc_node->appendChild($xml->createElement('filename'))->nodeValue = iconv('windows-1251', 'utf-8', $filename);
				$desc_node->appendChild($xml->createElement('filesize'))->nodeValue = $this->fileSizeDynamic(filesize(__DIR__.'/data/'.$filename));
				$desc_node->appendChild($xml->createElement('description'))->nodeValue = $description;
				$root->appendChild($desc_node);
			}
		}
		return XSLTransform($xml->saveXML($root), __DIR__.'/form.xsl');

	}

	/**
	 * Creates the backup with given name and description
	 *
	 * @param string $name filename
	 * @param string $description
	 * @return bool true on success, false on error
	 */
	private function createBackup($name, $description = '') {
		$zip = new ZipArchive();

		// create empty array
		if ($zip->open(__DIR__.'/data/'.$name, ZipArchive::CREATE) !== true) {
			return false;
		}

		// add all files
		if (!isset($this->CONFIG['backup_list']) || !is_array($this->CONFIG['backup_list'])) {
			return false;
		}

		foreach ($this->CONFIG['backup_list'] as $item) {
			$zip->addGlob($item);
		}
		// rename files as zip thinks there are CP866 filenames but they are in 1251
		// so all cyrillic names are unreadable
		for ($i = 0; $i < $zip->numFiles; $i++) {
			if ($zip->getNameIndex($i) > '') {
				$zip->renameIndex($i, iconv(filesystem_encoding(), 'CP866', $zip->getNameIndex($i)));
			}
		}

		// ok, finished
		$zip->setArchiveComment($description);
		$zip->close();

		return true;
	}

	/**
	 * Restores data from the backup
	 *
	 * @param string $filename
	 * @return mixed true on success, error message on the first error, further processing skipped
	 */
	private function restoreBackup($filename) {
		$result = true;

		// get archive
		$zip = new ZipArchive();
		if ($zip->open(__DIR__.'/data/'.$filename) !== true) {
			return false;
		}

		
		for ($i = 0; $i < $zip->numFiles; $i++) {
			if (($filename = $zip->getNameIndex($i)) > '') {
				if (@file_put_contents('./'.iconv('CP866', filesystem_encoding(), $filename), $zip->getFromIndex($i)) === false) {
					return 'FAILED on : '.iconv('CP866', 'utf-8', $filename); // output always in UTF-8 
				};
			}
		}

		return $result;
	}

	/**
	 * Deletes backup file
	 *
	 * @param string $filename
	 * @return bool true on sucess, false on error
	 */
	private function deleteBackup($filename) {
		if (file_exists(__DIR__.'/data/'.$filename)) {
			unlink(__DIR__.'/data/'.$filename);
			return true;
		}
		return false;
	}

	/**
	 * Converts size in bytes to something like 100KB or 2MB
	 *
	 * @param int $size size in bytes
	 * @return string
	 */
	private function fileSizeDynamic($size) {
		$result = 'B';
		foreach (array('KB', 'MB', 'GB') as $try_more) {
			if (abs($size) > 1024) {
				$size = $size / 1024;
				$result = $try_more;
			}
		}
		return round($size).$result;
	}

}

?>