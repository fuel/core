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

class Database_Sqlsrv_Schema extends \Database_Schema
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
		$sql  = 'SELECT DB_ID('.$this->_connection->escape($database).') as dbid;';

		try
		{
			$result = $this->_connection->query(\DB::SELECT, $sql, false);
			if ($result->count() and $result = $result->current() and ! is_null($result['dbid']))
			{
				return true;
			}
		}
		catch (\Database_Exception $e)
		{
			// check if we have a DB connection at all
			if ( ! $this->_connection->has_connection())
			{
				// if no connection could be made, re throw the exception
				throw $e;
			}
		}

		return false;
	}
}
