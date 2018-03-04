<?php //> Й <- UTF mark

/*

UI-related functions

*/

class J_DB_UI {

	/*
	 * Creates HTML for data-table view
	 *
	 * options supported (case sensitive)(no checks performed, just translated to XML generator)
	 *     id     : report ID to generate
	 *     config : direct report config
	 *
	 * @param array $params report identifier
	 * @param resource $DB database to use
	 * @return string report table HTML markup
	 *
	 */
	public static function generateTable($params, $DB) {

		// set method to call and call it, yeah!
		$params['method'] = 'get_report_as_xml';
		$result = J_DB::callAPI($params, $return_metadata, $DB);

		if ($return_metadata['status'] == 'OK') {

			// check for custom XSL, either in inline definition or in standard
			$xsl_filename = __DIR__.'/xsl/report.xsl';
			if (isset($params['config']) && isset($params['config']['xsl'])) {
				$xsl_filename = $params['config']['xsl'];
			}
			if (isset($params['report_id']) && isset(CMS::$R['db_api_reports'][$params['report_id']]['xsl'])) {
				$xsl_filename = CMS::$R['db_api_reports'][$params['report_id']]['xsl'];
			}
			$result = XSLTransform($result, $xsl_filename);
		} else {
			$result = '<b>[JuliaCMS][db module] error</b> : '.$result;
		}
		return $result;
	}

	/**
	 * Creates context menu HTML for given report, row and field
	 *
	 * @param string $report_id report to get menu items
	 * @param string $row_id row identifier
	 * @param string $field_name field name
	 * @param resource $DB database connection resource
	 * @return
	 *
	 */
	public static function generateContextMenu($report_id, $row_id, $field_name, $DB) {

		if (!isset(CMS::$R['db_api_reports'][$report_id])) {
			return false;
		}
		$params = array(
			'menu_items' => get_array_value(CMS::$R['db_api_reports'][$report_id], 'context_menu', array()),
			'row_id'     => $row_id,
			'field_name' => $field_name
		);

		return XSLTransform(J_DB_API::generateContextMenuXML($params, $return_metadata, $DB), __DIR__.'/xsl/contextmenu.xsl');
	}

	/**
	 * Creates comments dialog
	 *
	 */
	public static function commentsDialog($input, &$return_metadata, $DB) {
		$input['method'] = 'generate_comments_xml';
		$xml = J_DB::callAPI($input, $return_metadata, $DB);

		// replace API's XML type with realistic HTML
//		if (isset($_GET['respond-type']) && ($_GET['respond-type'] == 'integrated')) {

//		} else {
			$return_metadata['type'] = 'html';
			return XSLTransform($xml, __DIR__.'/xsl/commentbox.xsl');
//		}
	}

	/**
	 * Creates HTML delete confirmation dialog
	 *
	 */
	public static function recordDeleteConfirm($input, &$return_metadata, $DB) {

		if (!user_allowed_to_do('db.record.delete')) {
			$return_metadata['status'] = 'error';
			return 'access denied';
		}

		$confirmation_text = 'Удалить запись?';

		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$xml_root = $xml->createElement('confirmation-box');
		$xml->appendChild($xml_root);

		$xml_root->appendChild($xml->createElement('confirmation-text'))->nodeValue = $confirmation_text;

		$more = $xml->createElement('api-data');
		$more->appendChild($xml->createElement('param'))->nodeValue = 'method';
		$more->appendChild($xml->createElement('value'))->nodeValue = 'record_delete';
		$xml_root->appendChild($more);

		$more = $xml->createElement('api-data');
		$more->appendChild($xml->createElement('param'))->nodeValue = 'row_id';
		$more->appendChild($xml->createElement('value'))->nodeValue = $input['row_id'];
		$xml_root->appendChild($more);

		$more = $xml->createElement('api-data');
		$more->appendChild($xml->createElement('param'))->nodeValue = 'report_id';
		$more->appendChild($xml->createElement('value'))->nodeValue = $input['report_id'];
		$xml_root->appendChild($more);

		$result = XSLTransform($xml->saveXML(), __DIR__.'/xsl/confirmationbox.xsl');

		$return_metadata['type'] = 'html';
		return $result;
	}

	/**
	 * Creates dialog HTML for inserting a new record. really A wrapper for recordEdit, just adds a special flag
	 *
	 */
	public static function recordAdd($input, &$return_metadata, $DB) {

		$params = $input;

		// change API method
		$params['method'] = 'record_edit';

		// add a special flag
		$params['new_record'] = true;

		$result = J_DB::callAPI($params, $return_metadata, $DB);
		return $result;
	}

	/**
	 * Creates dialog HTML for editing (either creating or updating) the record
	 *
	 */
	public static function recordEdit($input, &$return_metadata, $DB) {

		if (!user_allowed_to_do('db.record.update')) {
			$return_metadata = array(
				'status'  => 'ERROR',
				'type'    => 'command',
				'command' => 'href ./?login'
			);
			return 'access denied';
		}

		if (!isset(CMS::$R['db_api_reports'][$input['report_id']])) {
			$return_metadata = array('status'=>'ERROR');
			return 'bad report index';
		}
		$report_id = $input['report_id'];
		$report_definition = CMS::$R['db_api_reports'][$report_id];


		// determine XSL filename
		if (!isset($report_definition['editor'])) {
			$xsl_filename = 'xsl/editorial.xsl';
		} else {
			$xsl_filename = $report_definition['editor'];
		}

		// now call XML generator
		$params = $input;
		$params['method'] = 'generate_editorial_xml';

		// yeah we get data XML, now transform it
		$xml = J_DB::callAPI($params, $return_metadata, $DB);
		// transform if exists, fail elsewhere
		$return_metadata['type'] = 'html';

		if (file_exists(__DIR__.'/'.$xsl_filename)) {
			$result = XSLTransform($xml, __DIR__.'/'.$xsl_filename);
		} else {
			$return_metadata['sratus'] = 'ERROR';
			$result = '<b>[JuliaCMS][db module] error</b> : XSL stylesheet "'.$xsl_filename.'" not found';
		}

		return $result;
	}



}


?>