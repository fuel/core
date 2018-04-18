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

class Database_PDO_Cached extends \Database_Result implements \SeekableIterator, \ArrayAccess
{
	/**
	 * @param  array   $result
	 * @param  string  $sql
	 * @param  mixed   $as_object
	 */
	public function __construct($result, $sql, $as_object = null)
	{
		// go the generic construction processing
		parent::__construct($result, $sql, $as_object);

		// Convert the result into an array, as PDOStatement::rowCount is not reliable
		if ($this->_as_object === false)
		{
			$this->_result = $this->result->fetchAll(\PDO::FETCH_ASSOC);
		}
		elseif (is_string($this->_as_object))
		{
			$this->_result = $this->result->fetchAll(\PDO::FETCH_CLASS, $this->_as_object);
		}
		else
		{
			$this->_result = $this->result->fetchAll(\PDO::FETCH_CLASS, 'stdClass');
		}

		// Find the number of rows in the result
		$this->_total_rows = count($this->_result);
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
	 * @return $this
	 */
	public function cached()
	{
		return $this;
	}

	/**************************
	 * SeekableIterator methods
	 *************************/

	/**
	 * @param integer $offset
	 *
	 * @return bool
	 */
	public function seek($offset)
	{
		if ( ! $this->offsetExists($offset))
		{
			return false;
		}

		$this->_current_row = $offset;

		return true;
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
		if ($this->valid())
		{
			$result = $this->_result[$this->_current_row];

			// sanitize the data if needed
			if ($this->_sanitization_enabled)
			{
				$result = \Security::clean($result, null, 'security.output_filter');
			}

			return $result;
		}
	}

	/**************************
	 * ArrayAccess methods
	 *************************/

	/**
	 * Implements [ArrayAccess::offsetExists], determines if row exists.
	 *
	 *     if (isset($result[10]))
	 *     {
	 *         // Row 10 exists
	 *     }
	 *
	 * @param integer $offset
	 *
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->_result[$offset]);
	}

	/**
	 * Implements [ArrayAccess::offsetGet], gets a given row.
	 *
	 *     $row = $result[10];
	 *
	 * @param integer $offset
	 *
	 * @return  mixed
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->offsetExists($offset))
		{
			return false;
		}
		else

		$result = $this->_result[$offset];

		// sanitize the data if needed
		if ($this->_sanitization_enabled)
		{
			$result = \Security::clean($result, null, 'security.output_filter');
		}

		return $result;
	}

	/**
	 * Implements [ArrayAccess::offsetSet], throws an error.
	 * [!!] You cannot modify a database result.
	 *
	 * @param integer $offset
	 * @param mixed   $value
	 *
	 * @throws  \FuelException
	 */
	final public function offsetSet($offset, $value)
	{
		throw new \FuelException('Database results are read-only');
	}

	/**
	 * Implements [ArrayAccess::offsetUnset], throws an error.
	 * [!!] You cannot modify a database result.
	 *
	 * @param integer $offset
	 *
	 * @throws  \FuelException
	 */
	final public function offsetUnset($offset)
	{
		throw new \FuelException('Database results are read-only');
	}
}
