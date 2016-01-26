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

class Database_Sqlsrv_Connection extends \Database_PDO_Connection
{
	/**
	 * Stores the database configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [static::instance].
	 *
	 * @param string $name
	 * @param array  $config
	 */
	protected function __construct($name, array $config)
	{
		// this driver only works on Windows
		if (php_uname('s') !== 'Windows')
		{
			throw new \Database_Exception('The "SQLSRV" database driver works only on Windows. On *nix, use the "DBLib" driver instead.');
		}

		parent::__construct($name, $config);
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
		$query = 'SELECT name FROM sys.databases db WHERE db.state = 0';

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
		// Always use system encoding for a SQL Server connection
		$this->_connection->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_SYSTEM);
	}

}
