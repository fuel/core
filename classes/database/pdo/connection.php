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
	 * @var  \PDO  Raw server connection
	 */
	protected $_connection;

	/**
	 * @var  string  PDO uses no quoting by default for identifiers
	 */
	protected $_identifier = '';

	/**
	 * @var  bool  Allows transactions
	 */
	protected $_in_transaction = false;

	/**
	 * @var  bool  Allows nested transactions
	 */
	protected $_transaction_level = 0;

	/**
	 * @var  string  Which kind of DB is used
	 */
	public $_db_type = '';

	protected function __construct($name, array $config)
	{
		parent::__construct($name, $config);

		if (isset($this->_config['identifier']))
		{
			// Allow the identifier to be overloaded per-connection
			$this->_identifier = (string) $this->_config['identifier'];
		}
	}

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
		));

		// Clear the connection parameters for security
		$this->_config['connection'] = array();

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

		try
		{
			// Create a new PDO connection
			$this->_connection = new \PDO($dsn, $username, $password, $attrs);
		}
		catch (\PDOException $e)
		{
			throw new \Database_Exception($e->getMessage(), $e->getCode(), $e);
		}

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}
	}

	public function disconnect()
	{
		// Destroy the PDO object
		$this->_connection = null;

		return true;
	}

	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// Execute a raw SET NAMES query
		$this->_connection->exec('SET NAMES '.$this->quote($charset));
	}

	public function query($type, $sql, $as_object)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ( ! empty($this->_config['profiling']))
		{
			// Benchmark this query for the current instance
			$benchmark = \Profiler::start("Database ({$this->_instance})", $sql);
		}

		try
		{
			$result = $this->_connection->query($sql);
		}
		catch (\Exception $e)
		{
			if (isset($benchmark))
			{
				// This benchmark is worthless
				\Profiler::delete($benchmark);
			}

			// Convert the exception in a database exception
			throw new \Database_Exception($e->getMessage().' with query: "'.$sql.'"');
		}

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
				$result->setFetchMode(\PDO::FETCH_ASSOC);
			}
			elseif (is_string($as_object))
			{
				$result->setFetchMode(\PDO::FETCH_CLASS, $as_object);
			}
			else
			{
				$result->setFetchMode(\PDO::FETCH_CLASS, 'stdClass');
			}

			$result = $result->fetchAll();

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

	public function list_tables($like = null)
	{
		throw new \FuelException('Database method '.__METHOD__.' is not supported by '.__CLASS__);
	}

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
			if ( ! is_null($like) and preg_match($like, $row['Field'])) continue;
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

	public function datatype($type)
	{
		// try to determine the datatype
		$datatype = parent::datatype($type);

		// if not an ANSI database, assume it's string
		return empty($datatype) ? array('type' => 'string') : $datatype;
	}

	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->quote($value);
	}

	public function in_transaction()
	{
		return $this->_in_transaction;
	}

	public function start_transaction()
	{
		$this->_connection or $this->connect();

		if (in_array($this->_db_type, \DB::$workarounds['nested_transactions']['savepoint']) && $this->_transaction_level > 0)
		{
			$this->_connection->exec("SAVEPOINT LEVEL{$this->_transaction_level}");
			//savepoint doesn't indicate an error in any way
			$started_transaction = true;
		}
		else
		{
			if (!($started_transaction = $this->_connection->beginTransaction()) && $this->_transaction_level > 0)
			{
				//when a database, other then the ones using the savepoint workaround,
				//attempts to start a nested transaction and fails
				throw new \PDOException('PDO attempted to start a nested transaction for a database of the type: ' . $this->_db_type);
			}
		}
		$this->_in_transaction = $started_transaction;
		$this->_in_transaction && $this->_transaction_level++;
		return $this->_in_transaction;
	}

	public function commit_transaction()
	{
		$this->_transaction_level--;
		if (in_array($this->_db_type, \DB::$workarounds['nested_transactions']['savepoint']) && $this->_transaction_level != 0)
		{
			//fires an exception if such savepoint doesn't exist
			$this->_connection->exec("RELEASE SAVEPOINT LEVEL{$this->_transaction_level}");
			return true;
		}
		else
		{
			$this->_in_transaction = false;
			//when this method is called without start_transaction before that
			//_transaction_level is negative
			$this->_transaction_level = 0;
			//fires no error if called before start_transaction was called
			return $this->_connection->commit();
		}
	}

	public function rollback_transaction()
	{
		$this->_transaction_level--;

		if (in_array($this->_db_type, \DB::$workarounds['nested_transactions']['savepoint']) && $this->_transaction_level != 0)
		{
			$this->_connection->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->_transaction_level}");
			return true;
		}
		else
		{
			$this->_in_transaction = false;
			//when this method is called without start_transaction before that
			//_transaction_level is negative
			$this->_transaction_level = 0;
			//fires no error if called before start_transaction was called
			return $this->_connection->rollBack();
		}
	}

}
