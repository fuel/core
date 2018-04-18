<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * DBUtil Class
 *
 * @package		Fuel
 * @category	Core
 * @author		Dan Horrigan
 */
class DBUtil
{
	/**
	 * @var  string  $connection  the database connection (identifier)
	 */
	protected static $connection = null;

	/*
	 * Load the db config, the Database_Connection might not have fired jet.
	 *
	 */
	public static function _init()
	{
		\Config::load('db', true);
	}

	/**
	 * Sets the database connection to use for following DBUtil calls.
	 *
	 * @throws \FuelException
	 * @param  string  $connection  connection name, null for default
	 */
	public static function set_connection($connection)
	{
		if ($connection !== null and ! is_string($connection))
		{
			throw new \FuelException('A connection must be supplied as a string.');
		}

		static::$connection = $connection;
	}

	/**
	 * Creates a database.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $database       the database name
	 * @param   string  $charset        the character set
	 * @param   boolean $if_not_exists  whether to add an IF NOT EXISTS statement.
	 * @param   string  $db             the database connection to use
	 * @return  int     the number of affected rows
	 */
	public static function create_database($database, $charset = null, $if_not_exists = true, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'create_database',
			array(
				$database,
				$charset,
				$if_not_exists,
			)
		);
	}

	/**
	 * Drops a database.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $database   the database name
	 * @param   string  $db         the database connection to use
	 * @return  int     the number of affected rows
	 */
	public static function drop_database($database, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'drop_database',
			array(
				$database,
			)
		);
	}

	/**
	 * Drops a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  int     the number of affected rows
	 */
	public static function drop_table($table, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'drop_table',
			array(
				$table,
			)
		);
	}

	/**
	 * Renames a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  \Database_Exception
	 * @param   string  $table          the old table name
	 * @param   string  $new_table_name the new table name
	 * @param   string  $db             the database connection to use
	 * @return  int     the number of affected
	 */
	public static function rename_table($table, $new_table_name, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'rename_table',
			array(
				$table,
				$new_table_name,
			)
		);
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
	 * @param   string          $db             the database connection to use
	 * @return  int             number of affected rows.
	 */
	public static function create_table($table, $fields, $primary_keys = array(), $if_not_exists = true, $engine = false, $charset = null, $foreign_keys = array(), $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'create_table',
			array(
				$table,
				$fields,
				$primary_keys,
				$if_not_exists,
				$engine,
				$charset,
				$foreign_keys,
			)
		);
	}

	/**
	 * Adds fields to a table a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $table   the table name
	 * @param   array   $fields  the new fields
	 * @param   string  $db      the database connection to use
	 * @return  int     the number of affected
	 */
	public static function add_fields($table, $fields, $db = null)
	{
		return static::alter_fields('ADD', $table, $fields, $db);
	}

	/**
	 * Modifies fields in a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $table   the table name
	 * @param   array   $fields  the modified fields
	 * @param   string  $db      the database connection to use
	 * @return  int     the number of affected
	 */
	public static function modify_fields($table, $fields, $db = null)
	{
		return static::alter_fields('MODIFY', $table, $fields, $db);
	}

	/**
	 * Drops fields from a table a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string        $table   the table name
	 * @param   string|array  $fields  the fields
	 * @param   string        $db      the database connection to use
	 * @return  int           the number of affected
	 */
	public static function drop_fields($table, $fields, $db = null)
	{
		return static::alter_fields('DROP', $table, $fields, $db);
	}

	/**
	 * Creates an index on that table.
	 *
	 * @access  public
	 * @static
	 * @param   string  $table
	 * @param   string  $index_name
	 * @param   string  $index_columns
	 * @param   string  $index (should be 'unique', 'fulltext', 'spatial' or 'nonclustered')
	 * @param   string  $db    the database connection to use
	 * @return  bool
	 * @author  Thomas Edwards
	 */
	public static function create_index($table, $index_columns, $index_name = '', $index = '', $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'create_index',
			array(
				$table,
				$index_columns,
				$index_name,
				$index,
			)
		);
	}

	/**
	 * Drop an index from a table.
	 *
	 * @access  public
	 * @static
	 * @param   string  $table
	 * @param   string  $index_name
	 * @param   string  $db          the database connection to use
	 * @return  bool
	 * @author  Thomas Edwards
	 */
	public static function drop_index($table, $index_name, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'drop_index',
			array(
				$table,
				$index_name,
			)
		);
	}

	/**
	 * Adds a single foreign key to a table
	 *
	 * @param   string  $table          the table name
	 * @param   array   $foreign_key    a single foreign key
	 * @param   string  $db          the database connection to use
	 * @return  int     number of affected rows
	 */
	public static function add_foreign_key($table, $foreign_key, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'add_foreign_key',
			array(
				$table,
				$foreign_key,
			)
		);
	}

	/**
	 * Drops a foreign key from a table
	 *
	 * @param   string  $table      the table name
	 * @param   string  $fk_name    the foreign key name
	 * @param   string  $db          the database connection to use
	 * @return  int     number of affected rows
	 */
	public static function drop_foreign_key($table, $fk_name, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'drop_foreign_key',
			array(
				$table,
				$fk_name,
			)
		);
	}

	/**
	 * Returns string of foreign keys
	 *
	 * @throws  \Database_Exception
	 * @param   array   $foreign_keys  Array of foreign key rules
	 * @param   string  $db            the database connection to use
	 * @return  string  the formatted foreign key string
	 */
	public static function process_foreign_keys($foreign_keys, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'process_foreign_keys',
			array(
				$foreign_keys,
			)
		);
	}

	/**
	 * Truncates a table.
	 *
	 * @throws  Fuel\Database_Exception
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  int     the number of affected rows
	 */
	public static function truncate_table($table, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'truncate_table',
			array(
				$table,
			)
		);
	}

	/**
	 * Analyzes a table.
	 *
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  bool    whether the table is OK
	 */
	public static function analyze_table($table, $db = null)
	{
		return static::table_maintenance('ANALYZE TABLE', $table, $db);
	}

	/**
	 * Checks a table.
	 *
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  bool    whether the table is OK
	 */
	public static function check_table($table, $db = null)
	{
		return static::table_maintenance('CHECK TABLE', $table, $db);
	}

	/**
	 * Optimizes a table.
	 *
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  bool    whether the table has been optimized
	 */
	public static function optimize_table($table, $db = null)
	{
		return static::table_maintenance('OPTIMIZE TABLE', $table, $db);
	}

	/**
	 * Repairs a table.
	 *
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  bool    whether the table has been repaired
	 */
	public static function repair_table($table, $db = null)
	{
		return static::table_maintenance('REPAIR TABLE', $table, $db);
	}

	/**
	 * Checks if a given table exists.
	 *
	 * @throws  \Database_Exception
	 * @param   string  $table  Table name
	 * @param   string  $db     the database connection to use
	 * @return  bool
	 */
	public static function table_exists($table, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'table_exists',
			array(
				$table,
			)
		);
	}

	/**
	 * Checks if given field(s) in a given table exists.
	 *
	 * @throws  \Database_Exception
	 * @param   string          $table      Table name
	 * @param   string|array    $columns    columns to check
	 * @param   string          $db         the database connection to use
	 * @return  bool
	 */
	public static function field_exists($table, $columns, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'field_exists',
			array(
				$table,
				$columns,
			)
		);
	}

	/**
	 *
	 */
	protected static function alter_fields($type, $table, $fields, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'alter_fields',
			array(
				$type,
				$table,
				$fields,
			)
		);
	}

	/*
	 * Executes table maintenance. Will throw FuelException when the operation is not supported.
	 *
	 * @throws  FuelException
	 * @param   string  $table  the table name
	 * @param   string  $db     the database connection to use
	 * @return  bool    whether the operation has succeeded
	 */
	protected static function table_maintenance($operation, $table, $db = null)
	{
		return \Database_Connection::instance($db ? $db : static::$connection)->schema(
			'table_maintenance',
			array(
				$operation,
				$table,
			)
		);
	}
}
