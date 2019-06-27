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
			$this->_config['attrs'][\PDO::MYSQL_ATTR_COMPRESS] = true;
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
