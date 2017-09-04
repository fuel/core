<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2017 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Database_PDO_Result extends \Database_Result
{
	protected $_internal_row = 0;

	/**
	 * @param  array   $result
	 * @param  string  $sql
	 * @param  mixed   $as_object
	 */
	public function __construct($result, $sql, $as_object = null)
	{
		// go the generic construction processing
		parent::__construct($result, $sql, $as_object);

		// Find the number of rows in the result
		$this->_total_rows = $this->result->rowCount();
	}

	/**
	 * Result destruction cleans up all open result sets.
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		// Cached results do not use driver resources
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
		return new \Database_Result_Cached($this->result, $this->_query, $this->_as_object);
	}

	/**************************
	 * Iterable methods
	 *************************/

	/**
	 * Implements [Iterator::current], returns the current row.
	 *
	 * @return  mixed
	 */
	public function current()
	{
		if ($this->_current_row !== $this->_internal_row and ! $this->seek($this->_current_row))
		{
			return false;
		}

		// Increment internal row for optimization assuming rows are fetched in order
		$this->_internal_row++;

		// Convert the result into an array, as PDOStatement::rowCount is not reliable
		if ($this->_as_object === false)
		{
			$result = $this->result->fetch(\PDO::FETCH_ASSOC);
		}
		elseif (is_string($this->_as_object))
		{
			$result = $this->result->fetchObject($this->_as_object);
		}
		else
		{
			$result = $this->result->fetchObject();
		}

		// sanitize the data if needed
		if ($this->_sanitization_enabled)
		{
			$result = \Security::clean($result, null, 'security.output_filter');
		}

		return $result;
	}
}
