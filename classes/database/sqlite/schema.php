<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2026 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_SQLite_Schema extends \Database_Schema
{
	/**
	 * Generic check if a given database exists.
	 *
	 * @throws  \Database_Exception
	 * @param   string  $table  Table name
	 * @return  bool
	 */
	public function database_exists($database)
	{
		$sql  = 'PRAGMA database_list;';

		try
		{
			$result = $this->_connection->query(\DB::SELECT, $sql, false);
			foreach ($result as $row)
			{
				if ($row['name'] == $database or strtolower($row['file']) == strtolower($database))
				{
					return true;
				}
			}
			return false;
		}
		catch (\Database_Exception $e)
		{
			// check if we have a DB connection at all
			if ( ! $this->_connection->has_connection())
			{
				// if no connection could be made, re throw the exception
				throw $e;
			}

			return false;
		}
	}
}
