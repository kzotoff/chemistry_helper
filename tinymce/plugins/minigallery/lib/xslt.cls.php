<?php
	/**
	 * Class form XSLT processing, extends standart XSLTProcessor.
	 *
	 */
	class XSLTManager extends XSLTProcessor
	{
		/**
		 * XSLT DOMDocument.
		 *
		 * @var DOMDocument
		 */
		private $xslDoc;

		/**
		 * XML DOMDocument.
		 *
		 * @var DOMDocument
		 */
		private $xmlDoc;

		/**
		 * XSLTManager class constructor.
		 *
		 */
		public function __construct($commonEncoding = 'UTF-8', $xmlDoc = null)
		{
			$this->xslDoc = new DOMDocument('1.0', $commonEncoding);
			if($xmlDoc === null)
			{
				$this->xmlDoc = new DOMDocument('1.0', $commonEncoding);
			}
			else
			{
				$this->xmlDoc = $xmlDoc;
			}
		}

		/**
		 * Load XML from file or a string ($isString should be set to true when loading from string).
		 *
		 * @param string $xmlSource
		 * @param bool $isString
		 */
		public function loadXML($xmlSource, $isString = false)
		{
			$this->loadRegularXML($this->xmlDoc, $xmlSource, $isString);
		}

		/**
		 * Load XSLT from file or a string ($isString should be set to true when loading from string).
		 *
		 * @param string $xmlSource
		 * @param bool $isString
		 */
		public function loadXSLT($xsltSource, $isString = false)
		{
			$this->loadRegularXML($this->xslDoc, $xsltSource, $isString);
		}


		/**
		 * Process tranformation and get tranformation result.
		 *
		 * @return string
		 */
		public function tranform()
		{
			if(!$this->importStylesheet($this->xslDoc))
			{
				throw new ErrorMessage
				(
					__METHOD__ . '()',
					'Importing stylesheet has failed Failed!'
				);
			}
			return $this->transformToXml($this->xmlDoc);
		}

		/**
		 * Load XML from file or a string ($isString should be set to true when loading from string) into $target.
		 *
		 * @param DOMDocument $target
		 * @param string $xmlSource
		 * @param string $isString
		 */
		private function loadRegularXML(DOMDocument $target, $xmlSource, $isString = false)
		{
			$bXMLLoaded = false;

			if($isString)
			{
				if($target->loadXML($xmlSource))
				{
					$bXMLLoaded = true;
				}
			}
			else
			{
				if($target->load($xmlSource))
				{
					$bXMLLoaded = true;
				}
			}

			if(!$bXMLLoaded)
			{
				throw new ErrorMessage
				(
					__METHOD__ . '()',
					'Loading XML ' . (!$isString ? 'from file "' . $xmlSource . '" ' : '') . 'Failed!'
				);
			}
		}
	}
	
	function XSLTransform($xml, $xslt) {
		$xsl = new XSLTManager('utf-8');
		$xsl->loadXML($xml, true);
		$xsl->loadXSLT($xslt);
		return $xsl->tranform();
	}
?>