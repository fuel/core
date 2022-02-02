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

abstract class Database_Result implements \Countable, \Iterator, \Sanitization
{
	/**
	 * @var  string Executed SQL for this result
	 */
	protected $_query;

	/**
	 * @var  resource  $_result raw result resource
	 */
	protected $_result;

	/**
	 * @var  array  $_results cached result data
	 */
	protected $_results;

	/**
	 * @var  mixed  $_row current row
	 */
	protected $_row;

	/**
	 * @var  int  $_total_rows total number of rows
	 */
	protected $_total_rows  = 0;

	/**
	 * @var  int  $_current_row  current row number
	 */
	protected $_current_row = -1;

	/**
	 * @var  bool  $_as_object  return rows as an object or associative array
	 */
	protected $_as_object;

	/**
	 * @var  bool  $_sanitization_enabled  If this is a records data will be sanitized on get
	 */
	protected $_sanitization_enabled = false;

	/**
	 * Sets the total number of rows and stores the result locally.
	 *
	 * @param  mixed   $result     query result
	 * @param  string  $sql        SQL query
	 * @param  mixed   $as_object  object
	 */
	public function __construct($result, $sql, $as_object = null)
	{
		// Store the result locally
		$this->_result = $result;

		// Store the SQL locally
		$this->_query = $sql;

		if (is_object($as_object))
		{
			// Get the object class name
			$as_object = get_class($as_object);
		}

		// Results as objects or associative arrays
		$this->_as_object = $as_object;
	}

	/**
	 * Result destruction cleans up all open result sets.
	 *
	 * @return  void
	 */
	abstract public function __destruct();

	/**
	 * Get a cached database result from the current result iterator.
	 *
	 *     $cachable = serialize($result->cached());
	 *
	 * @return  Database_Result cache class
	 * @since   3.0.5
	 */
	abstract public function cached();

	/**
	 * Return all of the rows in the result as an array.
	 *
	 *     // Indexed array of all rows
	 *     $rows = $result->as_array();
	 *
	 *     // Associative array of rows by "id"
	 *     $rows = $result->as_array('id');
	 *
	 *     // Associative array of rows, "id" => "name"
	 *     $rows = $result->as_array('id', 'name');
	 *
	 * @param   string $key   column for associative keys
	 * @param   string $value column for values
	 * @return  array
	 */
	public function as_array($key = null, $value = null)
	{
		$results = array();

		if ($key === null and $value === null)
		{
			// Indexed rows

			foreach ($this as $row)
			{
				$results[] = $row;
			}
		}
		elseif ($key === null)
		{
			// Indexed columns

			if ($this->_as_object)
			{
				foreach ($this as $row)
				{
					$results[] = $row->$value;
				}
			}
			else
			{
				foreach ($this as $row)
				{
					$results[] = $row[$value];
				}
			}
		}
		elseif ($value === null)
		{
			// Associative rows

			if ($this->_as_object)
			{
				foreach ($this as $row)
				{
					$results[$row->$key] = $row;
				}
			}
			else
			{
				foreach ($this as $row)
				{
					$results[$row[$key]] = $row;
				}
			}
		}
		else
		{
			// Associative columns

			if ($this->_as_object)
			{
				foreach ($this as $row)
				{
					$results[$row->$key] = $row->$value;
				}
			}
			else
			{
				foreach ($this as $row)
				{
					$results[$row[$key]] = $row[$value];
				}
			}
		}

		$this->rewind();

		return $results;
	}

	/**
	 * Return the named column from the current row.
	 *
	 *     // Get the "id" value
	 *     $id = $result->get('id');
	 *
	 * @param   string $name    column to get
	 * @param   mixed  $default default value if the column does not exist
	 *
	 * @return  mixed
	 */
	public function get($name, $default = null)
	{
		// if out of iterator range, reset
		if ( ! $this->valid())
		{
			$this->rewind();
		}

		if ($this->_as_object)
		{
			if (isset($this->_row->$name))
			{
				// sanitize the data if needed
				if ( ! $this->_sanitization_enabled)
				{
					$result = $this->_row->$name;
				}
				else
				{
					$result = \Security::clean($this->_row->$name, null, 'security.output_filter');
				}

				return $result;
			}
		}
		else
		{
			if (isset($this->_row[$name]))
			{
				// sanitize the data if needed
				if ( ! $this->_sanitization_enabled)
				{
					$result = $this->_row[$name];
				}
				else
				{
					$result = \Security::clean($this->_row[$name], null, 'security.output_filter');
				}

				return $result;
			}
		}

		return \Fuel::value($default);
	}

	/**
	 * Enable sanitization mode in the object
	 *
	 * @return  $this
	 */
	public function sanitize()
	{
		$this->_sanitization_enabled = true;

		return $this;
	}

	/**
	 * Disable sanitization mode in the object
	 *
	 * @return  $this
	 */
	public function unsanitize()
	{
		$this->_sanitization_enabled = false;

		return $this;
	}

	/**
	 * Returns the current sanitization state of the object
	 *
	 * @return  bool
	 */
	public function sanitized()
	{
		return $this->_sanitization_enabled;
	}

	/**
	 *  sanitizates the current row
	 */
	protected function _sanitizate()
	{
		if ( ($this->_row !== null) and $this->_sanitization_enabled)
		{
			$this->_row = \Security::clean($this->_row, null, 'security.output_filter');
		}
	}
	
	/**************************
	 * Countable methods
	 *************************/

	/**
	 * Implements [Countable::count], returns the total number of rows.
	 *
	 *     echo count($result);
	 *
	 * @return  integer
	 */
	#[\ReturnTypeWillChange]
	public function count()/*: int*/
	{
		return $this->_total_rows;
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
		return $this->_row;
	}

	/**
	 * Implements [Iterator::key], returns the current row number.
	 *
	 * @return  integer
	 */
	#[\ReturnTypeWillChange]
	public function key()/*: mixed*/
	{
		return $this->_current_row;
	}

	/**
	 * Implements [Iterator::next], moves to the next row.
	 */
	#[\ReturnTypeWillChange]
	public function next()/*: void*/
	{
		++$this->_current_row;
	}

	/**
	 * Implements [Iterator::rewind], sets the current row to -1.
	 */
	#[\ReturnTypeWillChange]
	public function rewind()/*: void*/
	{
		// first row is zero, not one!
		$this->_current_row = -1;

		// advance to the first record, and load it
		$this->next();
	}

	/**
	 * Implements [Iterator::valid], checks if the current row exists.
	 *
	 * @return  boolean
	 */
	#[\ReturnTypeWillChange]
	public function valid()/*: bool*/
	{
		return $this->_current_row >= 0 and $this->_current_row < $this->_total_rows;
	}
}
