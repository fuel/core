<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       http://fuelphp.com
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
	 * Create a new PDO instance
	 *
	 * @param   array  array of PDO connection information
	 * @param   array  array of PDO attributes
	 * @return  PDO
	 */
	protected function _connect(array $config,  array $attrs = array())
	{
		// enable compression if needed
		if ($config['compress'])
		{
			// use client compression with mysql or mysqli (doesn't work with mysqlnd)
			$config['attrs'][\PDO::MYSQL_ATTR_COMPRESS] = true;
		}

		// add the charset to the DSN if needed
		if ($config['charset'] and strpos($config['dsn'], ';charset=') === false)
		{
			$config['dsn'] .= ';charset='.$config['charset'];
		}

		// create the PDO instance
		parent::_connect($config, $attrs);
	}

}
