<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
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

		// if an array is passed, use it
		if (is_array($result))
		{
			$this->_results = $result;
		}

		// else we're getting a mysqli object. convert the result into an array
		elseif ($result instanceof \PDOStatement)
		{
			if ($this->_as_object === false)
			{
				$this->_results = $this->_result->fetchAll(\PDO::FETCH_ASSOC);
			}
			elseif (is_string($this->_as_object))
			{
				$this->_results = $this->_result->fetchAll(\PDO::FETCH_CLASS, $this->_as_object);
			}
			else
			{
				$this->_results = $this->_result->fetchAll(\PDO::FETCH_CLASS, 'stdClass');
			}
		}
		else
		{
			throw new \FuelException('Database_Cached requires database results in either an array or a database object');
		}

		$this->_total_rows = count($this->_results);
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
			$this->_row = $this->_results[$this->_current_row];

			// sanitize the data if needed
			if ($this->_sanitization_enabled)
			{
				$this->_row = \Security::clean($this->_row, null, 'security.output_filter');
			}
		}
		else
		{
			$this->rewind();
		}

		return $this->_row;
	}

	/**
	 * Implements [Iterator::next], returns the next row.
	 *
	 * @return  mixed
	 */
	public function next()
	{
		parent::next();

		isset($this->_results[$this->_current_row]) and $this->_row = $this->_results[$this->_current_row];
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
		return isset($this->_results[$offset]);
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

		$result = $this->_results[$offset];

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
