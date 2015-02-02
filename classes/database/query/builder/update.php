<?php
/**
 * Database query builder for UPDATE statements.
 *
 * @package    Fuel/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */

namespace Fuel\Core;

class Database_Query_Builder_Update extends \Database_Query_Builder_Where
{
	/**
	 * @var string  $_table  table name
	 */
	protected $_table;

	/**
	 * @var array  $_set  update values
	 */
	protected $_set = array();

	/**
	 * @var array  $_join  join statements
	 */
	protected $_join = array();

	/**
	 * @var Database_Query_Builder_Join  $_last_join  last join statement
	 */
	protected $_last_join;

	/**
	 * Set the table for a update.
	 *
	 * @param  mixed  $table  table name or array($table, $alias) or object
	 *
	 * @return  void
	 */
	public function __construct($table = NULL)
	{
		if ($table)
		{
			// Set the inital table name
			$this->_table = $table;
		}

		// Start the query with no SQL
		parent::__construct('', \DB::UPDATE);
	}

	/**
	 * Sets the table to update.
	 *
	 * @param  mixed  $table  table name or array($table, $alias)
	 *
	 * @return  $this
	 */
	public function table($table)
	{
		$this->_table = $table;

		return $this;
	}

	/**
	 * Set the values to update with an associative array.
	 *
	 * @param  array  $pairs   associative (column => value) list
	 *
	 * @return  $this
	 */
	public function set(array $pairs)
	{
		foreach ($pairs as $column => $value)
		{
			$this->_set[] = array($column, $value);
		}

		return $this;
	}

	/**
	 * Set the value of a single column.
	 *
	 * @param   mixed  $column  table name or array($table, $alias) or object
	 * @param   mixed  $value   column value
	 *
	 * @return  $this
	 */
	public function value($column, $value)
	{
		$this->_set[] = array($column, $value);

		return $this;
	}

	/**
	 * Compile the SQL query and return it.
	 *
	 * @param   mixed  $db  Database instance or instance name
	 *
	 * @return  string
	 */
	public function compile($db = null)
	{
		if ( ! $db instanceof \Database_Connection)
		{
			// Get the database instance
			$db = \Database_Connection::instance($db);
		}

		// Start an update query
		$query = 'UPDATE '.$db->quote_table($this->_table);

		if ( ! empty($this->_join))
		{
			// Add tables to join
			$query .= ' '.$this->_compile_join($db, $this->_join);
		}

		// Add the columns to update
		$query .= ' SET '.$this->_compile_set($db, $this->_set);

		if ( ! empty($this->_where))
		{
			// Add selection conditions
			$query .= ' WHERE '.$this->_compile_conditions($db, $this->_where);
		}

		if ( ! empty($this->_order_by))
		{
			// Add sorting
			$query .= ' '.$this->_compile_order_by($db, $this->_order_by);
		}

		if ($this->_limit !== NULL && substr($db->_db_type, 0, 6) !== 'sqlite')
		{
			// Add limiting
			$query .= ' LIMIT '.$this->_limit;
		}

		return $query;
	}

	/**
	 * Reset the query parameters
	 *
	 * @return $this
	 */
	public function reset()
	{
		$this->_table      = null;
		$this->_join       = array();
		$this->_set        = array();
		$this->_where      = array();
		$this->_order_by   = array();
		$this->_limit      = null;
		$this->_last_join  = null;
		$this->_parameters = array();

		return $this;
	}

	/**
	 * Adds addition tables to "JOIN ...".
	 *
	 * @param   mixed   $table  column name or array($column, $alias) or object
	 * @param   string  $type   join type (LEFT, RIGHT, INNER, etc)
	 *
	 * @return  $this
	 */
	public function join($table, $type = null)
	{
		$this->_join[] = $this->_last_join = new \Database_Query_Builder_Join($table, $type);

		return $this;
	}

	/**
	 * Adds "ON ..." conditions for the last created JOIN statement.
	 *
	 * @param   mixed  $c1 column name or array($column, $alias) or object
	 * @param   string $op logic operator
	 * @param   mixed  $c2 column name or array($column, $alias) or object
	 *
	 * @return  $this
	 */
	public function on($c1, $op, $c2)
	{
		$this->_last_join->on($c1, $op, $c2);

		return $this;
	}
}
