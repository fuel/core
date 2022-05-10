<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_MySQLi_Cached extends \Database_Result implements \SeekableIterator, \ArrayAccess
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
		elseif ($result instanceof \MySQLi_Result)
		{
			if ($this->_as_object === false)
			{
				$this->_results = $this->_result->fetch_all(MYSQLI_ASSOC);
			}
			elseif (is_string($this->_as_object))
			{
				$this->_results = array();
				while ($row = $this->_result->fetch_object($this->_as_object))
				{
					$this->_results[] = $row;
				}
			}
			else
			{
				$this->_results = array();
				while ($row = $this->_result->fetch_object())
				{
					$this->_results[] = $row;
				}
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
	#[\ReturnTypeWillChange]
	public function seek(/*int */$offset)/*: void*/
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
	#[\ReturnTypeWillChange]
	public function current()/*: mixed*/
	{
		if ($this->valid())
		{
			$this->_row = $this->_results[$this->_current_row];

			// sanitize the data if needed
			$this->_sanitizate();
		}
		else
		{
			// auto sanitized row in rewind()->next()
			$this->rewind();
		}

		return $this->_row;
	}

	/**
	 * Implements [Iterator::next], returns the next row.
	 *
	 * @return  mixed
	 */
	#[\ReturnTypeWillChange]
	public function next()/*: void*/
	{
		parent::next();

		$this->_row = null;

		isset($this->_results[$this->_current_row]) and $this->_row = $this->_results[$this->_current_row];

		// sanitize the data if needed
		$this->_sanitizate();

		return $this->_row;
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
	#[\ReturnTypeWillChange]
	public function offsetExists(/*mixed */$offset)/*: bool*/
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
	#[\ReturnTypeWillChange]
	public function offsetGet(/*mixed */$offset)/*: mixed*/
	{
		if ( ! $this->offsetExists($offset))
		{
			return false;
		}
		else

		$result = $this->_results[$offset];

		// sanitize the data if needed
		$this->_sanitizate();

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
	#[\ReturnTypeWillChange]
	final public function offsetSet(/*mixed */$offset, /*mixed */$value)/*: void*/
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
	#[\ReturnTypeWillChange]
	final public function offsetUnset(/*mixed */$offset)/*: void*/
	{
		throw new \FuelException('Database results are read-only');
	}
}
