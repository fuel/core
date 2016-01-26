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

class Database_SQLite_Connection extends \Database_PDO_Connection
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
		$query = 'SELECT name FROM sqlite_master WHERE type = "table" AND name != "sqlite_sequence" AND name != "geometry_columns" AND name != "spatial_ref_sys"'
             . 'UNION ALL SELECT name FROM sqlite_temp_master '
             . 'WHERE type = "table"';

		if (is_string($like))
		{
			$query .= ' AND name LIKE ' . $this->quote($like);
		}

		$query .= ' ORDER BY name';

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
			$this->_connection->exec('PRAGMA encoding = ' . $this->quote($charset));
		}
	}
}
