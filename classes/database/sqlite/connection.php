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
	 * Create a new [Database_Query_Builder_Update].
	 *
	 *     // UPDATE users
	 *     $query = $db->update('users');
	 *
	 * @param   string  table to update
	 * @return  Database_Query_Builder_Update
	 */
	public function update($table = null)
	{
		return new Database_SQLite_Builder_Update($table);
	}

	/**
	 * Create a new [Database_Query_Builder_Delete].
	 *
	 *     // DELETE FROM users
	 *     $query = $db->delete('users');
	 *
	 * @param   string  table to delete from
	 * @return  Database_Query_Builder_Delete
	 */
	public function delete($table = null)
	{
		return new Database_SQLite_Builder_Delete($table);
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
	 * List table columns
	 *
	 * @param   string  $table  table name
	 * @param   string  $like   column name pattern
	 * @return  array   array of column structure
	 */
	public function list_columns($table, $like = null)
	{
		$query = "PRAGMA table_info('" . $this->quote_table($table) . "')";
		$q = $this->_connection->prepare($query);
		$q->execute();
		$result = $q->fetchAll();

		$count = 0;
		$columns = array();
		foreach ($result as $row)
		{
			$column = $this->datatype($row['type']);

			$column['name']             = $row['name'];
			$column['default']          = $row['dflt_value'];
			$column['data_type']        = $row['type'];
			$column['null']             = $row['notnull'];
			$column['ordinal_position'] = ++$count;
			$column['comment']          = '';
			$column['extra']            = $row['cid'];
			$column['key']              = $row['pk'];
			$column['privileges']       = '';

			$columns[$row['name']] = $column;
		}

		return $columns;
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
