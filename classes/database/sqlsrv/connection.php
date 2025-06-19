<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @author     cocteau666@gmail.com
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_Sqlsrv_Connection extends \Database_PDO_Connection
{
	/**
	 * Stores the database configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [static::instance].
	 *
	 * @param string $name
	 * @param array  $config
	 */
	protected function __construct($name, array $config)
	{
		parent::__construct($name, $config);
	}

	/**
	 * Create a new [Database_Query_Builder_Select]. Each argument will be
	 * treated as a column. To generate a `foo AS bar` alias, use an array.
	 *
	 *     // SELECT id, username
	 *     $query = $db->select('id', 'username');
	 *
	 *     // SELECT id AS user_id
	 *     $query = $db->select(array('id', 'user_id'));
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   ...
	 * @return  Database_Query_Builder_Select
	 */
	public function select($args = null)
	{
		$instance = new \Database_Sqlsrv_Builder_Select($args);
		return $instance->set_connection($this);
	}

	/**
	 * Create a new [Database_Query_Builder_Delete].
	 *
	 *     // DELETE FROM users
	 *     $query = $db->delete('users');
	 *
	 * @param   string  table to delete from
	 * @return  Database_Query_Builder_Delete
	 */
	public function delete($table = null)
	{
		$instance = new \Database_Sqlsrv_Builder_Delete($table);
		return $instance->set_connection($this);
	}

	/**
	 * List tables
	 *
	 * @param string $like
	 *
	 * @throws \FuelException
	 */
	public function list_tables($like = null)
	{
		$query = "SELECT name FROM sys.objects WHERE type = 'U' AND name != 'sysdiagrams'";

		if (is_string($like))
		{
			$query .= " AND name LIKE ".$this->quote($like);
		}

		// Find all table names
		$result = $this->query(\DB::SELECT, $query, false);

		$tables = array();
		foreach ($result as $row)
		{
			$tables[] = reset($row);
		}

		return $tables;
	}

	/**
	 * List table columns
	 *
	 * @param   string  $table  table name
	 * @param   string  $like   column name pattern
	 * @return  array   array of column structure
	 */
	public function list_columns($table, $like = null)
	{
		$query = "SELECT * FROM information_schema.columns WHERE table_name = " . $this->quote($table);

		if (is_string($like))
		{
			// Search for column names
			$query .= " AND name LIKE ".$this->quote($like);
		}

		// Find all column names
		$result = $this->query(\DB::SELECT, $query, false);

		$columns = array();
		foreach ($result as $row)
		{
			// generic values
			$column = $this->datatype($row['DATA_TYPE']);
			$column['data_type']  = $row['DATA_TYPE'];
			$column['name']  = $row['COLUMN_NAME'];
			$column['default'] = $row['COLUMN_DEFAULT'];
			$column['null'] = ($row['IS_NULLABLE'] == 'YES');
			$column['ordinal_position'] = $row['ORDINAL_POSITION'];
			$column['collation_name'] = $row['COLLATION_NAME'];

			// type specific values
			switch ($column['type'])
			{
				case 'string':
					$length = (int) $row['CHARACTER_MAXIMUM_LENGTH'];
					$length > 0 and $column['character_maximum_length'] = $length;
					break;
			}

			// deal with defaults syntax
			if ($column['default'])
			{
				if (preg_match('~^\([\(\'](.*)[\'\)]\)$~', $column['default'], $matches))
				{
					$column['default'] = $matches[1];
				}
				else
				{
					// unsupported syntax, probably a server function
					$column['default'] = null;
				}
			}

			// store the result
			$columns[$row['COLUMN_NAME']] = $column;
		}

		return $columns;
	}

	/**
	 * Set the charset
	 *
	 * @param string $charset
	 */
	public function set_charset($charset)
	{
		if ($charset == 'utf8' or $charset = 'utf-8')
		{
			// use utf8 encoding
			$this->_connection->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8);
		}
		elseif ($charset == 'system')
		{
			// use system encoding
			$this->_connection->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_SYSTEM);
		}
		elseif (is_numeric($charset))
		{
			// charset code passed directly
			$this->_connection->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, $charset);
		}
		else
		{
			// unknown charset, use the default encoding
			$this->_connection->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_DEFAULT);
		}
	}

	/**
	 * Quote a database identifier, such as a column name. Adds the
	 * table prefix to the identifier if a table name is present.
	 *
	 *     $column = $db->quote_identifier($column);
	 *
	 * You can also use SQL methods within identifiers.
	 *
	 *     // The value of "column" will be quoted
	 *     $column = $db->quote_identifier('COUNT("column")');
	 *
	 * Objects passed to this function will be converted to strings.
	 * [Database_Expression] objects will use the value of the expression.
	 * [Database_Query] objects will be compiled and converted to a sub-query.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed $value any identifier
	 *
	 * @return  string
	 *
	 * @uses    static::table_prefix
	 */
	public function quote_identifier($value)
	{
		if ($value === '*')
		{
			return $value;
		}
		elseif (is_object($value))
		{
			if ($value instanceof Database_Query)
			{
				// Create a sub-query
				return '('.$value->compile($this).')';
			}
			elseif ($value instanceof Database_Expression)
			{
				// Use a raw expression
				return $value->value();
			}
			else
			{
				// Convert the object to a string
				return $this->quote_identifier((string) $value);
			}
		}
		elseif (is_array($value))
		{
			// Separate the column and alias
			list($value, $alias) = $value;

			return $this->quote_identifier($value).' AS '.$this->quote_identifier($alias);
		}

		if (preg_match('/^(["\']).*\1$/m', $value))
		{
			return $value;
		}

		if (strpos($value, '.') !== false)
		{
			// Split the identifier into the individual parts
			// This is slightly broken, because a table or column name
			// (or user-defined alias!) might legitimately contain a period.
			$parts = explode('.', $value);

			if ($prefix = $this->table_prefix())
			{
				// Add the table prefix to the table name
				$parts[0] = $prefix.$parts[0];
			}

			// Quote each of the parts, Transact-SQL style
			return '[' . implode('].[', $parts) . ']';
		}

		// That you can simply escape the identifier by doubling
		// it is a built-in assumption which may not be valid for
		// all connection types!  However, it's true for MySQL,
		// SQLite, Postgres and other ANSI SQL-compliant DBs.
		return $this->_identifier.str_replace($this->_identifier, $this->_identifier.$this->_identifier, $value).$this->_identifier;
	}

}
