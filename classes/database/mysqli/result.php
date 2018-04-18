<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Database_MySQLi_Result extends \Database_Result
{
	/**
	 * Sets the total number of rows and stores the result locally.
	 *
	 * @param  mixed   $result     query result
	 * @param  string  $sql        SQL query
	 * @param  mixed   $as_object  object
	 */
	public function __construct($result, $sql, $as_object)
	{
		parent::__construct($result, $sql, $as_object);

		// Find the number of rows in the result
		$this->_total_rows = $result->num_rows;
	}

	/**
	 * Result destruction cleans up all open result sets.
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		if ($this->_result instanceof \MySQLi_Result)
		{
			$this->_result->free();
		}
	}

	/**
	 * Get a cached database result from the current result iterator.
	 *
	 *     $cachable = serialize($result->cached());
	 *
	 * @return  Database_PDO_Cached
	 */
	public function cached()
	{
		return new \Database_MySQLi_Cached($this->result, $this->_query, $this->_as_object);
	}

	/**************************
	 * Iterable methods
	 *************************/

	/**
	 * Implements [Iterator::current], returns the next row.
	 *
	 * @return  mixed
	 */
	public function current()
	{
		// Convert the result into an array, as PDOStatement::rowCount is not reliable
		if ($this->_as_object === false)
		{
			$result = $this->result->fetch_array(MYSQLI_ASSOC);
		}
		elseif (is_string($this->_as_object))
		{
			$result = $this->result->fetch_object($this->_as_object);
		}
		else
		{
			$result = $this->result->fetch_object();
		}

		// sanitize the data if needed
		if ($this->_sanitization_enabled)
		{
			$result = \Security::clean($result, null, 'security.output_filter');
		}

		return $result;
	}

}
