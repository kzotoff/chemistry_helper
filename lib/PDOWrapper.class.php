<?php //>
/**
 * Wrapper for PDO class with additional functionality
 *
 * A simple wrapper for using PDO, has the fully same syntax but includes
 * some special functionality like logging. Lazy - connection will not be established
 * until any query
 *
 * @package PDOWrapper
 * @see Logger
 *
 */
class PDOWrapper {

	/**
	 * Internal storage for real PDO object, all functions will be redirected to it
	 *
	 * @name $DB
	 */
	public $DB;

	/**
	 * Database driver
	 *
	 * @name $driver
	 */
	private $driver;

	/**
	 * Server host or filename for SQLite. Note that it will be stored uppercase
	 *
	 * @name $host
	 */
	private $host;

	/**
	 * Username to login as
	 *
	 * @name $login
	 */
	private $username;

	/**
	 * Password
	 *
	 * @name $password
	 */
	private $password;

	/**
	 * Database name at the server
	 *
	 * @name $database
	 */
	private $database;

	/**
	 * Various DB driver options
	 *
	 * @name $driver_options
	 */
	private $driver_options;

	/**
	 * Shows if connection established or not
	 *
	 * @name $connected
	 */
	private $connected;

	/**
	 * Left field name and table name bounary (for using in constructions like "select [fieldname] from [tablename]")
	 *
	 * @name $lb;
	 */
	public $lb;

	/**
	 * Right bounary
	 *
	 * @name $rb;
	 */
	public $rb;

	/**
	 * Internal logger. Checks if logger class exists and logs a message if possible
	 *
	 * @param string $message text message to log
	 * @param int $level event level. Refer Logger manual for available levels
	 */
	private function log( $message = '', $level = '' ) {
		if (class_exists('Logger')) {
			Logger::instance()->log($message, $level?:Logger::LOG_LEVEL_MESSAGE);
		}
	}

	/**
	 * Just an object constuctor
	 *
	 * @param string $dsn information required to connect to the database
	 * @param string $username user login to use whlie connecting
	 * @param string $password password
	 * @param array  $driver_options driver-specific connection options
	 */
	function __construct($driver, $host, $username = '', $password = '', $database = '', $driver_options = array()) {

		// we use lazy connection - wrapper will connect only when connection is really needed
		$this->connected = false;

		// init params
		$this->driver         = strtoupper($driver);
		$this->host           = $host;
		$this->username       = $username;
		$this->password       = $password;
		$this->database       = $database;
		$this->driver_options = $driver_options;

		// assign bounaries
		switch ($this->driver) {
			case 'SQLSRV':
				$this->lb = '[';
				$this->rb = ']';
				break;
			case 'ODBC':
				$this->lb = '"';
				$this->rb = '"';
				break;
			default:
				$this->lb = '`';
				$this->rb = '`';
				break;
		}

	}

	/**
	 * Wrapper for PDO connector. Creates DSN by itself using driver name supplied
	 * requires locale.php for localization

	 * @param string $driver driver to be used (sqlsrv, mysql etc.)
	 * @param string $host server IP or filename
	 * @param string $username server username
	 * @param string $password username's password
	 * @param string $database database to use
	 *
	 * @return object db connection object in case of success, also exception will be thrown if failed
	 */
	private function connect() {

		if ($this->connected) {
			return;
		}

		// choose connection string first
		switch ($this->driver) {
			case 'SQLSRV':
				// note on MultipleActiveResultSets statement at the end. Removing it will generate messages on "begin transaction"
				// see "query" method comments for details
				$connection_string = 'sqlsrv:Server='.$this->host.';Database='.$this->database.';MultipleActiveResultSets=True';
				break;
			case 'ODBC':
				$connection_string = 'odbc:'.$this->host;
				break;
			case 'SQLITE':
				$connection_string = 'sqlite:'.$this->host;
				break;
			case 'MYSQL':
				$connection_string = 'mysql:host='.$this->host.';dbname='.$this->database.';';
				break;
			default:
				throw new Exception(sprintf('unsupported driver "%s"', $this->driver));
				break;
		}

		$this->log('PDOWrapper: connecting...', Logger::LOG_LEVEL_DEBUG);
		try {
			$this->DB = new PDO($connection_string, $this->username, $this->password, $this->driver_options);

			// some special actions
			switch ($this->driver) {
				case 'MYSQL':
					$this->DB->exec('set names utf8');
					break;
			}
			$this->connected = true;
			$this->log('PDOWrapper: connected!', Logger::LOG_LEVEL_DEBUG);
		} catch (Exception $e) {
			$this->log('error connecting to database: '.$e->getMessage(), Logger::LOG_LEVEL_ERROR);
			trigger_error('<b>[PDOWrapper] ERROR:</b> database connection failed ('.$e->getMessage().')', E_USER_WARNING);
			return false;
		}

		$this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		return true;
	}

	/**
	 * Sets an attribute for database connection
	 * refer to PDO::setAttribute manual for further information
	 *
	 * @param string $param
	 * @param mixed $value
	 */
	public function setAttribute($param, $value) {
		if (($this->connected || $this->connect()) == false) { return false; }
		$this->DB->setAttribute($param, $value);
	}

	/**
	 * Prepares the statement for later execution
	 *
	 * @param string $sql SQL string
	 * @param array $driver_options
	 * @return PDOStatement
	 */
	public function prepare($sql, $driver_options = array()) {
		if (($this->connected || $this->connect()) == false) { return false; }
		return $this->DB->prepare($sql, $driver_options);
	}

	/**
	 * Sends a query to the connection, returns entire dataset for fetching
	 *
	 * @param string $sql SQL string
	 * @return PDOStatement
	 */
	public function query($sql) {
		if (($this->connected || $this->connect()) == false) { return false; }
		return $this->DB->query($sql);
	}

	/**
	 * Returns a single value using query
	 *
	 * @param string $sql SQL string
	 * @param string $column column name to return (instead of the first)
	 * @return mixed
	 */
	public function querySingle($sql, $column = 0) {
		if (($this->connected || $this->connect()) == false) { return false; }
		$query = $this->DB->query($sql);
		if (is_string($column)) {
			if (($data = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
				return $data[$column];
			}
		} else {
			if (($data = $query->fetchColumn($column)) !== false) {
				return $data;
			}
		}
		return false;
	}

	/**
	 * Executes a query against the connection, returns rows affected
	 *
	 * NOTES:
	 * - exec("begin transaction") + SQLSRV driver will generate warning.
	 *   Use $->DB->beginTransaction() instead
	 *
	 *
	 * @param string $sql query SQL string
	 * @return bool|int affected rows count or false on connection fail
	 */
	public function exec($sql) {
		if (($this->connected || $this->connect()) == false) { return false; }
		return $this->DB->exec($sql);
	}

	/**
	 * Starts yet another transaction
	 *
	 * @return bool
	 */
	public function beginTransaction() {
		if (($this->connected || $this->connect()) == false) { return false; }
		return $this->DB->beginTransaction();
	}

	/**
	 * Commit transaction
	 *
	 * @return bool
	 */
	public function commit() {
		if (($this->connected || $this->connect()) == false) { return false; }
		return $this->DB->commit();
	}

	/**
	 * Returns information about last error
	 *
	 * @return array
	 */
	public function errorInfo() {
		if (($this->connected || $this->connect()) == false) { return false; }
		return $this->DB->errorInfo();
	}

	/**
	 * Converts raw DB datetime value to human-friendly format
	 *
	 * @param string $value value to convert
	 * @param string $output_format format to use on output
	 * @param string $force_format converter will not try to guess $value format and will use this
	 * @return string
	 */
	public static function datetimeToString($value, $output_format = 'Y.m.d h:i:s', $input_format = 'YYYY.MM.DD hh:mm:ss') {

		if (trim($value) == '') {
			return '';
		}

		$D = new DateTime();
		// guess input format
		if (preg_match('~(\d{4}).(\d{2}).(\d{2})\s(\d{2}).(\d{2})(.(\d{2})(.(\d{3}))?)?~', $value, $match)) { // YYYY.MM.DD hh:mm:ss
			$year   = $match[1];
			$month  = $match[2];
			$day    = $match[3];
			$hour   = $match[4];
			$minute = $match[5];
			$second = isset($match[7]) ? $match[7] : 0;
			$msec   = isset($match[9]) ? $match[9] : 0;
			$D->setDate($year, $month, $day)->setTime($hour, $minute, $second);
		} elseif (is_numeric($mod = str_replace(',', '.', $value))) { // excel format
			$D->setTimestamp($mod * 86400 - 2209161600);
		} else {
			return 'FALSE: '.$value;
		}

		return $D->format($output_format);
	}

    /**
     * Creates LIMIT 0, 100 expression for paged output
     *
     * @param int $from page start
     * @param int $count page end
     * @return string
     */
    public function createLimitSQL( $from, $count ) {

        if ($from < 0) {
            trigger_error('negative page start value, set to 0', E_USER_WARNING);
            $from = 0;
        }

        $result = '';
        switch ( $this->driver ) {
            case 'SQLSRV':
                $result .= 'offset '.$from.' row';
                break;
            case 'SQLITE':
            case 'MYSQL':
                $result .= 'limit '.$from;
                break;
        }

        if ($count > 0) {
            switch ( $this->driver ) {
                case 'SQLSRV':
                    $result .= 'fetch next '.$count.' rows only';
                    break;
                case 'SQLITE':
                case 'MYSQL':
                    $result .= ', '.$count;
                    break;
            }
        }
        
        return $result;
    }


}

?>