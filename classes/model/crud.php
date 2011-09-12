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

class Model_Crud extends Model {
	
	/**
	 * @var  string  $_table  The table name
	 */
	protected static $_table = false;

	/**
	 * @var  string  $_pk  The primary key for the table
	 */
	protected static $_pk = 'id';
	
	/**
	 * @var  array  $_rules  The validation rules
	 */
	protected static $_rules = array();
	
	/**
	 * @var array  $_labels  Field labels
	 */
	protected static $_labels = array();

	/**
	 * Finds a row with the given primary key value.
	 *
	 * @param   mixed  $value  The primary key value to find
	 * @return  null|object  Either null or a new Model object
	 */
	public static function find_by_pk($value)
	{
		return static::find_one_by(static::$_pk, $value);
	}

	/**
	 * Finds a row with the given column value.
	 *
	 * @param   mixed  $column  The column to search
	 * @param   mixed  $value   The value to find
	 * @return  null|object  Either null or a new Model object
	 */
	public static function find_one_by($column, $value = null, $operator = '=')
	{
		$query = \DB::select('*')
		           ->from(static::$_table);
		
		if (is_array($column))
		{
			$query->where($column);
		}
		else
		{
			$query->where($column, $operator, $value);
		}

		$query = $query->limit(1)
		               ->as_object(get_called_class())
		               ->execute();

		if ($query->count() === 0)
		{
			return null;
		}

		return $query->current();
	}

	/**
	 * Finds all records where the given column matches the given value using
	 * the given operator ('=' by default).  Optionally limited and offset.
	 *
	 * @param   string  $column    The column to search
	 * @param   mixed   $value     The value to find
	 * @param   string  $operator  The operator to search with
	 * @param   int     $limit     Number of records to return
	 * @param   int     $offset    What record to start at
	 * @return  null|object  Null if not found or an array of Model object
	 */
	public static function find_by($column = null, $value = null, $operator = '=', $limit = null, $offset = 0)
	{
		$query = \DB::select('*')
		           ->from(static::$_table);
		
		if ($column !== null)
		{
			if (is_array($column))
			{
				$query->where($column);
			}
			else
			{
				$query->where($column, $operator, $value);
			}
		}
		
		if ($limit !== null)
		{
			$query->limit($limit)->offset($offset);
		}

		$query = $query->as_object(get_called_class())
		               ->execute();

		if ($query->count() === 0)
		{
			return null;
		}

		return $query->as_array();
	}

	/**
	 * Finds all records in the table.  Optionally limited and offset.
	 *
	 * @param   int     $limit     Number of records to return
	 * @param   int     $offset    What record to start at
	 * @return  null|object  Null if not found or an array of Model object
	 */
	public static function find_all($limit = null, $offset = 0)
	{
		return static::find_by(null, null, '=', $limit, $offset);
	}

	/**
	 * Implements dynamic Model_Crud::find_by_{column} and Model_Crud::find_one_by_{column}
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
	 * @var  bool  $_is_new  If this is a new record
	 */
	protected $_is_new = true;

	/**
	 * @var  bool  $_is_frozen  If this is a record is frozen
	 */
	protected $_is_frozen = false;
	
	/**
	 * @var  object  $_validation  The validation instance
	 */
	protected $_validation = null;

	/**
	 * Sets up the object.
	 *
	 * @param   array  $data  The data array
	 * @return  void
	 */
	public function __construct(array $data = array())
	{
		if (isset($this->{static::$_pk}))
		{
			$this->is_new(false);
		}

		if ( ! empty($data))
		{
			foreach ($data as $key => $value)
			{
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Magic setter so new objects can be assigned values
	 *
	 * @param   string  $property  The property name
	 * @param   mixed   $value     The property value
	 * @return  void
	 */
	public function __set($property, $value)
	{
		$this->{$property} = $value;
	}

	/**
	 * Sets an array of values to class properties
	 *
	 * @param   array  $data  The data
	 * @return  $this
	 */
	public function set(array $data)
	{
		foreach ($data as $key => $value)
		{
			$this->{$key} = $value;
		}
		return $this;
	}

	/**
	 * Saves the object to the database by either creating a new record
	 * or updating an existing record.
	 *
	 * @return  mixed  Rows affected and or insert ID
	 */
	public function save()
	{
		if ($this->frozen())
		{
			throw new \Exception('Cannot modify a frozen row.');
		}
		// This crazy bit of code gets all of this object's public properties
		$vars = (array) $this;
		array_walk($vars, function ($value, $key) use (&$vars)
		{
			if ($key[0] === "\0")
			{
				unset($vars[$key]);
			}
		});
		
		if(count(static::$_rules) > 0)
		{
			$validated = $this->run_validation($vars);
			
			if($validated)
			{
				$vars = $this->validation()->validated();
			}
			else
			{
				return false;
			}
		}

		if ($this->is_new())
		{
			return \DB::insert(static::$_table)
			         ->set($vars)
			         ->execute();
		}
		return \DB::update(static::$_table)
		         ->set($vars)
		         ->where(static::$_pk, '=', $this->{static::$_pk})
		         ->execute();
	}
	
	/**
	 * Run validation
	 *
	 * @param   array  $vars  array to validate
	 * @return  bool   validation result
	 */
	protected function run_validation($vars)
	{
		$this->_validation = null;
		$this->_validation = $this->validation();
		
		if(static::$_rules as $field => $rules)
		{
			$label = array_key_exists($field, static::$_labels) ? static::$_labels[$field] : $field;
			$this->_validation->add_field($field, $label, $rules);
		}
		
		return $this->_validation->run($vars);
	}
	
	/**
	 * Returns the a validation object for the model.
	 *
	 * @return  object  Validation object
	 */
	public function validation()
	{
		$this->_validation or $this->_validation = \Validation::forge(md5(microtime(true)));
		
		return $this->_validation;
	}

	/**
	 * Deletes this record and freezes the object
	 *
	 * @return  mixed  Rows affected
	 */
	public function delete()
	{
		$this->frozen(true);
		return \DB::delete(static::$_table)
		         ->where(static::$_pk, '=', $this->{static::$_pk})
		         ->execute();
	}

	/**
	 * Either checks if the record is new or sets whether it is new or not.
	 *
	 * @param   bool|null  $new  Whether this is a new record
	 * @return  void|$this
	 */
	public function is_new($new = null)
	{
		if ($new === null)
		{
			return $this->_is_new;
		}

		$this->_is_new = (bool) $new;

		return $this;
	}

	/**
	 * Either checks if the record is frozen or sets whether it is frozen or not.
	 *
	 * @param   bool|null  $new  Whether this is a frozen record
	 * @return  void|$this
	 */
	public function frozen($frozen = null)
	{
		if ($frozen === null)
		{
			return $this->_is_frozen;
		}

		$this->_is_frozen = (bool) $frozen;

		return $this;
	}
	
	/**
	 * Class init.
	 *
	 * Sets the table name if not set, based on the model name.
	 */
	public static function _init()
	{
		if( ! static::$table)
		{
			$class = get_called_class();
			$table = \Inflector::tableize($class);
			static::$table = &$table;
		}
	}

}