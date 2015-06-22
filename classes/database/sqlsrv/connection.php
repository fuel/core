<?php
/**
 * SQL Server database connection.
 *
 * @package    Fuel/Database
 * @category   Drivers
 * @author     Takeshi Sakurai <sakurai@pnop.co.jp>
 * @copyright  (c) 2015 Takeshi Sakurai
 * @license    http://kohanaphp.com/license
 */

namespace Fuel\Core;

class Database_SQLSRV_Connection extends \Database_Connection
{
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
	 * @var  string  SQL Server uses a backtick for identifiers
	 */
	protected $_identifier = '"';

	/**
	 * @var  string  Which kind of DB is used
	 */
	public $_db_type = 'sqlsrv';

	public function connect()
	{
		if ($this->_connection)
		{
			return;
		}

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'database'   => '',
			'hostname'   => '',
			'port'       => '',
			'username'   => '',
			'password'   => '',
		));

		try
		{
			// Build right first argument for sqlsrv_connect()
			if ($port != '')
			{
				$hostname = $hostname.','.$port;
			}

			$this->_connection = sqlsrv_connect($hostname, array("Database" => $database,
                                                                             "UID"      => $username,
                                                                             "PWD"      => $password,
                                                                             "CharacterSet"  => $this->_config['charset']));
		}
		catch (\ErrorException $e)
		{
			// No connection exists
			$this->_connection = null;

			$errors = sqlsrv_errors();
			throw new \Database_Exception(str_replace($password, str_repeat('*', 10), $errors[0]['message']), $errors[0]['code']);
		}

		// \xFF is a better delimiter, but the PHP driver uses underscore
		$this->_connection_id = sha1($hostname.'_'.$username.'_'.$password);

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}
	}

        /**
         * Select the database
         *
         * @param   string  Database
         * @return  void
         */
        protected function _select_db($database)
        {
                // CAN NOT Use "select" on Azure SQL Database, so SKIP
        }

	/**
	 * Disconnect from the database
	 *
	 * @throws  \Exception  when the sql server database is not disconnected properly
	 */
	public function disconnect()
	{
		try
		{
			// Database is assumed disconnected
			$status = true;

			if (is_resource($this->_connection))
			{
				if ($status = sqlsrv_close($this->_connection))
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
			$status = ! is_resource($this->_connection);
		}

		return $status;
	}

        public function set_charset($charset)
        {
                // Make sure the database is connected
                $this->_connection or $this->connect();
        }

	public function query($type, $sql, $as_object)
	{
		// Make sure the database is connected
		if ($this->_connection)
		{
		}
		else
		{
			$this->connect();
		}

		// Execute the query
		if (($result = sqlsrv_query($this->_connection, $sql, array(), array('Scrollable' => SQLSRV_CURSOR_STATIC))) === false)
		{
			if (isset($benchmark))
			{
				// This benchmark is worthless
				\Profiler::delete($benchmark);
			}

			$errors = sqlsrv_errors();
			throw new \Database_Exception($errors[0]['message'].' [ '.$sql.' ]', $errors[0]['code']);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === \DB::SELECT)
		{
			// Return an iterator of results
			return new \Database_SQLSRV_Result($result, $sql, $as_object);
		}
		elseif ($type === \DB::INSERT)
		{
			// Return a list of insert id and rows created
                        $identity = 0;
                        $identity_result = sqlsrv_query($this->_connection, "SELECT SCOPE_IDENTITY()");
                        if ($identity_result !== False) {
				if ($identity_row = sqlsrv_fetch_array($identity_result)) {
					$identity = $identity_row[0];
				}
                        }
			return array(
				$identity,
				sqlsrv_rows_affected($result),
			);
		}
		elseif ($type === \DB::UPDATE or $type === \DB::DELETE)
		{
			// Return the number of rows affected
			return sqlsrv_rows_affected($result);
		}

		return $result;
	}

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
	 * @param   string  $like  pattern of table name
	 * @return  array   array of table name
	 */
	public function list_tables($like = null)
	{
		if (is_string($like))
		{
			// Search for table names
			$result = $this->query(\DB::SELECT, "SELECT name FROM sys.objects WHERE type = 'U' AND name LIKE ".$this->quote($like), false);
		}
		else
		{
			// Find all table names
			$result = $this->query(\DB::SELECT, "SELECT name FROM sys.objects WHERE type = 'U'", false);
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
			$result = $this->query(\DB::SELECT, "SELECT * FROM Sys.Columns WHERE id = object_id('" . $table . "') AND name LIKE ".$this->quote($like), false);
		}
		else
		{
			// Find all column names
			$result = $this->query(\DB::SELECT, "SELECT * FROM Sys.Columns WHERE id = object_id('" . $table . "')", false);
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

			$column['comment']      = $row['Comment'];
			$column['extra']        = $row['Extra'];
			$column['key']          = $row['Key'];
			$column['privileges']   = $row['Privileges'];

			$columns[$row['Field']] = $column;
		}

		return $columns;
	}

	/**
	 * Escape query for sql
	 *
	 * @param   mixed   $value  value of string castable
	 * @return  string  escaped sql string
	 */
	public function escape($value)
	{
                $value = $this->sqlsrv_escape($value);

		// SQL standard is to use single-quotes for all values
		return "'$value'";
	}

	public function error_info()
	{
                $errors = sqlsrv_errors();
		$errno = $errors[0]['code'];
		return array($errno, empty($errno) ? null : $errno, empty($errno) ? null : $errors[0]['message']);
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

        protected function sqlsrv_escape($data) {
		if(is_numeric($data)) {
			return $data;
		}
		return addslashes($data);
	}

}
