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

abstract class Database_Query_Builder extends \Database_Query
{
	/**
	 * Compiles an array of JOIN statements into an SQL partial.
	 *
	 * @param   object $db    Database instance
	 * @param   array  $joins join statements
	 *
	 * @return  string
	 */
	protected function _compile_join(\Database_Connection$db, array $joins)
	{
		$statements = array();

		foreach ($joins as $join)
		{
			// Compile each of the join statements
			$statements[] = $join->compile($db);
		}

		return implode(' ', $statements);
	}

	/**
	 * Compiles an array of conditions into an SQL partial. Used for WHERE
	 * and HAVING.
	 *
	 * @param   object $db         Database instance
	 * @param   array  $conditions condition statements
	 *
	 * @return  string
	 */
	protected function _compile_conditions(\Database_Connection$db, array $conditions)
	{
		$last_condition = NULL;

		$sql = '';
		foreach ($conditions as $group)
		{
			// Process groups of conditions
			foreach ($group as $logic => $condition)
			{
				if ($condition === '(')
				{
					if ( ! empty($sql) AND $last_condition !== '(')
					{
						// Include logic operator
						$sql .= ' '.$logic.' ';
					}

					$sql .= '(';
				}
				elseif ($condition === ')')
				{
					$sql .= ')';
				}
				else
				{
					if ( ! empty($sql) AND $last_condition !== '(')
					{
						// Add the logic operator
						$sql .= ' '.$logic.' ';
					}

					// Split the condition
					list($column, $op, $value) = $condition;

					// Support DB::expr() as where clause
					if ($column instanceOf Database_Expression and $op === null and $value === null)
					{
						$sql .= (string) $column;
					}
					else
					{
						if ($value === NULL)
						{
							if ($op === '=')
							{
								// Convert "val = NULL" to "val IS NULL"
								$op = 'IS';
							}
							elseif ($op === '!=')
							{
								// Convert "val != NULL" to "valu IS NOT NULL"
								$op = 'IS NOT';
							}
						}

						// Database operators are always uppercase
						$op = strtoupper($op);

						if (($op === 'BETWEEN' OR $op === 'NOT BETWEEN') AND is_array($value))
						{
							// BETWEEN always has exactly two arguments
							list($min, $max) = $value;

							if (is_string($min) AND array_key_exists($min, $this->_parameters))
							{
								// Set the parameter as the minimum
								$min = $this->_parameters[$min];
							}

							if (is_string($max) AND array_key_exists($max, $this->_parameters))
							{
								// Set the parameter as the maximum
								$max = $this->_parameters[$max];
							}

							// Quote the min and max value
							$value = $db->quote($min).' AND '.$db->quote($max);
						}
						else
						{
							if (is_string($value) AND array_key_exists($value, $this->_parameters))
							{
								// Set the parameter as the value
								$value = $this->_parameters[$value];
							}

							// Quote the entire value normally
							$value = $db->quote($value);
						}

						// Append the statement to the query
						$sql .= $db->quote_identifier($column).' '.$op.' '.$value;
					}
				}

				$last_condition = $condition;
			}
		}

		return $sql;
	}

	/**
	 * Compiles an array of set values into an SQL partial. Used for UPDATE.
	 *
	 * @param   object $db     Database instance
	 * @param   array  $values updated values
	 *
	 * @return  string
	 */
	protected function _compile_set(\Database_Connection$db, array $values)
	{
		$set = array();
		foreach ($values as $group)
		{
			// Split the set
			list($column, $value) = $group;

			// Quote the column name
			$column = $db->quote_identifier($column);

			if (is_string($value) AND array_key_exists($value, $this->_parameters))
			{
				// Use the parameter value
				$value = $this->_parameters[$value];
			}

			$set[$column] = $column.' = '.$db->quote($value);
		}

		return implode(', ', $set);
	}

	/**
	 * Compiles an array of ORDER BY statements into an SQL partial.
	 *
	 * @param   object  $db       Database instance
	 * @param   array   $columns  sorting columns
	 *
	 * @return  string
	 */
	protected function _compile_order_by(\Database_Connection $db, array $columns)
	{
		$sort = array();

		foreach ($columns as $group)
		{
			list($column, $direction) = $group;

			$direction = strtoupper($direction);
			if ( ! empty($direction))
			{
				// Make the direction uppercase
				$direction = ' '.($direction == 'ASC' ? 'ASC' : 'DESC');
			}

			$sort[] = $db->quote_identifier($column).$direction;
		}

		return 'ORDER BY '.implode(', ', $sort);
	}

	/**
	 * Reset the current builder status.
	 *
	 * @return  $this
	 */
	abstract public function reset();
}
