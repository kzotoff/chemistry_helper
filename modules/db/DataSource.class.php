<?php

/**
 * Universal datasource class.
 * Provides fetcheable interface to any data structure such as PDO connection or raw data array
 *
 * Usage: 
 *
 *   init the object:
 *
 * 	$source = new DataSource( $some_array ); // init with simple array
 *    or
 * 	$source = new DataSource( $PDOCnnection->query('select fields from table') ); // init with PDO statement
 *
 *   then get data:
 * 
 * while ($data = $source->fetch()) {
 *   print_r( $data ) 
 * }
 *
 *
 */
class DataSource {

	/**
	 * Real data source (database connection, array or anything else
	 */
	private $data;

	/**
	 * Single call point for data fetching
	 */
	private $fetch_function;

	/**
	 * Init function for array data source
	 *
	 * @param array data to use
	 * @return void
	 */
	private function initWithArray( $data ) {
		$this->data = new ArrayIterator($data);
		$this->data->rewind();
		$this->fetch_function = 'fetchFromArray';
	}

	/**
	 * Init function for array data source
	 *
	 * @param array data to use
	 * @return data|false
	 */
	private function fetchFromArray( $options ) {
		if (!$this->data->valid()) {
			return false;
		}
		$result = $this->data->current();
		$this->data->next();
		return $result;
	}

	/**
	 * Init function for PDOStatement data source
	 *
	 * @param PDOStatement data to use
	 * @return void
	 */
	private function initWithPDOStatement( $data ) {
		$this->data = $data;
		$this->fetch_function = 'fetchFromPDOStatement';
	}

	/**
	 * Returns one more data record from PDOWrapper connection
	 *
	 * @param mixed $options any options for PDOWrapper->fetch
	 * @return array|false some data or false on any error
	 */
	private function fetchFromPDOStatement( $options ) {
		return $this->data->fetch( $options );
	}

	/**
	 * On create, detect data type and do appropriate init
	 */
	public function __construct($source) {

		if (is_array($source)) {
			$this->initWithArray( $source );

		} else if (is_a($source, 'PDOStatement')) {
			$this->initWithPDOStatement( $source );

		} else {
			throw new Exception('Unsupported data source type '.get_class( $source ));
			break;
		}
	}

	/**
	 * Universal fetch access point
	 *
	 * @param mixed $options any thing one may pass to fetch engine
	 * @return mixed|false data or false on error
	 */
	public function fetch( $options = false ) {
		$call_this = $this->fetch_function;
		return $this->$call_this( $options );
	}
    
    /**
     * Record count in the source NOT REALIZED YET
     *
     * @return int
     */
    public function recordCount() {
        throw new Exception('not realized yet');
    }

}

?>