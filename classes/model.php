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

class Model {

	/**
	 * The return type:
	 *  - 'array': Returns as an array of assoc. arrays
	 *  - 'object': Returned as a result object
	 *  - 'Your_Class_Name': Each row will be of this type
	 *
	 * @var  string
	 */
	protected static $return_type = 'array';

	/**
	 * The table name of the model
	 *
	 * @var  string
	 */
	protected static $table = '';

	/**
	 * Finds and returns all the records in the table.  Optionally limited and
	 * offset.
	 *
	 * @param   string  $limit   Record limit
	 * @param   string  $offset  Record offset
	 * @return  mixed   Based on static::$return_type
	 */
	public static function find_all($limit = null, $offset = 0)
	{
		$query = DB::select('*')->from(static::$table);
		
		if ($limit !== null)
		{
			$query->limit($limit)->offset($offset);
		}
		
		$result = static::get_result($query);
		
		return $result;
	}

	/**
	 * Implements dynamic Model::find_by_{column} and Model::find_one_by_{column}
	 * methods.
	 *
	 * @param   string  $name  The method name
	 * @param   string  $args  The method args
	 * @return  mixed   Based on static::$return_type
	 * @throws  BadMethodCallException
	 */
	public static function __callStatic($name, $args)
	{
		if (strncmp($name, 'find_by_', 8) === 0)
		{
			return static::find_by(substr($name, 8), reset($args));
		}
		elseif (strncmp($name, 'find_one_by_', 12) === 0)
		{
			return static::find_one_by(substr($name, 12), reset($args));
		}
		throw new \BadMethodCallException('Method "'.$name.'" does not exist.');
	}

	/**
	 * This special find_by method is so that only one entry is returned.
	 *
	 * @param   string  $id  The row id
	 * @return  mixed   Based on static::$return_type
	 */
	public static function find_by_id($id)
	{
		return static::find_one_by('id', $id);
	}

	/**
	 * Finds all the records that meet the given criteria.  The first parameter
	 * can be an array that is valid for the where() function in the QB.
	 *
	 * @param   string|array  $column    The column name or array of column/values
	 * @param   string        $value     The value to find
	 * @param   string        $operator  The comparison operator
	 * @return  mixed         Based on static::$return_type
	 * @throws  OutOfBoundsException
	 */
	public static function find_by($column, $value = null, $operator = '=')
	{
		$query = DB::select('*')->from(static::$table);
		
		if (is_array($column))
		{
			$query->where($column);
		}
		else
		{
			$query->where($column, $operator, $value);
		}
		
		$result = static::get_result($query);
		
		if (count($result) === 0)
		{
			throw new \OutOfBoundsException('No record with `'.$column.'` '.$operator.' "'.$value.'" in the "'.static::$table.'" table.');
		}
		
		return $result;
	}

	/**
	 * Finds one record that meet the given criteria.  The first parameter
	 * can be an array that is valid for the where() function in the QB.
	 *
	 * @param   string|array  $column    The column name or array of column/values
	 * @param   string        $value     The value to find
	 * @param   string        $operator  The comparison operator
	 * @return  mixed         Based on static::$return_type
	 * @throws  OutOfBoundsException
	 */
	public static function find_one_by($column, $value = null, $operator = '=')
	{
		$query = DB::select('*')->from(static::$table);
		
		if (is_array($column))
		{
			$query->where($column);
		}
		else
		{
			$query->where($column, $operator, $value);
		}
		
		$query->limit(1);
		
		$result = static::get_result($query);

		if (count($result) === 0)
		{
			throw new \OutOfBoundsException('No record with `'.$column.'` '.$operator.' "'.$value.'" in the "'.static::$table.'" table.');
		}
		
		return reset($result);
	}

	/**
	 * Gets the results and returns them based on static::$return_type.
	 *
	 * @param   object  $query  The Query Builder object
	 * @return  mixed   Based on static::$return_type
	 */
	protected static function get_result($query)
	{
		if (static::$return_type == 'array')
		{
			$result = $query->execute()->as_array();
		}
		elseif (static::$return_type == 'object')
		{
			$result = $query->execute();
		}
		else
		{
			$result = $query->as_object(static::$return_type)->execute();
		}

		return $result;
	}
}
