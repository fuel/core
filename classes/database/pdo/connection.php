<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       http://fuelphp.com
 *
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
	 * @param string $name
	 * @param array  $config
	 */
	protected function __construct($name, array $config)
	{
		// example of constructing a custom schema driver
		# $this->_schema = new \Database_<drivername>_Schema($name, $this);

		// call the parent consructor
		parent::__construct($name, $config);

		// add default attributes and config values for those missing
		$this->_config = \Arr::merge(array(
			'attrs'        => array(
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			),
			'cached'       => false,
		), $this->_config);

		// convert generic config values to specific attributes
		if ( ! empty($this->_config['connection']['persistent']))
		{
			// Make the connection persistent
			$this->_config['attrs'][\PDO::ATTR_PERSISTENT] = true;
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

		try
		{
			// Create a new PDO connection
			$this->_connect();
		}
		catch (\PDOException $e)
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

			throw new \Database_Exception(str_replace($this->_config['connection']['password'], str_repeat('*', 10), $e->getMessage()), $e->getCode(), $e, $error_code);
		}
	}

	/**
	 * @return bool
	 */
	public function disconnect()
	{
		// destroy the PDO object
		$this->_connection = null;

		// and reset the savepoint depth
		$this->_transaction_depth = 0;

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

		if ($charset)
		{
			$this->_connection->exec('SET NAMES '.$this->quote($charset));
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
		$this->_connection or $this->connect();

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

						if ($this->_connection)
						{
							$error_code = $this->_connection->errorinfo();
							$error_code = $error_code[1];
						}
						else
						{
							$error_code = 0;
						}

						throw new \Database_Exception($e->getMessage().' with query: "'.$sql.'"', $e->getCode(), $e, $error_code);
					}
				}

				// no more attempts left, bail out
				else
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

					throw new \Database_Exception($e->getMessage().' with query: "'.$sql.'"', $e->getCode(), $e, $error_code);
				}
			}
		}
		while ($attempts-- > 0);

		// check if PDO ERROR Exceptions aren't disabled for some reason
		if ($result === false)
		{
			// and if so, fetch the error and still throw the exception
			if ($this->_connection)
			{
				$error_code = $this->_connection->errorinfo();
				$message = $error_code[2];
				$error_code = $error_code[1];
			}
			else
			{
				$error_code = 0;
				$message = 'Unknown error';
			}

			throw new \Database_Exception($message.' with query: "'.$sql.'"', $message, null, $error_code);
		}

		if (isset($benchmark))
		{
			\Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === \DB::SELECT)
		{
			// if no custom caching is given, use the global setting
			if ($caching)
			{
				// Return an iterator of results
				return new \Database_PDO_Cached($result, $sql, $as_object);
			}
			else
			{
				// Return an iterator of results
				return new \Database_PDO_Result($result, $sql, $as_object);
			}
		}
		elseif ($type === \DB::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				$this->_connection->lastInsertId(),
				$result->rowCount(),
			);
		}
		elseif ($type === \DB::UPDATE or $type === \DB::DELETE)
		{
			// Return the number of rows affected
			return $result->errorCode() === '00000' ? $result->rowCount() : -1;
		}

		return $result->errorCode() === '00000' ? true : false;
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
		$q = $this->_connection->prepare("DESCRIBE ".$this->quote_table($table));
		$q->execute();
		$result  = $q->fetchAll();
		$count   = 0;
		$columns = array();
		! is_null($like) and $like = str_replace('%', '.*', $like);
		foreach ($result as $row)
		{
			if ( ! is_null($like) and ! preg_match('#'.$like.'#', $row['Field']))
			{
				continue;
			}
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
	 * List indexes
	 *
	 * @param string $like
	 *
	 * @throws \FuelException
	 */
	public function list_indexes($table, $like = null)
	{
		throw new \FuelException('Database method '.__METHOD__.' is not supported by '.__CLASS__);
	}

	/**
	 * Returns a database cache object
	 *
	 * @param  array   $result
	 * @param  string  $sql
	 * @param  mixed   $as_object
	 *
	 * @return  Database_Cached
	 */
	public function cache($result, $sql, $as_object = null)
	{
		return new \Database_PDO_Cached($result, $sql, $as_object);
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
			if ( ! is_numeric($value))
			{
				$result = "'".str_replace("'", "''", $value)."'";
			}
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
	 * Create a new PDO instance
	 *
	 * @return  PDO
	 */
	protected function _connect()
	{
		$this->_connection = new \PDO(
			$this->_config['connection']['dsn'],
			$this->_config['connection']['username'],
			$this->_config['connection']['password'],
			$this->_config['attrs']
		);

		// set the DB charset if needed
		$this->set_charset($this->_config['charset']);
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
	protected function set_savepoint($name)
	{
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
	protected function release_savepoint($name)
	{
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
	protected function rollback_savepoint($name)
	{
		$result = $this->_connection->exec('ROLLBACK TO SAVEPOINT LEVEL'.$name);
		return $result !== false;
	}

}
