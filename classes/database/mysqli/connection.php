<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_MySQLi_Connection extends \Database_Connection
{
	/**
	 * @var  \MySQLi  Raw server connection
	 */
	protected $_connection;

	/**
	 * @var  array  Database in use by each connection
	 */
	protected static $_current_databases = array();

	/**
	 * @var  bool  Use SET NAMES to set the character set
	 */
	protected static $_set_names;

	/**
	 * @var  string  Identifier for this connection within the PHP driver
	 */
	protected $_connection_id;

	/**
	 * @var  string  MySQL uses a backtick for identifiers
	 */
	protected $_identifier = '`';

	/**
	 * @var  string  Which kind of DB is used
	 */
	public $_db_type = 'mysql';

	/**
	 * @param string $name
	 * @param array  $config
	 */
	protected function __construct($name, array $config)
	{
		// construct a custom schema driver
//		$this->_schema = new \Database_Drivername_Schema($name, $this);

		// call the parent consructor
		parent::__construct($name, $config);

		// make sure we have all connection parameters, add defaults for those missing
		$this->_config = \Arr::merge(array(
			'connection'  => array(
				'socket'     => '',
				'port'       => '',
				'compress'   => false,
			),
			'enable_cache'   => true,
		), $this->_config);
	}

	public function connect()
	{
		if ($this->_connection)
		{
			return;
		}

		if (static::$_set_names === null)
		{
			// Determine if we can use mysqli_set_charset(), which is only
			// available on PHP 5.2.3+ when compiled against MySQL 5.0+
			static::$_set_names = ! function_exists('mysqli_set_charset');
		}

		// Extract the connection parameters, adding required variables
		extract($this->_config['connection']);

		try
		{
			if ($socket != '')
			{
				$port   = null;
			}
			elseif ($port != '')
			{
				$socket = null;
			}
			else
			{
				$socket = null;
				$port   = null;
			}

            $host = ($persistent) ? 'p:'.$hostname : $hostname;

            // Create a connection and force it to be a new link
            if ($compress)
            {
                $mysqli = mysqli_init();
                $mysqli->real_connect($host, $username, $password, $database, $port, $socket, MYSQLI_CLIENT_COMPRESS);

                $this->_connection = $mysqli;
            }
            else
            {
                $this->_connection = new \MySQLi($host, $username, $password, $database, $port, $socket);
            }

			if ($this->_connection->error)
			{
				// Unable to connect, select database, etc
				throw new \Database_Exception(str_replace($password, str_repeat('*', 10), $this->_connection->error), $this->_connection->errno, null, $this->_connection->errno);
			}
		}
		catch (\ErrorException $e)
		{
			// No connection exists
			$this->_connection = null;

			throw new \Database_Exception(str_replace($password, str_repeat('*', 10), $e->getMessage()), $e->getCode(), $e, $e->getCode());
		}

		// \xFF is a better delimiter, but the PHP driver uses underscore
		$this->_connection_id = sha1($hostname.'_'.$username.'_'.$password);

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}

		static::$_current_databases[$this->_connection_id] = $database;
	}

	/**
	 * Select the database
	 *
	 * @param   string  Database
	 * @return  void
	 */
	protected function _select_db($database)
	{
		if ($this->_config['connection']['database'] !== static::$_current_databases[$this->_connection_id])
		{
			if ($this->_connection->select_db($database) !== true)
			{
				// Unable to select database
				throw new \Database_Exception($this->_connection->error, $this->_connection->errno, null, $this->_connection->errno);
			}
		}

		static::$_current_databases[$this->_connection_id] = $database;
	}

	/**
	 * Disconnect from the database
	 *
	 * @throws  \Exception  when the mysql database is not disconnected properly
	 */
	public function disconnect()
	{
		try
		{
			// Database is assumed disconnected
			$status = true;

			if ($this->_connection instanceof \MySQLi)
			{
				if ($status = $this->_connection->close())
				{
					// clear the connection
					$this->_connection = null;

					// and reset the savepoint depth
					$this->_transaction_depth = 0;
				}

			}
		}
		catch (\Exception $e)
		{
			// Database is probably not disconnected
			$status = ! ($this->_connection instanceof \MySQLi);
		}

		return $status;
	}

	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();
		$status = $this->_connection->set_charset($charset);

		if ($status === false)
		{
			throw new \Database_Exception($this->_connection->error, $this->_connection->errno, null, $this->_connection->errno);
		}
	}

	/**
	 * Perform an SQL query of the given type.
	 *
	 *     // Make a SELECT query and use objects for results
	 *     $db->query(static::SELECT, 'SELECT * FROM groups', true);
	 *
	 *     // Make a SELECT query and use "Model_User" for the results
	 *     $db->query(static::SELECT, 'SELECT * FROM users LIMIT 1', 'Model_User');
	 *
	 * @param   integer $type       query type (\DB::SELECT, \DB::INSERT, etc.)
	 * @param   string  $sql        SQL string
	 * @param   mixed   $as_object  used when query type is SELECT
	 * @param   bool    $caching    whether or not the result should be stored in a caching iterator
	 *
 	 * @return  mixed  when SELECT then return an iterator of results,<br>
	 *                 when INSERT then return a list of insert id and rows created,<br>
	 *                 in other case return the number of rows affected
	 *
	 * @throws \Database_Exception
	 */
	public function query($type, $sql, $as_object, $caching = null)
	{
		// If no custom caching is given, use the global setting
		is_null($caching) and $caching = $this->_config['enable_cache'];

		// Make sure the database is connected
		if ($this->_connection)
		{
			// Make sure the connection is still alive
			if ( ! $this->_connection->ping())
			{
				throw new \Database_Exception($this->_connection->error.' [ '.$sql.' ]', $this->_connection->errno, null, $this->_connection->errno);
			}
		}
		else
		{
			$this->connect();
		}

		if (\Fuel::$profiling and ! empty($this->_config['profiling']))
		{
			// Get the paths defined in config
			$paths = \Config::get('profiling_paths');

			// Storage for the trace information
			$stacktrace = array();

			// Get the execution trace of this query
			$include = false;
			foreach (debug_backtrace() as $index => $page)
			{
				// Skip first entry and entries without a filename
				if ($index > 0 and empty($page['file']) === false)
				{
					// Checks to see what paths you want backtrace
					foreach($paths as $index => $path)
					{
						if (strpos($page['file'], $path) !== false)
						{
							$include = true;
							break;
						}
					}

					// Only log if no paths we defined, or we have a path match
					if ($include or empty($paths))
					{
						$stacktrace[] = array('file' => \Fuel::clean_path($page['file']), 'line' => $page['line']);
					}
				}
			}

			$benchmark = \Profiler::start($this->_instance, $sql, $stacktrace);
		}

		if ( ! empty($this->_config['connection']['persistent']) and $this->_config['connection']['database'] !== static::$_current_databases[$this->_connection_id])
		{
			// Select database on persistent connections
			$this->_select_db($this->_config['connection']['database']);
		}

		// Execute the query
		if (($result = $this->_connection->query($sql, $caching ? MYSQLI_STORE_RESULT :MYSQLI_USE_RESULT)) === false)
		{
			if (isset($benchmark))
			{
				// This benchmark is worthless
				\Profiler::delete($benchmark);
			}

			throw new \Database_Exception($this->_connection->error.' [ '.$sql.' ]', $this->_connection->errno, null, $this->_connection->errno);
		}

		// check for multiresults, we don't support those at the moment
		while($this->_connection->more_results() and $this->_connection->next_result())
		{
			if ($more_result = $this->_connection->use_result())
			{
				throw new \Database_Exception('The MySQLi driver does not support multiple resultsets', 0);
			}
		}

		if (isset($benchmark))
		{
			\Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === \DB::SELECT)
		{
			if ($caching)
			{
				// Return an iterator of results
				return new \Database_MySQLi_Cached($result, $sql, $as_object);
			}
			else
			{
				// Return an iterator of results
				return new \Database_MySQLi_Result($result, $sql, $as_object);
			}
		}
		elseif ($type === \DB::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				$this->_connection->insert_id,
				$this->_connection->affected_rows,
			);
		}
		elseif ($type === \DB::UPDATE or $type === \DB::DELETE)
		{
			// Return the number of rows affected
			return $this->_connection->affected_rows;
		}

		return $result;
	}

	/**
	 * Returns a database cache object
	 *
	 *     $db->cache($result, $sql);
	 *
	 * @param  array   $result
	 * @param  string  $sql
	 * @param  mixed   $as_object
	 *
	 * @return  Database_Cached
	 */
	public function cache($result, $sql, $as_object = null)
	{
		return new \Database_MySQLi_Cached($result, $sql, $as_object);
	}

	/**
	 * Resolve a datatype
	 *
	 * @param integer $type
	 *
	 * @return array
	 */
	public function datatype($type)
	{
		static $types = array(
			'blob'                      => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '65535'),
			'bool'                      => array('type' => 'bool'),
			'bigint unsigned'           => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
			'datetime'                  => array('type' => 'string'),
			'decimal unsigned'          => array('type' => 'float', 'exact' => true, 'min' => '0'),
			'double'                    => array('type' => 'float'),
			'double precision unsigned' => array('type' => 'float', 'min' => '0'),
			'double unsigned'           => array('type' => 'float', 'min' => '0'),
			'enum'                      => array('type' => 'string'),
			'fixed'                     => array('type' => 'float', 'exact' => true),
			'fixed unsigned'            => array('type' => 'float', 'exact' => true, 'min' => '0'),
			'float unsigned'            => array('type' => 'float', 'min' => '0'),
			'int unsigned'              => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
			'integer unsigned'          => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
			'longblob'                  => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '4294967295'),
			'longtext'                  => array('type' => 'string', 'character_maximum_length' => '4294967295'),
			'mediumblob'                => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '16777215'),
			'mediumint'                 => array('type' => 'int', 'min' => '-8388608', 'max' => '8388607'),
			'mediumint unsigned'        => array('type' => 'int', 'min' => '0', 'max' => '16777215'),
			'mediumtext'                => array('type' => 'string', 'character_maximum_length' => '16777215'),
			'national varchar'          => array('type' => 'string'),
			'numeric unsigned'          => array('type' => 'float', 'exact' => true, 'min' => '0'),
			'nvarchar'                  => array('type' => 'string'),
			'point'                     => array('type' => 'string', 'binary' => true),
			'real unsigned'             => array('type' => 'float', 'min' => '0'),
			'set'                       => array('type' => 'string'),
			'smallint unsigned'         => array('type' => 'int', 'min' => '0', 'max' => '65535'),
			'text'                      => array('type' => 'string', 'character_maximum_length' => '65535'),
			'tinyblob'                  => array('type' => 'string', 'binary' => true, 'character_maximum_length' => '255'),
			'tinyint'                   => array('type' => 'int', 'min' => '-128', 'max' => '127'),
			'tinyint unsigned'          => array('type' => 'int', 'min' => '0', 'max' => '255'),
			'tinytext'                  => array('type' => 'string', 'character_maximum_length' => '255'),
			'varchar'                   => array('type' => 'string', 'exact' => true),
			'year'                      => array('type' => 'string'),
		);

		$type = str_replace(' zerofill', '', $type);

		if (isset($types[$type]))
		{
			return $types[$type];
		}

		return parent::datatype($type);
	}

	/**
	 * List tables
	 *
	 * @param   string  $like   pattern of table name
	 * @return  array   array of table names
	 */
	public function list_tables($like = null)
	{
		if (is_string($like))
		{
			// Search for table names
			$result = $this->query(\DB::SELECT, 'SHOW TABLES LIKE '.$this->quote($like), false);
		}
		else
		{
			// Find all table names
			$result = $this->query(\DB::SELECT, 'SHOW TABLES', false);
		}

		$tables = array();
		foreach ($result as $row)
		{
			$tables[] = reset($row);
		}

		return $tables;
	}

	/**
	 * List table columns
	 *
	 * @param   string  $table  table name
	 * @param   string  $like   column name pattern
	 * @return  array   array of column structure
	 */
	public function list_columns($table, $like = null)
	{
		// Quote the table name
		$table = $this->quote_table($table);

		if (is_string($like))
		{
			// Search for column names
			$result = $this->query(\DB::SELECT, 'SHOW FULL COLUMNS FROM '.$table.' LIKE '.$this->quote($like), false);
		}
		else
		{
			// Find all column names
			$result = $this->query(\DB::SELECT, 'SHOW FULL COLUMNS FROM '.$table, false);
		}

		$count = 0;
		$columns = array();
		foreach ($result as $row)
		{
			list($type, $length) = $this->_parse_type($row['Type']);

			$column = $this->datatype($type);

			$column['name']             = $row['Field'];
			$column['default']          = $row['Default'];
			$column['data_type']        = $type;
			$column['null']             = ($row['Null'] == 'YES');
			$column['ordinal_position'] = ++$count;

			switch ($column['type'])
			{
				case 'float':
					if (isset($length))
					{
						list($column['numeric_precision'], $column['numeric_scale']) = explode(',', $length);
					}
				break;
				case 'int':
					if (isset($length))
					{
						// MySQL attribute
						$column['display'] = $length;
					}
				break;
				case 'string':
					switch ($column['data_type'])
					{
						case 'binary':
						case 'varbinary':
							$column['character_maximum_length'] = $length;
						break;

						case 'char':
						case 'varchar':
							$column['character_maximum_length'] = $length;
						case 'text':
						case 'tinytext':
						case 'mediumtext':
						case 'longtext':
							$column['collation_name'] = $row['Collation'];
						break;

						case 'enum':
						case 'set':
							$column['collation_name'] = $row['Collation'];
							$column['options'] = explode('\',\'', substr($length, 1, -1));
						break;
					}
				break;
			}

			// MySQL attributes
			$column['comment']      = $row['Comment'];
			$column['extra']        = $row['Extra'];
			$column['key']          = $row['Key'];
			$column['privileges']   = $row['Privileges'];

			$columns[$row['Field']] = $column;
		}

		return $columns;
	}

	/**
	 * List indexes
	 *
	 * @param string $like
	 *
	 * @throws \FuelException
	 */
	public function list_indexes($table, $like = null)
	{
		// Quote the table name
		$table = $this->quote_table($table);

		if (is_string($like))
		{
			// Search for index names
			$result = $this->query(\DB::SELECT, 'SHOW INDEX FROM '.$table.' WHERE '.$this->quote_identifier('Key_name').' LIKE '.$this->quote($like), false);
		}
		else
		{
			// Find all index names
			$result = $this->query(\DB::SELECT, 'SHOW INDEX FROM '.$table, false);
		}

		// unify the result
		$indexes = array();
		foreach ($result as $row)
		{
			$index = array(
				'name' => $row['Key_name'],
				'column' => $row['Column_name'],
				'order' => $row['Seq_in_index'],
				'type' => $row['Index_type'],
				'primary' => $row['Key_name'] == 'PRIMARY' ? true : false,
				'unique' => $row['Non_unique'] == 0 ? true : false,
				'null' => $row['Null'] == 'YES' ? true : false,
				'ascending' => $row['Collation'] == 'A' ? true : false,
			);

			$indexes[] = $index;
		}

		return $indexes;
	}

	/**
	 * Escape query for sql
	 *
	 * @param   mixed   $value  value of string castable
	 * @return  string  escaped sql string
	 */
	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if (($value = $this->_connection->real_escape_string((string) $value)) === false)
		{
			throw new \Database_Exception($this->_connection->error, $this->_connection->errno, null, $this->_connection->errno);
		}

		// SQL standard is to use single-quotes for all values
		return "'$value'";
	}

	public function error_info()
	{
		$errno = $this->_connection->errno;
		return array($errno, empty($errno) ? null : $errno, empty($errno) ? null : $this->_connection->error);
	}

	protected function driver_start_transaction()
	{
		$this->query(0, 'START TRANSACTION', false);
		return true;
	}

	protected function driver_commit()
	{
		$this->query(0, 'COMMIT', false);
		return true;
	}

	protected function driver_rollback()
	{
		$this->query(0, 'ROLLBACK', false);
		return true;
	}

	/**
	 * Sets savepoint of the transaction
	 *
	 * @param string $name name of the savepoint
	 * @return boolean true  - savepoint was set successfully;
	 *                 false - failed to set savepoint;
	 */
	protected function set_savepoint($name) {
		$this->query(0, 'SAVEPOINT LEVEL'.$name, false);
		return true;
	}

	/**
	 * Release savepoint of the transaction
	 *
	 * @param string $name name of the savepoint
	 * @return boolean true  - savepoint was set successfully;
	 *                 false - failed to set savepoint;
	 */
	protected function release_savepoint($name) {
		$this->query(0, 'RELEASE SAVEPOINT LEVEL'.$name, false);
		return true;
	}

	/**
	 * Rollback savepoint of the transaction
	 *
	 * @param string $name name of the savepoint
	 * @return boolean true  - savepoint was set successfully;
	 *                 false - failed to set savepoint;
	 */
	protected function rollback_savepoint($name) {
		$this->query(0, 'ROLLBACK TO SAVEPOINT LEVEL'.$name, false);
		return true;
	}

}
