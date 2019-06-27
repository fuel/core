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

class Database_Query_Builder_Join extends \Database_Query_Builder
{
	/**
	 * @var string  $_type  join type
	 */
	protected $_type = null;

	/**
	 * @var string  $_table  join table
	 */
	protected $_table = null;

	/**
	 * @var string  $_alias  join table alias
	 */
	protected $_alias = null;

	/**
	 * @var array  $_on  ON clauses
	 */
	protected $_on = array();

	/**
	 * Creates a new JOIN statement for a table. Optionally, the type of JOIN
	 * can be specified as the second parameter.
	 *
	 * @param   mixed  $table column name or array($column, $alias) or object
	 * @param   string $type  type of JOIN: INNER, RIGHT, LEFT, etc
	 */
	public function __construct($table, $type = null)
	{
		// Set the table and alias to JOIN on
		if (is_array($table))
		{
			$this->_table = array_shift($table);
			$this->_alias = array_shift($table);
		}
		else
		{
			$this->_table = $table;
			$this->_alias = null;
		}

		if ($type !== null)
		{
			// Set the JOIN type
			$this->_type = (string) $type;
		}
	}

	/**
	 * Adds a new OR condition for joining.
	 *
	 * @param   mixed   $c1  column name or array($column, $alias) or object
	 * @param   string  $op  logic operator
	 * @param   mixed   $c2  column name or array($column, $alias) or object
	 *
	 * @return  $this
	 */
	public function or_on($c1, $op, $c2)
	{
		$this->_on[] = array($c1, $op, $c2, 'OR');

		return $this;
	}

	/**
	 * Adds a new AND condition for joining.
	 *
	 * @param   mixed   $c1  column name or array($column, $alias) or object
	 * @param   string  $op  logic operator
	 * @param   mixed   $c2  column name or array($column, $alias) or object
	 *
	 * @return  $this
	 */
	public function on($c1, $op, $c2)
	{
		$this->_on[] = array($c1, $op, $c2, 'AND');

		return $this;
	}

	/**
	 * Adds a new AND condition for joining.
	 *
	 * @param   mixed   $c1  column name or array($column, $alias) or object
	 * @param   string  $op  logic operator
	 * @param   mixed   $c2  column name or array($column, $alias) or object
	 *
	 * @return  $this
	 */
	public function and_on($c1, $op, $c2)
	{
		return $this->on($c1, $op, $c2);
	}

	/**
	 * Adds a opening bracket.
	 *
	 * @return  $this
	 */
	public function on_open()
	{
		$this->_on[] = array('', '', '', '(');

		return $this;
	}

	/**
	 * Adds a closing bracket.
	 *
	 * @return  $this
	 */
	public function on_close()
	{
		$this->_on[] = array('', '', '', ')');

		return $this;
	}

	/**
	 * Compile the SQL partial for a JOIN statement and return it.
	 *
	 * @param   mixed  $db  Database_Connection instance or instance name
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

		if ($this->_type)
		{
			$sql = strtoupper($this->_type).' JOIN';
		}
		else
		{
			$sql = 'JOIN';
		}

		if ($this->_table instanceof \Database_Query_Builder_Select)
		{
			// Compile the subquery and add it
			$sql .= ' ('.$this->_table->compile().')';
		}
		elseif ($this->_table instanceof \Database_Expression)
		{
			// Compile the expression and add its value
			$sql .= ' ('.trim($this->_table->value(), ' ()').')';
		}
		else
		{
			// Quote the table name that is being joined
			$sql .= ' '.$db->quote_table($this->_table);
		}

		// Add the alias if needed
		if ($this->_alias)
		{
			$sql .= ' AS '.$db->quote_table($this->_alias);
		}

		$conditions = array();

		foreach ($this->_on as $condition)
		{
			// Split the condition
			list($c1, $op, $c2, $chaining) = $condition;

			$c_string = $c1 . $op . $c2;

			// Just a chaining character?
			if (empty($c_string))
			{
				$conditions[] = $chaining;
			}
			else
			{
				// Check if we have a pending bracket open
				if (end($conditions) == '(')
				{
					// Update the chain type
					$conditions[key($conditions)] = ' '.$chaining.' (';
				}
				else
				{
					// Just add chain type
					$conditions[] = ' '.$chaining.' ';
				}

				if ($op)
				{
					// Make the operator uppercase and spaced
					$op = ' '.strtoupper($op);
				}

				// Quote each of the identifiers used for the condition
				$conditions[] = $db->quote_identifier($c1).$op.' '.(is_null($c2) ? 'NULL' : $db->quote_identifier($c2));
			}
		}

		// remove the first chain type
		array_shift($conditions);

		// if there are conditions, concat the conditions "... AND ..." and glue them on...
		empty($conditions) or $sql .= ' ON ('.implode('', $conditions).')';

		return $sql;
	}

	/**
	 * Resets the join values.
	 *
	 * @return  $this
	 */
	public function reset()
	{
		$this->_type = null;
		$this->_table = null;
		$this->_alias = null;
		$this->_on = array();
	}
}
