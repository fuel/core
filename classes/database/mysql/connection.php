<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_MySQL_Connection extends \Database_PDO_Connection
{
	/**
	 * List tables
	 *
	 * @param string $like
	 *
	 * @throws \FuelException
	 */
	public function list_tables($like = null)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		$query = 'SHOW TABLES';

		if (is_string($like))
		{
			$query .= ' LIKE ' . $this->quote($like);
		}

		$q = $this->_connection->prepare($query);
		$q->execute();
		$result = $q->fetchAll();

		$tables = array();
		foreach ($result as $row)
		{
			$tables[] = reset($row);
		}

		return $tables;
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
		// Make sure the database is connected
		$this->_connection or $this->connect();

		$query = 'SHOW INDEX FROM '.$this->quote_table($table);

		if (is_string($like))
		{
			$query .= ' WHERE '.$this->quote_identifier('Key_name').' LIKE ' . $this->quote($like);
		}

		$q = $this->_connection->prepare($query);
		$q->execute();
		$result = $q->fetchAll(\PDO::FETCH_ASSOC);

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
		// run the query. if the connection is lost, try 3 times to reconnect
		$attempts = 3;

		do
		{
			try
			{
				// try to run the query
				$result = parent::query($type, $sql, $as_object, $caching);
				break;
			}
			catch (\Database_Exception $e)
			{
				// if failed and we have attempts left
				// try reconnecting if it was a MySQL disconnected error
				if ($attempts > 0 and strpos($e->getMessage(), '2006 MySQL') !== false)
				{
					$this->disconnect();
					$this->connect();
				}

				// no more attempts left, or not a disconnect error, bail out
				else
				{
					throw $e;
				}
			}
		}
		while ($attempts-- > 0);

		// return the query result
		return $result;
	}

	/**
	 * Allows for driver specific additions to column definitions when calling list_columns
	 *
	 * @param   array $column generic column definitions
	 * @param   array $row    raw row data as returned by the driver
	 * @param   string $type  determined data type
	 * @param   int   $length determined field length
	 *
	 * @return  array
	 */
	protected function _list_column($column, $row, $type, $length)
	{
		// add MySQL specific attributes
		if ($column['type'] == 'int' and ! is_null($length))
		{
			$column['display'] = $length;
		}

		$column['comment'] = isset($row['Comment']) ? $row['Comment'] : null;
		$column['extra'] = $row['Extra'];
		$column['key'] = $row['Key'];
		$column['privileges'] = isset($row['Privileges']) ? $row['Privileges'] : null;

		// return the updated column data
		return $column;
	}

	/**
	 * Create a new PDO instance
	 *
	 * @return  PDO
	 */
	protected function _connect()
	{
		// enable compression if needed
		if ($this->_config['connection']['compress'])
		{
			// use client compression with mysql or mysqli (doesn't work with mysqlnd)
			if (PHP_VERSION_ID < 80500)
			{
				$this->_config['attrs'][\PDO::MYSQL_ATTR_COMPRESS] = true;
			}
			else
			{
				$this->_config['attrs'][\Pdo\Mysql::ATTR_COMPRESS] = true;
			}
		}

		// add the charset to the DSN if needed
		if ($this->_config['charset'] and strpos($this->_config['connection']['dsn'], ';charset=') === false)
		{
			$this->_config['connection']['dsn'] .= ';charset='.$this->_config['charset'];
		}

		// create the PDO instance
		parent::_connect();
	}

}
