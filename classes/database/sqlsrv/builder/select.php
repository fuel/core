<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_Sqlsrv_Builder_Select extends \Database_Query_Builder_Select
{
	/**
	 * Compile the SQL query and return it.
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
			$db = $this->_connection ?: \Database_Connection::instance($db);
		}

		// Callback to quote identifiers
		$quote_ident = array($db, 'quote_identifier');

		// Callback to quote tables
		$quote_table = array($db, 'quote_table');

		// Start a selection query
		$query = 'SELECT ';

		if ($this->_distinct === TRUE)
		{
			// Select only unique results
			$query .= 'DISTINCT ';
		}

		if (empty($this->_select))
		{
			// Select all columns if none given
		}
		else
		{
			// Select all columns
			$query .= implode(', ', array_unique(array_map($quote_ident, $this->_select)));
		}

		if ( ! empty($this->_from))
		{
			// Set tables to select from
			$query .= ' FROM '.implode(', ', array_unique(array_map($quote_table, $this->_from)));
		}

		if ( ! empty($this->_join))
		{
			// Add tables to join
			$query .= ' '.$this->_compile_join($db, $this->_join);
		}

		if ( ! empty($this->_where))
		{
			// Add selection conditions
			$query .= ' WHERE '.$this->_compile_conditions($db, $this->_where);
		}

		if ( ! empty($this->_group_by))
		{
			// Add sorting
			$query .= ' GROUP BY '.implode(', ', array_map($quote_ident, $this->_group_by));
		}

		if ( ! empty($this->_having))
		{
			// Add filtering conditions
			$query .= ' HAVING '.$this->_compile_conditions($db, $this->_having);
		}

		if ( ! empty($this->_order_by))
		{
			// Add sorting
			$query .= ' '.$this->_compile_order_by($db, $this->_order_by);
		}

		// special handling of LIMIT
		if ($this->_limit !== NULL)
		{
			// if no order was defined
			if (empty($this->_order_by))
			{
				// add a dummy one, OFFSET-FETCH doesn't work without
				$query .= ' ORDER BY(SELECT NULL)';
			}

			// Add offsets, use zero if only a limit is given
			$query .= ' OFFSET '.(int) $this->_offset.' ROWS';

			// Add limiting
			$query .= ' FETCH NEXT '.$this->_limit.' ROWS ONLY';
		}

		return $query;
	}
}
