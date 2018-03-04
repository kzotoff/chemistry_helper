<?php //>
/**
 * Wrapper for PDO class with additional functionality
 *
 * A simple wrapper for using PDO, has the fully same syntax but includes
 * some special functionality like logging. Lazy - connection will not be established
 * until any query
 *
 * @package PDOWrapper
 */
class PDOWrapper {

	/**
	 * Internal storage for real PDO object, all functions will be redirected to it
	 * @name $DB
	 */
	public $DB;

	/**
	 * Database driver
	 * @name $driver
	 */
	private $driver;

	/**
	 * Server host or filename for SQLite
	 * @name $host
	 */
	private $host;

	/**
	 * Username to login as
	 * @name $login
	 */
	private $username;

	/**
	 * Password
	 * @name $password
	 */
	private $password;

	/**
	 * Database name at the server
	 * @name $database
	 */
	private $database;

	/**
	 * Various DB driver options
	 * @name $driver_options
	 */
	private $driver_options;

	/**
	 * Shows if connection established or not
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
	 * Just an object constuctor
	 *
	 * @param string $dsn information required to connect to the database
	 * @param string $username user login to use whlie connecting
	 * @param string $password password
	 * @param array  $driver_options driver-specific connection options
	 */
	function __construct($driver, $host, $username = '', $password = '', $database = '', $driver_options = array()) {

		// we use lazy connection - wrapper will connect only when connection really needed
		$this->connected = false;

		// init params
		$this->driver         = $driver;
		$this->host           = $host;
		$this->username       = $username;
		$this->password       = $password;
		$this->database       = $database;
		$this->driver_options = $driver_options;

		// assign bounaries
		switch ($driver) {
			case 'mssql':
				$this->lb = '[';
				$this->rb = ']';
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
	 * @param string $password usernames' password
	 * @param string $database database to use
	 *
	 * @return object db connection object in case of success, also exception will be thrown if failed
	 */
	private function connect() {

		if ($this->connected) {
			return;
		}

		// choose connection string first
		switch (strtoupper($this->driver)) {
			case 'SQLSRV':
				$connection_string = 'sqlsrv: Server='.$this->host.'; Database='.$this->database;
				break;
			case 'SQLITE':
				$connection_string = 'sqlite:'.$this->host;
				break;
			default:
				throw new Exception(sprintf('unsupported driver "%s"', $this->driver));
				break;
		}

		try {
			$this->DB = new PDO($connection_string, $this->username, $this->password, $this->driver_options);
			$this->connected = true;
		} catch (Exception $e) {
			trigger_error('<b>[JuliaCMS][db] ERROR:</b> database connection failed ('.$e->getMessage().')', E_USER_ERROR);
			return false;
		}

		$this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		return true;
	}

	/**
	 * Closes database connection
	 */
	public function close() {
//		$this->DB->close();
	}

	/**
	 * Sets an attribute for database connection
	 * refer to PDO::setAttribute manual for further information
	 *
	 * @param string $param
	 * @param mixed  $value
	 */
	public function setAttribute($param, $value) {
		$this->connected || $this->connect();
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
		$this->connected || $this->connect();
		return $this->DB->prepare($sql, $driver_options);
	}

	/**
	 * Sends a query to the connection, returns entire dataset for fetching
	 *
	 * @param string $sql SQL string
	 * @return PDOStatement
	 */
	public function query($sql) {
		$this->connected || $this->connect();
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
		$this->connected || $this->connect();
		$query = $this->DB->query($sql);
		if (is_string($column)) {
			if ($data = $query->fetch(PDO::FETCH_ASSOC)) {
				return $data[$column];
			}
		} else {
			if ($data = $query->fetchColumn($column)) {
				return $data;
			}
		}
		return false;
	}

	/**
	 * Executes a query against the connection, returns rows affected
	 *
	 * @param string $sql query SQL string
	 * @return int affected rows count
	 */
	public function exec($sql) {
		$this->connected || $this->connect();
		return $this->DB->exec($sql);
	}


}

?>