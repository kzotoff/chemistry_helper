<?php

function generate_periodic_table_compact() {

    module_init('db');
    $data = CMS::$cache['db']['object']->DB->query('select * from periodic')->fetchAll(PDO::FETCH_ASSOC);
    $xml = array_to_xml($data, array('table', 'item'));
// $xml->formatOutput = true; echo '<pre>'.htmlspecialchars($xml->saveXML()); exit;
	return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/../xsl/periodic_compact.xsl');

}

function generate_periodic_table_short() {

    module_init('db');
    $data = CMS::$cache['db']['object']->DB->query('select * from periodic')->fetchAll(PDO::FETCH_ASSOC);
    $xml = array_to_xml($data, array('table', 'item'));
// $xml->formatOutput = true; echo '<pre>'.htmlspecialchars($xml->saveXML()); exit;
	return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/../xsl/periodic_short.xsl');

}
