<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
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
class DBUtil {

	/**
	 * Creates a database.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string	$database	the database name
	 * @param	string	$database	the character set
	 * @return	int		the number of affected rows
	 */
	public static function create_database($database, $charset = null)
	{
		$charset or $charset = \Config::get('db.default_charset', '');
		
		if( ! empty($charset))
		{
			if(stripos($charset, '_') !== false)
			{
				$charset = ' DEFAULT CHARACTER SET '.substr($charset, 0, stripos($charset, '_')).' COLLATE '.$charset;
			}
			else
			{
				$charset = ' DEFAULT CHARACTER SET '.$charset;
			}
		}
		return DB::query('CREATE DATABASE '.DB::quote_identifier($database), \DB::UPDATE)->execute();
	}

	/**
	 * Drops a database.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string	$database	the database name
	 * @return	int		the number of affected rows
	 */
	public static function drop_database($database)
	{
		return DB::query('DROP DATABASE '.DB::quote_identifier($database), \DB::DELETE)->execute();
	}

	/**
	 * Drops a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string	$table	the table name
	 * @return	int		the number of affected rows
	 */
	public static function drop_table($table)
	{
		return DB::query('DROP TABLE IF EXISTS '.DB::quote_identifier(DB::table_prefix($table)), \DB::DELETE)->execute();
	}

	/**
	 * Renames a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string	$table			the old table name
	 * @param	string	$new_table_name	the new table name
	 * @return	int		the number of affected
	 */
	public static function rename_table($table, $new_table_name)
	{
		return DB::query('RENAME TABLE '.DB::quote_identifier(DB::table_prefix($table)).' TO '.DB::quote_identifier(DB::table_prefix($new_table_name)),DB::UPDATE)->execute();
	}

	public static function create_table($table, $fields, $primary_keys = array(), $if_not_exists = true, $engine = false)
	{
		$sql = 'CREATE TABLE';

		$sql .= $if_not_exists ? ' IF NOT EXISTS ' : ' ';

		$sql .= DB::quote_identifier(DB::table_prefix($table)).' (';
		$sql .= static::process_fields($fields);
		if ( ! empty($primary_keys))
		{
			$key_name = DB::quote_identifier(implode('_', $primary_keys));
			$primary_keys = DB::quote_identifier($primary_keys);
			$sql .= ",\n\tPRIMARY KEY ".$key_name." (" . implode(', ', $primary_keys) . ")";
		}
		$engine = ($engine !== false) ? ' ENGINE = '.$engine.' ' : '';
		$sql .= "\n)".$engine.";";

		return DB::query($sql, DB::UPDATE)->execute();
	}
		
	/**
	 * Adds fields to a table a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string	$table			the table name
	 * @param	array	$fields			the new fields
	 * @return	int		the number of affected
	 */
	public static function add_fields($table, $fields)
	{
		return static::alter_fields('ADD', $table, $fields);
	}

	/**
	 * Modifies fields in a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string	$table			the table name
	 * @param	array	$fields			the modified fields
	 * @return	int		the number of affected
	 */
	public static function modify_fields($table, $fields)
	{
		return static::alter_fields('CHANGE', $table, $fields);
	}
	
	/**
	 * Drops fields from a table a table.  Will throw a Database_Exception if it cannot.
	 *
	 * @throws	Fuel\Database_Exception
	 * @param	string			$table			the table name
	 * @param	string|array	$fields			the fields
	 * @return	int				the number of affected
	 */
	public static function drop_fields($table, $fields)
	{
		return static::alter_fields('DROP', $table, $fields);
	}

	protected static function alter_fields($type, $table, $fields)
	{
		$sql = 'ALTER TABLE '.DB::quote_identifier(DB::table_prefix($table)).' '.$type.' ';
		if ($type === 'DROP')
		{
			if( ! is_array($fields))
			{
				$fields = array($fields);
			}
			$fields = array_map(function($field){
				return DB::quote_identifier($field);
			}, $fields);
			$sql .= implode(', ', $fields);
		} else {
			$sql .= static::process_fields($fields);
		}
		return DB::query($sql, DB::UPDATE)->execute();
	}

	protected static function process_fields($fields)
	{
		$sql_fields = array();

		foreach ($fields as $field => $attr)
		{
			$sql = "\n\t";
			$attr = array_change_key_case($attr, CASE_UPPER);

			$sql .= DB::quote_identifier($field);
			$sql .= array_key_exists('NAME', $attr) ? ' '.DB::quote_identifier($attr['NAME']).' ' : '';
			$sql .= array_key_exists('TYPE', $attr) ? ' '.$attr['TYPE'] : '';
			$sql .= array_key_exists('CONSTRAINT', $attr) ? '('.$attr['CONSTRAINT'].')' : '';
			$sql .= array_key_exists('CHARSET', $attr) ? ' CHARACTER SET '.substr($attr['CHARSET'], 0, stripos($attr['CHARSET'], '_')).' COLLATE '.$attr['CHARSET'] : '';

			if (array_key_exists('UNSIGNED', $attr) and $attr['UNSIGNED'] === true)
			{
				$sql .= ' UNSIGNED';
			}

			$sql .= array_key_exists('DEFAULT', $attr) ? ' DEFAULT '. (($attr['DEFAULT'] instanceof \Database_Expression) ? $attr['DEFAULT']  : DB::escape($attr['DEFAULT'])) : '';
			$sql .= array_key_exists('NULL', $attr) ? (($attr['NULL'] === true) ? ' NULL' : ' NOT NULL') : '';

			if (array_key_exists('AUTO_INCREMENT', $attr) and $attr['AUTO_INCREMENT'] === true)
			{
				$sql .= ' AUTO_INCREMENT';
			}
			$sql_fields[] = $sql;
		}

		return \implode(',', $sql_fields);
	}

	/**
	 * Truncates a table.
	 *
	 * @throws    Fuel\Database_Exception
	 * @param     string    $table    the table name
	 * @return    int       the number of affected rows
	 */
	public static function truncate_table($table)
	{
		return DB::query('TRUNCATE TABLE '.DB::quote_identifier(DB::table_prefix($table)), \DB::DELETE)->execute();
	}

	/**
	 * Analyzes a table.
	 *
	 * @param     string    $table    the table name
	 * @return    bool      whether the table is OK
	 */
	public static function analyze_table($table)
	{
		return static::table_maintenance('ANALYZE TABLE', $table);
	}

	/**
	 * Checks a table.
	 *
	 * @param     string    $table    the table name
	 * @return    bool      whether the table is OK
	 */
	public static function check_table($table)
	{
		return static::table_maintenance('CHECK TABLE', $table);
	}

	/**
	 * Optimizes a table.
	 *
	 * @param     string    $table    the table name
	 * @return    bool      whether the table has been optimized
	 */
	public static function optimize_table($table)
	{
		return static::table_maintenance('OPTIMIZE TABLE', $table);
	}

	/**
	 * Repairs a table.
	 *
	 * @param     string    $table    the table name
	 * @return    bool      whether the table has been repaired
	 */
	public static function repair_table($table)
	{
		return static::table_maintenance('REPAIR TABLE', $table);
	}

	/*
	 * Executes table maintenance. Will throw Fuel_Exception when the operation is not supported.
	 *
	 * @throws	Fuel_Exception
	 * @param     string    $table    the table name
	 * @return    bool      whether the operation has succeeded
	 */
	protected static function table_maintenance($operation, $table)
	{
		$result = \DB::query($operation.' '.\DB::quote_identifier(DB::table_prefix($table)), \DB::SELECT)->execute();
		$type = $result->get('Msg_type');
		$message = $result->get('Msg_text');
		$table = $result->get('Table');
		if($type === 'status' and in_array(strtolower($message), array('ok','table is already up to date')))
		{
			return true;
		}

		if($type === 'error')
		{
			\Log::error('Table: '.$table.', Operation: '.$operation.', Message: '.$result->get('Msg_text'), 'DBUtil::table_maintenance');
		}
		else
		{
			\Log::write(ucfirst($type), 'Table: '.$table.', Operation: '.$operation.', Message: '.$result->get('Msg_text'), 'DBUtil::table_maintenance');
		}
		return false;
	}

}

/* End of file dbutil.php */
