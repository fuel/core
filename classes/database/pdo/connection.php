<?php
/**
 * PDO database connection.
 *
 * @package    Fuel/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */

namespace Fuel\Core;


class Database_PDO_Connection extends \Database_Connection
{
	/**
	 * @var  \PDO  $_connection  raw server connection
	 */
	protected $_connection;

	/**
	 * @var  string  $_identifier  PDO uses no quoting by default for identifiers
	 */
	protected $_identifier = '';

	/**
	 * @var  string  $_db_type  which kind of DB is used
	 */
	public $_db_type = '';

	/**
	 * @param string $name
	 * @param array  $config
	 */
	protected function __construct($name, array $config)
	{
		parent::__construct($name, $config);

		if (isset($this->_config['identifier']))
		{
			// Allow the identifier to be overloaded per-connection
			$this->_identifier = (string) $this->_config['identifier'];
		}
	}

	/**
	 * Connects to the database
	 *
	 * @throws \Database_Exception
	 */
	public function connect()
	{
		if ($this->_connection)
		{
			return;
		}

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'dsn'        => '',
			'username'   => null,
			'password'   => null,
			'persistent' => false,
			'compress'   => false,
		));

		// determine db type
		$_dsn_find_collon = strpos($dsn, ':');
		$this->_db_type = $_dsn_find_collon ? substr($dsn, 0, $_dsn_find_collon) : null;

		// Force PDO to use exceptions for all errors
		$attrs = array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION);

		if ( ! empty($persistent))
		{
			// Make the connection persistent
			$attrs[\PDO::ATTR_PERSISTENT] = true;
		}

		if (in_array(strtolower($this->_db_type), array('mysql', 'mysqli')) and $compress)
		{
			// Use client compression with mysql or mysqli (doesn't work with mysqlnd)
			$attrs[\PDO::MYSQL_ATTR_COMPRESS] = true;
		}

		// add the charset to the DSN if needed
		if ( ! empty($this->_config['charset']) and strpos($dsn, ';charset=') === false and strtolower($this->_db_type) != 'sqlite')
		{
			$dsn .= ';charset='.$this->_config['charset'];
		}

		try
		{
			// Create a new PDO connection
			$this->_connection = new \PDO($dsn, $username, $password, $attrs);
		}
		catch (\PDOException $e)
		{
			// and convert the exception in a database exception
			if ( ! is_numeric($error_code = $e->getCode()))
			{
				if ($this->_connection)
				{
					$error_code = $this->_connection->errorinfo();
					$error_code = $error_code[1];
				}
				else
				{
					$error_code = 0;
				}
			}
			throw new \Database_Exception(str_replace($password, str_repeat('*', 10), $e->getMessage()), $error_code, $e);
		}

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}
	}

	/**
	 * @return bool
	 */
	public function disconnect()
	{
		// Destroy the PDO object
		$this->_connection = null;

		return true;
	}

	/**
	 * Get the current PDO Driver name
	 *
	 * @return string
	 */
	public function driver_name()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// Getting driver name
		return $this->_connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Set the charset
	 *
	 * @param string $charset
	 */
	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// Set Charset for SQL Server connection
		if (strtolower($this->driver_name()) == 'sqlsrv')
		{
			$this->_connection->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_SYSTEM);
		}
		// Set Charset for SQLite connection
		elseif (strtolower($this->driver_name()) == 'sqlite')
		{
			// Execute a raw PRAGMA encoding query
			$this->_connection->exec('PRAGMA encoding = ' . $this->quote($charset));
		}
		// Set Charset for any connection except ODBC, as it throws exception
		elseif (strtolower($this->driver_name()) != 'odbc')
		{
			// Execute a raw SET NAMES query
			$this->_connection->exec('SET NAMES '.$this->quote($charset));
		}
	}

	/**
	 * Query the database
	 *
	 * @param integer $type
	 * @param string  $sql
	 * @param mixed   $as_object
	 *
	 * @return mixed
	 *
	 * @throws \Database_Exception
	 */
	public function query($type, $sql, $as_object)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ( ! empty($this->_config['profiling']))
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
						$stacktrace[] = array('file' => Fuel::clean_path($page['file']), 'line' => $page['line']);
					}
				}
			}

			$benchmark = \Profiler::start($this->_instance, $sql, $stacktrace);
		}

		// run the query. if the connection is lost, try 3 times to reconnect
		$attempts = 3;

		do
		{
			try
			{
				// try to run the query
				$result = $this->_connection->query($sql);
				break;
			}
			catch (\Exception $e)
			{
				// if failed and we have attempts left
				if ($attempts > 0)
				{
					// try reconnecting if it was a MySQL disconnected error
					if (strpos($e->getMessage(), '2006 MySQL') !== false)
					{
						$this->disconnect();
						$this->connect();
					}
					else
					{
						// other database error, cleanup the profiler
						isset($benchmark) and  \Profiler::delete($benchmark);

						// and convert the exception in a database exception
						if ( ! is_numeric($error_code = $e->getCode()))
						{
							if ($this->_connection)
							{
								$error_code = $this->_connection->errorinfo();
								$error_code = $error_code[1];
							}
							else
							{
								$error_code = 0;
							}
						}

						throw new \Database_Exception($e->getMessage().' with query: "'.$sql.'"', $error_code, $e);
					}
				}

				// no more attempts left, bail out
				else
				{
					// and convert the exception in a database exception
					if ( ! is_numeric($error_code = $e->getCode()))
					{
						if ($this->_connection)
						{
							$error_code = $this->_connection->errorinfo();
							$error_code = $error_code[1];
						}
						else
						{
							$error_code = 0;
						}
					}
					throw new \Database_Exception($e->getMessage().' with query: "'.$sql.'"', $error_code, $e);
				}
			}
		}
		while ($attempts-- > 0);

		if (isset($benchmark))
		{
			\Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === \DB::SELECT)
		{
			// Convert the result into an array, as PDOStatement::rowCount is not reliable
			if ($as_object === false)
			{
				$result = $result->fetchAll(\PDO::FETCH_ASSOC);
			}
			elseif (is_string($as_object))
			{
				$result = $result->fetchAll(\PDO::FETCH_CLASS, $as_object);
			}
			else
			{
				$result = $result->fetchAll(\PDO::FETCH_CLASS, 'stdClass');
			}


			// Return an iterator of results
			return new \Database_Result_Cached($result, $sql, $as_object);
		}
		elseif ($type === \DB::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				$this->_connection->lastInsertId(),
				$result->rowCount(),
			);
		}
		else
		{
			// Return the number of rows affected
			return $result->errorCode() === '00000' ? $result->rowCount() : -1;
		}
	}

	/**
	 * List tables
	 *
	 * @param string $like
	 *
	 * @throws \FuelException
	 */
	public function list_tables($like = null)
	{
		throw new \FuelException('Database method '.__METHOD__.' is not supported by '.__CLASS__);
	}

	/**
	 * List table columns
	 *
	 * @param string $table
	 * @param string $like
	 *
	 * @return array
	 */
	public function list_columns($table, $like = null)
	{
		$this->_connection or $this->connect();
		$q = $this->_connection->prepare("DESCRIBE ".$table);
		$q->execute();
		$result  = $q->fetchAll();
		$count   = 0;
		$columns = array();
		! is_null($like) and $like = str_replace('%', '.*', $like);
		foreach ($result as $row)
		{
			if ( ! is_null($like) and ! preg_match('#'.$like.'#', $row['Field'])) continue;
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
							$column['collation_name'] = isset($row['Collation']) ? $row['Collation'] : null;
							break;

						case 'enum':
						case 'set':
							$column['collation_name'] = isset($row['Collation']) ? $row['Collation'] : null;
							$column['options']        = explode('\',\'', substr($length, 1, - 1));
							break;
					}
					break;
			}

			// MySQL attributes
			$column['comment']    = isset($row['Comment']) ? $row['Comment'] : null;
			$column['extra']      = $row['Extra'];
			$column['key']        = $row['Key'];
			$column['privileges'] = isset($row['Privileges']) ? $row['Privileges'] : null;

			$columns[$row['Field']] = $column;
		}

		return $columns;
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
		// try to determine the datatype
		$datatype = parent::datatype($type);

		// if not an ANSI database, assume it's string
		return empty($datatype) ? array('type' => 'string') : $datatype;
	}

	/**
	 * Escape a value
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		$result = $this->_connection->quote($value);
		// poor-mans workaround for the fact that not all drivers implement quote()
		if (empty($result))
		{
			$result = "'".str_replace("'", "''", $value)."'";
		}
		return $result;
	}

	/**
	 * Retrieve error info
	 *
	 * @return array
	 */
	public function error_info()
	{
		return $this->_connection->errorInfo();
	}

	/**
	 * Start a transaction
	 *
	 * @return bool
	 */
	protected function driver_start_transaction()
	{
		$this->_connection or $this->connect();
		return $this->_connection->beginTransaction();
	}

	/**
	 * Commit a transaction
	 *
	 * @return bool
	 */
	protected function driver_commit()
	{
		return $this->_connection->commit();
	}

	/**
	 * Rollback a transaction
	 * @return bool
	 */
	protected function driver_rollback()
	{
		return $this->_connection->rollBack();
	}

	/**
	 * Sets savepoint of the transaction
	 *
	 * @param string $name name of the savepoint
	 * @return boolean true  - savepoint was set successfully;
	 *                 false - failed to set savepoint;
	 *                 null  - RDBMS does not support savepoints
	 */
	protected function set_savepoint($name) {
		$result = $this->_connection->exec('SAVEPOINT LEVEL'.$name);
		return $result !== false;
	}

	/**
	 * Release savepoint of the transaction
	 *
	 * @param string $name name of the savepoint
	 * @return boolean true  - savepoint was set successfully;
	 *                 false - failed to set savepoint;
	 *                 null  - RDBMS does not support savepoints
	 */
	protected function release_savepoint($name) {
		$result = $this->_connection->exec('RELEASE SAVEPOINT LEVEL'.$name);
		return $result !== false;
	}

	/**
	 * Rollback savepoint of the transaction
	 *
	 * @param string $name name of the savepoint
	 * @return boolean true  - savepoint was set successfully;
	 *                 false - failed to set savepoint;
	 *                 null  - RDBMS does not support savepoints
	 */
	protected function rollback_savepoint($name) {
		$result = $this->_connection->exec('ROLLBACK TO SAVEPOINT LEVEL'.$name);
		return $result !== false;
	}

}
