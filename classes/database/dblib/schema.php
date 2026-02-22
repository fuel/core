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

class Database_Dblib_Schema extends \Database_Schema
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
		throw new \FuelException('dblib pdo driver: database_exists() is not implemented yet');
	}
}
