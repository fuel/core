<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_Schema
{
	/**
	 * @var  Database_Connection  database connection instance
	 */
	protected $_connection;

	/**
	 * @var  string  database connection config name
	 */
	protected $_name;

	/**
	 * Stores the database instance to be used.
	 *
	 * @param  string  database connection instance
	 */
	public function __construct($name, $connection)
	{
		// Set the connection config name
		$this->_name = $name;

		// Set the connection instance
		$this->_connection = $connection;
	}

	/**
	 * Creates a database.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $database       the database name
	 * @param   string  $charset        the character set
	 * @param   boolean $if_not_exists  whether to add an IF NOT EXISTS statement.
	 * @return  int     the number of affected rows
	 */
	public function create_database($database, $charset = null, $if_not_exists = true)
	{
		$sql = 'CREATE DATABASE';
		$sql .= $if_not_exists ? ' IF NOT EXISTS ' : ' ';

		$sql .= $this->_connection->quote_identifier($database).$this->process_charset($charset, true);

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Drops a database.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $database   the database name
	 * @return  int     the number of affected rows
	 */
	public function drop_database($database)
	{
		$sql = 'DROP DATABASE ';
		$sql .= $this->_connection->quote_identifier($database);

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Drops a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $table  the table name
	 * @return  int     the number of affected rows
	 */
	public function drop_table($table)
	{
		$sql = 'DROP TABLE IF EXISTS ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Renames a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  \Database_Exception
	 * @param   string  $table          the old table name
	 * @param   string  $new_table_name the new table name
	 * @return  int     the number of affected
	 */
	public function rename_table($table, $new_table_name)
	{
		$sql = 'RENAME TABLE ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));
		$sql .= ' TO ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($new_table_name));

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Creates a table.
	 *
	 * @throws  \Database_Exception
	 * @param   string          $table          the table name
	 * @param   array           $fields         the fields array
	 * @param   array           $primary_keys   an array of primary keys
	 * @param   boolean         $if_not_exists  whether to add an IF NOT EXISTS statement.
	 * @param   string|boolean  $engine         storage engine overwrite
	 * @param   string          $charset        default charset overwrite
	 * @param   array           $foreign_keys   an array of foreign keys
	 * @return  int             number of affected rows.
	 */
	public function create_table($table, $fields, $primary_keys = array(), $if_not_exists = true, $engine = false, $charset = null, $foreign_keys = array())
	{
		$sql = 'CREATE TABLE';

		$sql .= $if_not_exists ? ' IF NOT EXISTS ' : ' ';

		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table)).' (';
		$sql .= $this->process_fields($fields, '');
		if ( ! empty($primary_keys))
		{
			foreach ($primary_keys as $index => $primary_key)
			{
				$primary_keys[$index] = $this->_connection->quote_identifier($primary_key);
			}
			$sql .= ",\n\tPRIMARY KEY (".implode(', ', $primary_keys).')';
		}

		empty($foreign_keys) or $sql .= $this->process_foreign_keys($foreign_keys);

		$sql .= "\n)";
		$sql .= ($engine !== false) ? ' ENGINE = '.$engine.' ' : '';
		$sql .= $this->process_charset($charset, true).";";

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Truncates a table.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $table  the table name
	 * @return  int     the number of affected rows
	 */
	public function truncate_table($table)
	{
		$sql = 'TRUNCATE TABLE ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));

		return $this->_connection->query(\DB::DELETE, $sql, false);
	}

	/**
	 * Generic check if a given table exists.
	 *
	 * @throws  \Database_Exception
	 * @param   string  $table  Table name
	 * @return  bool
	 */
	public function table_exists($table)
	{
		$sql  = 'SELECT * FROM ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));
		$sql .= ' LIMIT 1';

		try
		{
			$this->_connection->query(\DB::SELECT, $sql, false);
			return true;
		}
		catch (\Database_Exception $e)
		{
			// check if we have a DB connection at all
			if ( ! $this->_connection->has_connection())
			{
				// if no connection could be made, re throw the exception
				throw $e;
			}

			return false;
		}
	}

	/**
	 * Checks if given field(s) in a given table exists.
	 *
	 * @throws  \Database_Exception
	 * @param   string          $table      Table name
	 * @param   string|array    $columns    columns to check
	 * @return  bool
	 */
	public function field_exists($table, $columns)
	{
		if ( ! is_array($columns))
		{
			$columns = array($columns);
		}

		$sql  = 'SELECT ';
		$sql .= implode(', ', array_unique(array_map(array($this->_connection, 'quote_identifier'), $columns)));
		$sql .= ' FROM ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));
		$sql .= ' LIMIT 1';

		try
		{
			$this->_connection->query(\DB::SELECT, $sql, false);
			return true;
		}
		catch (\Database_Exception $e)
		{
			// check if we have a DB connection at all
			if ( ! $this->_connection->has_connection())
			{
				// if no connection could be made, re throw the exception
				throw $e;
			}

			return false;
		}
	}

	/**
	 * Creates an index on that table.
	 *
	 * @access  public
	 * @param   string  $table
	 * @param   string  $index_name
	 * @param   string  $index_columns
	 * @param   string  $index (should be 'unique', 'fulltext', 'spatial' or 'nonclustered')
	 * @return  bool
	 * @author  Thomas Edwards
	 */
	public function create_index($table, $index_columns, $index_name = '', $index = '')
	{
		static $accepted_index = array('UNIQUE', 'FULLTEXT', 'SPATIAL', 'NONCLUSTERED', 'PRIMARY');

		// make sure the index type is uppercase
		$index !== '' and $index = strtoupper($index);

		if (empty($index_name))
		{
			if (is_array($index_columns))
			{
				foreach ($index_columns as $key => $value)
				{
					if (is_numeric($key))
					{
						$index_name .= ($index_name == '' ? '' : '_').$value;
					}
					else
					{
						$index_name .= ($index_name == '' ? '' : '_').str_replace(array('(', ')', ' '), '', $key);
					}
				}
			}
			else
			{
				$index_name = $index_columns;
			}
		}

		if ($index == 'PRIMARY')
		{
			$sql = 'ALTER TABLE ';
			$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));
			$sql .= ' ADD PRIMARY KEY ';
			if (is_array($index_columns))
			{
				$columns = '';
				foreach ($index_columns as $key => $value)
				{
					if (is_numeric($key))
					{
						$columns .= ($columns=='' ? '' : ', ').$this->_connection->quote_identifier($value);
					}
					else
					{
						$columns .= ($columns=='' ? '' : ', ').$this->_connection->quote_identifier($key).' '.strtoupper($value);
					}
				}
				$sql .= ' ('.$columns.')';
			}
		}
		else
		{
			$sql = 'CREATE ';

			$index !== '' and $sql .= (in_array($index, $accepted_index)) ? $index.' ' : '';

			$sql .= 'INDEX ';
			$sql .= $this->_connection->quote_identifier($index_name);
			$sql .= ' ON ';
			$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table));
			if (is_array($index_columns))
			{
				$columns = '';
				foreach ($index_columns as $key => $value)
				{
					if (is_numeric($key))
					{
						$columns .= ($columns=='' ? '' : ', ').$this->_connection->quote_identifier($value);
					}
					else
					{
						$columns .= ($columns=='' ? '' : ', ').$this->_connection->quote_identifier($key).' '.strtoupper($value);
					}
				}
				$sql .= ' ('.$columns.')';
			}
			else
			{
				$sql .= ' ('.$this->_connection->quote_identifier($index_columns).')';
			}
		}

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Drop an index from a table.
	 *
	 * @access  public
	 * @param   string  $table
	 * @param   string  $index_name
	 * @return  bool
	 * @author  Thomas Edwards
	 */
	public function drop_index($table, $index_name)
	{
		if (strtoupper($index_name) == 'PRIMARY')
		{
			$sql = 'ALTER TABLE '.$this->_connection->quote_identifier($this->_connection->table_prefix($table));
			$sql .= ' DROP PRIMARY KEY';
		}
		else
		{
			$sql = 'DROP INDEX '.$this->_connection->quote_identifier($index_name);
			$sql .= ' ON '.$this->_connection->quote_identifier($this->_connection->table_prefix($table));
		}

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Adds a single foreign key to a table
	 *
	 * @param   string  $table          the table name
	 * @param   array   $foreign_key    a single foreign key
	 * @return  int     number of affected rows
	 */
	public function add_foreign_key($table, $foreign_key)
	{
		if ( ! is_array($foreign_key))
		{
			throw new \InvalidArgumentException('Foreign key for add_foreign_key() must be specified as an array');
		}

		$sql = 'ALTER TABLE ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table)).' ';
		$sql .= 'ADD ';
		$sql .= ltrim($this->process_foreign_keys(array($foreign_key), $this->_connection), ',');

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Drops a foreign key from a table
	 *
	 * @param   string  $table      the table name
	 * @param   string  $fk_name    the foreign key name
	 * @return  int     number of affected rows
	 */
	public function drop_foreign_key($table, $fk_name)
	{
		$sql = 'ALTER TABLE ';
		$sql .= $this->_connection->quote_identifier($this->_connection->table_prefix($table)).' ';
		$sql .= 'DROP FOREIGN KEY '.$this->_connection->quote_identifier($fk_name);

		return $this->_connection->query(0, $sql, false);
	}

	/**
	 * Returns string of foreign keys
	 *
	 * @throws  \Database_Exception
	 * @param   array   $foreign_keys  Array of foreign key rules
	 * @return  string  the formatted foreign key string
	 */
	public function process_foreign_keys($foreign_keys)
	{
		if ( ! is_array($foreign_keys))
		{
			throw new \Database_Exception('Foreign keys on create_table() must be specified as an array');
		}

		$fk_list = array();

		foreach($foreign_keys as $definition)
		{
			// some sanity checks
			if (empty($definition['key']))
			{
				throw new \Database_Exception('Foreign keys on create_table() must specify a foreign key name');
			}
			if ( empty($definition['reference']))
			{
				throw new \Database_Exception('Foreign keys on create_table() must specify a foreign key reference');
			}
			if (empty($definition['reference']['table']) or empty($definition['reference']['column']))
			{
				throw new \Database_Exception('Foreign keys on create_table() must specify a reference table and column name');
			}

			$sql = '';
			! empty($definition['constraint']) and $sql .= " CONSTRAINT ".$this->_connection->quote_identifier($definition['constraint']);
			$sql .= " FOREIGN KEY (".$this->_connection->quote_identifier($definition['key']).')';
			$sql .= " REFERENCES ".$this->_connection->quote_identifier($this->_connection->table_prefix($definition['reference']['table'])).' (';
			if (is_array($definition['reference']['column']))
			{
				$sql .= implode(', ', $this->_connection->quote_identifier($definition['reference']['column']));
			}
			else
			{
				$sql .= $this->_connection->quote_identifier($definition['reference']['column']);
			}
			$sql .= ')';
			! empty($definition['on_update']) and $sql .= " ON UPDATE ".$definition['on_update'];
			! empty($definition['on_delete']) and $sql .= " ON DELETE ".$definition['on_delete'];

			$fk_list[] = "\n\t".ltrim($sql);
		}

		return ', '.implode(',', $fk_list);
	}

	/**
	 *
	 */
	public function alter_fields($type, $table, $fields)
	{
		$sql = 'ALTER TABLE '.$this->_connection->quote_identifier($this->_connection->table_prefix($table)).' ';

		if ($type === 'DROP')
		{
			if ( ! is_array($fields))
			{
				$fields = array($fields);
			}

			$drop_fields = array();
			foreach ($fields as $field)
			{
				$drop_fields[] = 'DROP '.$this->_connection->quote_identifier($field);
			}
			$sql .= implode(', ', $drop_fields);
		}
		else
		{
			$use_brackets = ! in_array($type, array('ADD', 'CHANGE', 'MODIFY'));
			$use_brackets and $sql .= $type.' ';
			$use_brackets and $sql .= '(';
			$sql .= $this->process_fields($fields, (( ! $use_brackets) ? $type.' ' : ''));
			$use_brackets and $sql .= ')';
		}

		return $this->_connection->query(0, $sql, false);
	}

	/*
	 * Executes table maintenance. Will throw FuelException when the operation is not supported.
	 *
	 * @throws  FuelException
	 * @param   string  $table  the table name
	 * @return  bool    whether the operation has succeeded
	 */
	public function table_maintenance($operation, $table)
	{
		$sql = $operation.' '.$this->_connection->quote_identifier($this->_connection->table_prefix($table));
		$result = $this->_connection->query(\DB::SELECT, $sql, false);

		$type = $result->get('Msg_type');
		$message = $result->get('Msg_text');
		$table = $result->get('Table');

		if ($type === 'status' and in_array(strtolower($message), array('ok', 'table is already up to date')))
		{
			return true;
		}

		// make sure we have a type logger can handle
		if (in_array($type, array('info', 'warning', 'error')))
		{
			$type = strtoupper($type);
		}
		else
		{
			$type = \Fuel::L_INFO;
		}

		logger($type, 'Table: '.$table.', Operation: '.$operation.', Message: '.$result->get('Msg_text'), 'DBUtil::table_maintenance');

		return false;
	}

	/**
	 * Formats the default charset.
	 *
	 * @param    string    $charset       the character set
	 * @param    bool      $is_default    whether to use default
	 * @param    string    $collation     the collating sequence to be used
	 * @return   string    the formatted charset sql
	 */
	protected function process_charset($charset = null, $is_default = false, $collation = null)
	{
		$charset or $charset = \Config::get('db.'.$this->_name.'.charset', null);

		if (empty($charset))
		{
			return '';
		}

		$collation or $collation = \Config::get('db.'.$this->_name.'.collation', null);

		if (empty($collation) and ($pos = stripos($charset, '_')) !== false)
		{
			$collation = $charset;
			$charset = substr($charset, 0, $pos);
		}

		$charset = ' CHARACTER SET '.$charset;

		if ($is_default)
		{
			$charset = ' DEFAULT '.$charset;
		}

		if ( ! empty($collation))
		{
			if ($is_default)
			{
				$charset .= ' DEFAULT';
			}
			$charset .= ' COLLATE '.$collation;
		}

		return $charset;
	}

	/**
	 *
	 */
	protected function process_fields($fields, $prefix = '')
	{
		$sql_fields = array();

		foreach ($fields as $field => $attr)
		{
			$attr = array_change_key_case($attr, CASE_UPPER);
			$_prefix = $prefix;
			if(array_key_exists('NAME', $attr) and $field !== $attr['NAME'] and $_prefix === 'MODIFY ')
			{
				$_prefix = 'CHANGE ';
			}
			$sql = "\n\t".$_prefix;
			$sql .= $this->_connection->quote_identifier($field);
			$sql .= (array_key_exists('NAME', $attr) and $attr['NAME'] !== $field) ? ' '.$this->_connection->quote_identifier($attr['NAME']).' ' : '';
			$sql .= array_key_exists('TYPE', $attr) ? ' '.$attr['TYPE'] : '';

			if(array_key_exists('CONSTRAINT', $attr))
			{
				if(is_array($attr['CONSTRAINT']))
				{
					$sql .= "(";
					foreach($attr['CONSTRAINT'] as $constraint)
					{
						$sql .= (is_string($constraint) ? "'".$constraint."'" : $constraint).", ";
					}
					$sql = rtrim($sql, ', '). ")";
				}
				else
				{
					$sql .= '('.$attr['CONSTRAINT'].')';
				}
			}

			$sql .= array_key_exists('CHARSET', $attr) ? $this->process_charset($attr['CHARSET'], false) : '';

			if (array_key_exists('UNSIGNED', $attr) and $attr['UNSIGNED'] === true)
			{
				$sql .= ' UNSIGNED';
			}

			if(array_key_exists('DEFAULT', $attr))
			{
				$sql .= ' DEFAULT '.(($attr['DEFAULT'] instanceof \Database_Expression) ? $attr['DEFAULT']  : $this->_connection->quote($attr['DEFAULT']));
			}

			if(array_key_exists('NULL', $attr) and $attr['NULL'] === true)
			{
				$sql .= ' NULL';
			}
			else
			{
				$sql .= ' NOT NULL';
			}

			if (array_key_exists('AUTO_INCREMENT', $attr) and $attr['AUTO_INCREMENT'] === true)
			{
				$sql .= ' AUTO_INCREMENT';
			}

			if (array_key_exists('PRIMARY_KEY', $attr) and $attr['PRIMARY_KEY'] === true)
			{
				$sql .= ' PRIMARY KEY';
			}

			if (array_key_exists('COMMENT', $attr))
			{
				$sql .= ' COMMENT '.$this->_connection->escape($attr['COMMENT']);
			}

			if (array_key_exists('FIRST', $attr) and $attr['FIRST'] === true)
			{
				$sql .= ' FIRST';
			}
			elseif (array_key_exists('AFTER', $attr) and strval($attr['AFTER']))
			{
				$sql .= ' AFTER '.$this->_connection->quote_identifier($attr['AFTER']);
			}

			$sql_fields[] = $sql;
		}

		return implode(',', $sql_fields);
	}

}
