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

class Model_Crud extends \Model implements \Iterator, \ArrayAccess, \Serializable, \Sanitization
{
	/**
	 * @var  string  $_table_name  The table name (must set this in your Model)
	 */
	// protected static $_table_name = '';

	/**
	 * @var  string  $_primary_key  The primary key for the table
	 */
	// protected static $_primary_key = 'id';

	/**
	 * @var string   $_connection   The database connection to use
	 */
	// protected static $_connection = null;

	/**
	 * @var string   $_write_connection   The database connection to use for writes
	 */
	// protected static $_write_connection = null;

	/**
	 * @var  array  $_rules  The validation rules (must set this in your Model to use)
	 */
	// protected static $_rules = array();

	/**
	 * @var  array  $_properties  The table column names (must set this in your Model to use)
	 */
	// protected static $_properties = array();

	/**
	 * @var  array  $_mass_whitelist  The table column names which will be set while using mass assignment like ->set($data)
	 */
	// protected static $_mass_whitelist = array();

	/**
	 * @var  array  $_mass_blacklist  The table column names which will not be set while using mass assignment like ->set($data)
	 */
	// protected static $_mass_blacklist = array();

	/**
	 * @var array  $_labels  Field labels (must set this in your Model to use)
	 */
	// protected static $_labels = array();

	/**
	 * @var array  $_defaults  Field defaults (must set this in your Model to use)
	 */
	// protected static $_defaults = array();

	/**
	 * @var  bool  set true to use MySQL timestamp instead of UNIX timestamp
	 */
	//protected static $_mysql_timestamp = false;

	/**
	 * @var  string  fieldname of created_at field, uncomment to use.
	 */
	//protected static $_created_at = 'created_at';

	/**
	 * @var  string  fieldname of updated_at field, uncomment to use.
	 */
	//protected static $_updated_at = 'updated_at';

	/**
	 * Forges new Model_Crud objects.
	 *
	 * @param   array  $data  Model data
	 * @return  Model_Crud
	 */
	public static function forge(array $data = array())
	{
		return new static($data);
	}

	/**
	 * Finds a row with the given primary key value.
	 *
	 * @param   mixed  $value  The primary key value to find
	 * @return  null|object  Either null or a new Model object
	 */
	public static function find_by_pk($value)
	{
		return static::find_one_by(static::primary_key(), $value);
	}

	/**
	 * Finds a row with the given column value.
	 *
	 * @param   mixed   $column    The column to search
	 * @param   mixed   $value     The value to find
	 * @param   string  $operator
	 * @return  null|object  Either null or a new Model object
	 */
	public static function find_one_by($column, $value = null, $operator = '=')
	{
		$config = array(
			'limit' => 1,
		);

		if (is_array($column) or ($column instanceof \Closure))
		{
			$config['where'] = $column;
		}
		else
		{
			$config['where'] = array(array($column, $operator, $value));
		}

		$result = static::find($config);

		if ($result !== null)
		{
			return reset($result);
		}

		return null;
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
		$config = array(
			'limit' => $limit,
			'offset' => $offset,
		);

		if ($column !== null)
		{
			if (is_array($column) or ($column instanceof \Closure))
			{
				$config['where'] = $column;
			}
			else
			{
				$config['where'] = array(array($column, $operator, $value));
			}
		}

		return static::find($config);
	}

	/**
	 * Finds all records in the table.  Optionally limited and offset.
	 *
	 * @param   int     $limit     Number of records to return
	 * @param   int     $offset    What record to start at
	 * @return  null|object        Null if not found or an array of Model object
	 */
	public static function find_all($limit = null, $offset = 0)
	{
		return static::find(array(
			'limit' => $limit,
			'offset' => $offset,
		));
	}

	/**
	 * Finds all records.
	 *
	 * @param    array     $config     array containing query settings
	 * @param    string    $key        optional array index key
	 * @return   array|null            an array containing models or null if none are found
	 */
	public static function find($config = array(), $key = null)
	{
		$query = \DB::select()
			->from(static::$_table_name)
			->as_object(get_called_class());

		if ($config instanceof \Closure)
		{
			$config($query);
		}
		else
		{
			$config = $config + array(
				'select' => array(static::$_table_name.'.*'),
				'where' => array(),
				'order_by' => array(),
				'limit' => null,
				'offset' => 0,
			);

			extract($config);

			is_string($select) and $select = array($select);
			$query->select_array($select);

			if ( ! empty($where))
			{
				$query->where($where);
			}

			if (is_array($order_by))
			{
				foreach ($order_by as $_field => $_direction)
				{
					$query->order_by($_field, $_direction);
				}
			}
			else
			{
				$query->order_by($order_by);
			}

			if ($limit !== null)
			{
				$query = $query->limit($limit)->offset($offset);
			}
		}

		static::pre_find($query);

		$result =  $query->execute(static::get_connection());
		$result = ($result->count() === 0) ? null : $result->as_array($key);

		return static::post_find($result);
	}

	/**
	 * Count all of the rows in the table.
	 *
	 * @param   string  $column    Column to count by
	 * @param   bool    $distinct  Whether to count only distinct rows (by column)
	 * @param   array   $where     Query where clause(s)
	 * @param   string  $group_by  Column to group by
	 * @return  int     The number of rows OR false
	 * @throws \FuelException
	 */
	public static function count($column = null, $distinct = true, $where = array(), $group_by = null)
	{
		$select = $column ?: static::primary_key();

		// Get the database group / connection
		$connection = static::get_connection();

		// Get the columns
		if ($connection instanceof \Database_Connection)
		{
			$columns = \DB::expr('COUNT('.($distinct ? 'DISTINCT ' : '').
				$connection->quote_identifier($select).
				') AS count_result');
		}
		else
		{
			$columns = \DB::expr('COUNT('.($distinct ? 'DISTINCT ' : '').
				\Database_Connection::instance($connection)->quote_identifier($select).
				') AS count_result');
		}

		// Remove the current select and
		$query = \DB::select($columns);

		// Set from table
		$query = $query->from(static::$_table_name);

		if ( ! empty($where))
		{
			//is_array($where) or $where = array($where);
			if ( ! is_array($where) and ($where instanceof \Closure) === false)
			{
				throw new \FuelException(get_called_class().'::count where statement must be an array or a closure.');
			}
			$query = $query->where($where);
		}

		if ( ! empty($group_by))
		{
			$result = $query->select($group_by)->group_by($group_by)->execute($connection)->as_array();
			$counts = array();
			foreach ($result as $res)
			{
				$counts[$res[$group_by]] = $res['count_result'];
			}

			return $counts;
		}

		$count = $query->execute($connection)->get('count_result');

		if ($count === null)
		{
			return false;
		}

		return (int) $count;
	}

	/**
	 * Implements dynamic Model_Crud::find_by_{column} and Model_Crud::find_one_by_{column}
	 * methods.
	 *
	 * @param   string  $name  The method name
	 * @param   string  $args  The method args
	 * @return  mixed   Based on static::$return_type
	 * @throws  \BadMethodCallException
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
	 * Get the connection to use for reading or writing
	 *
	 * @param  boolean  $writable Get a writable connection
	 * @return mixed    Database profile name (string) or Database_Connection (object)
	 */
	protected static function get_connection($writable = false)
	{
		if ($writable and isset(static::$_write_connection))
		{
			return static::$_write_connection;
		}

		return isset(static::$_connection) ? static::$_connection : null;
	}

	/**
	 * Get the primary key for the current Model
	 *
	 * @return  string
	 */
	protected static function primary_key()
	{
		return isset(static::$_primary_key) ? static::$_primary_key : 'id';
	}

	/**
	 * Gets called before the query is executed.  Must return the query object.
	 *
	 * @param   Database_Query  $query  The query object
	 * @return  void
	 */
	protected static function pre_find(&$query){}

	/**
	 * Gets called after the query is executed and right before it is returned.
	 * $result will be null if 0 rows are returned.
	 *
	 * @param   array|null    $result    the result array or null when there was no result
	 * @return  array|null
	 */
	protected static function post_find($result)
	{
		return $result;
	}

	/**
	 * @var  array  $_data  Data container for this object
	 */
	protected $_data = array();

	/**
	 * @var  bool  $_is_new  If this is a new record
	 */
	protected $_is_new = true;

	/**
	 * @var  bool  $_is_frozen  If this is a record is frozen
	 */
	protected $_is_frozen = false;

	/**
	 * @var  bool  $_sanitization_enabled  If this is a records data will be sanitized on get
	 */
	protected $_sanitization_enabled = false;

	/**
	 * @var  object  $_validation  The validation instance
	 */
	protected $_validation = null;

	/**
	 * Sets up the object.
	 *
	 * @param   array  $data  The data array
	 */
	public function __construct(array $data = array())
	{
		$this->set($data);

		if (isset($this->_data[static::primary_key()]))
		{
			$this->is_new(false);
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
		$this->_data[$property] = $value;
	}

	/**
	 * Magic getter to fetch data from the data container
	 *
	 * @param   string  $property  The property name
	 * @return  mixed
	 */
	public function __get($property)
	{
		if (array_key_exists($property, $this->_data))
		{
			return $this->_sanitization_enabled ? \Security::clean($this->_data[$property], null, 'security.output_filter') : $this->_data[$property];
		}

		throw new \OutOfBoundsException('Property "'.$property.'" not found for '.get_called_class().'.');
	}

	/**
	 * Magic isset to check if values exist
	 *
	 * @param   string  $property  The property name
	 * @return  bool  whether or not the property exists
	 */
	public function __isset($property)
	{
		return isset($this->_data[$property]);
	}

	/**
	 * Magic unset to remove existing properties
	 *
	 * @param   string  $property  The property name
	 */
	public function __unset($property)
	{
		unset($this->_data[$property]);
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
			if (isset(static::$_mass_whitelist))
			{
				in_array($key, static::$_mass_whitelist) and $this->_data[$key] = $value;
			}
			elseif (isset(static::$_mass_blacklist))
			{
				( ! in_array($key, static::$_mass_blacklist)) and $this->_data[$key] = $value;
			}
			else
			{
				// no static::$_mass_whitelist or static::$_mass_blacklist set, proceed with default behavior
				$this->_data[$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Saves the object to the database by either creating a new record
	 * or updating an existing record. Sets the default values if set.
	 *
	 * @param   bool   $validate  whether to validate the input
	 * @return  array|int  Rows affected and or insert ID
	 * @throws \Exception
	 */
	public function save($validate = true)
	{
		if ($this->frozen())
		{
			throw new \Exception('Cannot modify a frozen row.');
		}

		$vars = $this->_data;

		// Set default if there are any
		isset(static::$_defaults) and $vars = $vars + static::$_defaults;

		if ($validate and isset(static::$_rules) and ! empty(static::$_rules))
		{
			$vars = $this->pre_validate($vars);
			$validated = $this->post_validate($this->run_validation($vars));

			if ($validated)
			{
				$validated = array_filter($this->validation()->validated(), function($val){
					return ($val !== null);
				});

				$vars = $validated + $vars;
			}
			else
			{
				return false;
			}
		}

		$vars = $this->prep_values($vars);

		if (isset(static::$_properties))
		{
			$vars = \Arr::filter_keys($vars, static::$_properties);
		}

		if(isset(static::$_updated_at))
		{
			if(isset(static::$_mysql_timestamp) and static::$_mysql_timestamp === true)
			{
				$vars[static::$_updated_at] = \Date::forge()->format('mysql');
			}
			else
			{
				$vars[static::$_updated_at] = \Date::forge()->get_timestamp();
			}
		}

		if ($this->is_new())
		{
			if(isset(static::$_created_at))
			{
				if(isset(static::$_mysql_timestamp) and static::$_mysql_timestamp === true)
				{
					$vars[static::$_created_at] = \Date::forge()->format('mysql');
				}
				else
				{
					$vars[static::$_created_at] = \Date::forge()->get_timestamp();
				}
			}

			$query = \DB::insert(static::$_table_name)
			            ->set($vars);

			$this->pre_save($query);
			$result = $query->execute(static::get_connection(true));

			if ($result[1] > 0)
			{
				// workaround for PDO connections not returning the insert_id
				if ($result[0] === false and isset($vars[static::primary_key()]))
				{
					$result[0] = $vars[static::primary_key()];
				}
				$this->set($vars);
				empty($result[0]) or $this->{static::primary_key()} = $result[0];
				$this->is_new(false);
			}

			return $this->post_save($result);
		}

		$query = \DB::update(static::$_table_name)
		         ->set($vars)
		         ->where(static::primary_key(), '=', $this->{static::primary_key()});

		$this->pre_update($query);
		$result = $query->execute(static::get_connection(true));
		$result > 0 and $this->set($vars);

		return $this->post_update($result);
	}

	/**
	 * Deletes this record and freezes the object
	 *
	 * @return  mixed  Rows affected
	 */
	public function delete()
	{
		$this->frozen(true);
		$query = \DB::delete(static::$_table_name)
		            ->where(static::primary_key(), '=', $this->{static::primary_key()});

		$this->pre_delete($query);
		$result = $query->execute(static::get_connection(true));

		return $this->post_delete($result);
	}

	/**
	 * Either checks if the record is new or sets whether it is new or not.
	 *
	 * @param   bool|null  $new  Whether this is a new record
	 * @return  bool|$this
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
	 * @param   bool|null  $frozen  Whether this is a frozen record
	 * @return  bool|$this
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
	 * Enable sanitization mode in the object
	 *
	 * @return  $this
	 */
	public function sanitize()
	{
		$this->_sanitization_enabled = true;

		return $this;
	}

	/**
	 * Disable sanitization mode in the object
	 *
	 * @return  $this
	 */
	public function unsanitize()
	{
		$this->_sanitization_enabled = false;

		return $this;
	}

	/**
	 * Returns the current sanitization state of the object
	 *
	 * @return  bool
	 */
	public function sanitized()
	{
		return $this->_sanitization_enabled;
	}

	/**
	 * Returns the a validation object for the model.
	 *
	 * @return  object  Validation object
	 */
	public function validation()
	{
		if( ! $this->_validation)
		{
			$this->_validation = \Validation::forge(\Str::random('alnum', 32));

			if (isset(static::$_rules) and count(static::$_rules))
			{
				foreach (static::$_rules as $field => $rules)
				{
					$label = (isset(static::$_labels) and array_key_exists($field, static::$_labels)) ? static::$_labels[$field] : $field;
					$this->_validation->add_field($field, $label, $rules);
				}
			}
		}

		return $this->_validation;
	}

	/**
	 * Returns all of $this object's public properties as an associative array.
	 *
	 * @return  array
	 */
	public function to_array()
	{
		return $this->_data;
	}

	/**
	 * Implementation of the Iterator interface
	 */

	public function rewind()
	{
		reset($this->_data);
	}

	public function current()
	{
		if ($this->_sanitization_enabled)
		{
			return \Security::clean(current($this->_data), null, 'security.output_filter');
		}
		return current($this->_data);
	}

	public function key()
	{
		return key($this->_data);
	}

	public function next()
	{
		if ($this->_sanitization_enabled)
		{
			return \Security::clean(next($this->_data), null, 'security.output_filter');
		}
		return next($this->_data);
	}

	public function valid()
	{
		return key($this->_data) !== null;
	}

	/**
	 * Sets the value of the given offset (class property).
	 *
	 * @param   string  $offset  class property
	 * @param   string  $value   value
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		$this->_data[$offset] = $value;
	}

	/**
	 * Checks if the given offset (class property) exists.
	 *
	 * @param   string  $offset  class property
	 * @return  bool
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_data);
	}

	/**
	 * Unsets the given offset (class property).
	 *
	 * @param   string  $offset  class property
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}

	/**
	 * Gets the value of the given offset (class property).
	 *
	 * @param   string  $offset  class property
	 * @return  mixed
	 */
	public function offsetGet($offset)
	{
		if (array_key_exists($offset, $this->_data))
		{
			if ($this->_sanitization_enabled)
			{
				return \Security::clean($this->_data[$offset], null, 'security.output_filter');
			}
			return $this->_data[$offset];
		}

		throw new \OutOfBoundsException('Property "'.$offset.'" not found for '.get_called_class().'.');
	}

	/**
	 * Returns whether the instance will pass validation.
	 *
	 * @return  bool  whether the instance passed validation
	 */
	public function validates()
	{
		if ( ! isset(static::$_rules) or count(static::$_rules) < 0)
		{
			return true;
		}

		$vars = $this->_data;

		// Set default if there are any
		isset(static::$_defaults) and $vars = $vars + static::$_defaults;
		$vars = $this->pre_validate($vars);

		return $this->run_validation($vars);
	}

	/**
	 * Run validation
	 *
	 * @param   array  $vars  array to validate
	 * @return  bool   validation result
	 */
	protected function run_validation($vars)
	{
		if ( ! isset(static::$_rules) or count(static::$_rules) < 0)
		{
			return true;
		}

		$this->_validation = $this->validation();

		return $this->_validation->run($vars);
	}

	/**
	 * Gets called before the insert query is executed.  Must return
	 * the query object.
	 *
	 * @param   Database_Query  $query  The query object
	 * @return  void
	 */
	protected function pre_save(&$query){}

	/**
	 * Gets called after the insert query is executed and right before
	 * it is returned.
	 *
	 * @param   array  $result  insert id and number of affected rows
	 * @return  array
	 */
	protected function post_save($result)
	{
		return $result;
	}

	/**
	 * Gets called before the update query is executed.  Must return the query object.
	 *
	 * @param   Database_Query  $query  The query object
	 * @return  void
	 */
	protected function pre_update(&$query){}

	/**
	 * Gets called after the update query is executed and right before
	 * it is returned.
	 *
	 * @param   int  $result  Number of affected rows
	 * @return  int
	 */
	protected function post_update($result)
	{
		return $result;
	}

	/**
	 * Gets called before the delete query is executed.  Must return the query object.
	 *
	 * @param   Database_Query  $query  The query object
	 * @return  void
	 */
	protected function pre_delete(&$query){}

	/**
	 * Gets called after the delete query is executed and right before
	 * it is returned.
	 *
	 * @param   int  $result  Number of affected rows
	 * @return  int
	 */
	protected function post_delete($result)
	{
		return $result;
	}

	/**
	 * Gets called before the validation is ran.
	 *
	 * @param   array  $data  The validation data
	 * @return  array
	 */
	protected function pre_validate($data)
	{
		return $data;
	}

	/**
	 * Called right after the validation is ran.
	 *
	 * @param   bool  $result  Validation result
	 * @return  bool
	 */
	protected function post_validate($result)
	{
		return $result;
	}

	/**
	 * Called right after values retrieval, before save,
	 * update, setting defaults and validation.
	 *
	 * @param   array  $values  input array
	 * @return  array
	 */
	protected function prep_values($values)
	{
		return $values;
	}

	/**
	 * Serializable implementation: serialize
	 *
	 * @return  array  model data
	 */
	public function serialize()
	{
		$data = $this->_data;

		$data['_is_new'] = $this->_is_new;
		$data['_is_frozen'] = $this->_is_frozen;

		return serialize($data);
	}

	/**
	 * Serializable implementation: unserialize
	 *
	 * @param   string  $data
	 * @return  array   model data
	 */
	public function unserialize($data)
	{
		$data = unserialize($data);

		if (isset($data['_is_new']))
		{
			$this->is_new = $data['_is_new'];
			unset($data['_is_new']);
		}
		else
		{
			$this->_is_new = true;
		}

		if (isset($data['_is_frozen']))
		{
			$this->_is_frozen = $data['_is_frozen'];
			unset($data['_is_frozen']);
		}
		else
		{
			$this->_is_frozen = false;
		}

		$this->_data = $data;
	}
}
