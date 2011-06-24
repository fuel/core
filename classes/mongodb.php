<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * This code is based on Redisent, a Redis interface for the modest.
 *
 * It has been modified to work with Fuel and to improve the code slightly.
 *
 * @author 		Justin Poliey <jdp34@njit.edu>
 * @copyright 	2009 Justin Poliey <jdp34@njit.edu>
 * @modified	Alex Bilbie
 * @modified	Phil Sturgeon
 * @license 	http://www.opensource.org/licenses/mit-license.php The MIT License
 */


class MongoDbException extends Fuel_Exception {}


class MongoDb {

	protected $db;
	protected $persist = false;

	protected $selects = array();
	public $wheres = array();	// $wheres is public for sanity reasons - useful for debuggging
	protected $sorts = array();
	protected $limit = 999999;
	protected $offset = 0;

	protected static $instances = array();

	public static function instance($name = 'default')
	{
		if (\array_key_exists($name, static::$instances))
		{
			return static::$instances[$name];
		}

		if (empty(static::$instances))
		{
			\Config::load('db', true);
		}
		if ( ! ($config = \Config::get('db.mongo.'.$name)))
		{
			throw new \MongoDbException('Invalid instance name given.');
		}

		static::$instances[$name] = new static($config);

		return static::$instances[$name];
	}

	protected $connection = false;


	/**
	*	--------------------------------------------------------------------------------
	*	CONSTRUCTOR
	*	--------------------------------------------------------------------------------
	*
	*	Automatically check if the Mongo PECL extension has been installed/enabled.
	*	Generate the connection string and establish a connection to the MongoDB.
	*/
	public function __construct(array $config = array())
	{
		if ( ! class_exists('Mongo'))
		{
			throw new \MongoDbException("The MongoDB PECL extension has not been installed or enabled");
		}

		// Build up a connect options array for mongo
		$options = array("connect" => TRUE);

		if ( ! empty($config['persistent']))
		{
			$options['persist'] = 'fuel_mongo_persist';
		}

		$connection_string = "mongodb://";

		if (empty($config['hostname']))
		{
			throw new \MongoDbException("The host must be set to connect to MongoDB");
		}

		if (empty($config['database']))
		{
			throw new \MongoDbException("The database must be set to connect to MongoDB");
		}

		if ( ! empty($config['username']) and ! empty($config['password']))
		{
			$connection_string .= "{$config['username']}:{$config['password']}@";
		}

		if (isset($config['port']) && ! empty($config['port']))
		{
			$connection_string .= "{$config['hostname']}:{$config['port']}";
		}
		else
		{
			$connection_string .= "{$config['hostname']}";
		}

		$connection_string .= "/{$config['database']}";

		// Let's give this a go
		try
		{
			$this->connection = new \Mongo(trim($connection_string), $options);
			$this->db = $this->connection->{$config['database']};
			return $this;
		}
		catch (\MongoConnectionException $e)
		{
			throw new \MongoDbException("Unable to connect to MongoDB: {$e->getMessage()}");
		}
	}

	// public function __destruct()
	// {
	// 	fclose($this->connection);
	// }

	/**
	*	--------------------------------------------------------------------------------
	*	Drop_db
	*	--------------------------------------------------------------------------------
	*
	*	Drop a Mongo database
	*	@usage $mongodb->drop_db("foobar");
	*/
	public static function drop_db($database = null)
	{
		if (empty($database))
		{
			throw new \MongoDbException('Failed to drop MongoDB database because name is empty');
		}

		else
		{
			try
			{
				static::instance()->connection->{$database}->drop();
				return true;
			}
			catch (\Exception $e)
			{
				throw new \MongoDbException("Unable to drop Mongo database `{$database}`: {$e->getMessage()}");
			}

		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Drop_collection
	*	--------------------------------------------------------------------------------
	*
	*	Drop a Mongo collection
	*	@usage $mongodb->drop_collection('foo', 'bar');
	*/
	public static function drop_collection($db = "", $col = "")
	{
		if (empty($db))
		{
			throw new \MongoDbException('Failed to drop MongoDB collection because database name is empty');
		}

		if (empty($col))
		{
			throw new \MongoDbException('Failed to drop MongoDB collection because collection name is empty');
		}

		else
		{
			try
			{
				static::instance()->connection->{$db}->{$col}->drop();
				return true;
			}
			catch (\Exception $e)
			{
				throw new \MongoDbException("Unable to drop Mongo collection `{$col}`: {$e->getMessage()}");
			}
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	SELECT FIELDS
	*	--------------------------------------------------------------------------------
	*
	*	Determine which fields to include OR which to exclude during the query process.
	*	Currently, including and excluding at the same time is not available, so the
	*	$includes array will take precedence over the $excludes array.  If you want to
	*	only choose fields to exclude, leave $includes an empty array().
	*
	*	@usage $mongodb->select(array('foo', 'bar'))->get('foobar');
	*/
	public function select($includes = array(), $excludes = array())
	{
	 	if ( ! is_array($includes))
	 	{
	 		$includes = array();
	 	}

	 	if ( ! is_array($excludes))
	 	{
	 		$excludes = array();
	 	}

	 	if ( ! empty($includes))
	 	{
	 		foreach ($includes as $col)
	 		{
	 			$this->selects[$col] = 1;
	 		}
	 	}
	 	else
	 	{
	 		foreach ($excludes as $col)
	 		{
	 			$this->selects[$col] = 0;
	 		}
	 	}
	 	return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents based on these search parameters.  The $wheres array should
	*	be an associative array with the field as the key and the value as the search
	*	criteria.
	*
	*	@usage $mongodb->where(array('foo' => 'bar'))->get('foobar');
	*/
	public function where($wheres = array())
	 {
	 	foreach ($wheres as $wh => $val)
	 	{
	 		$this->wheres[$wh] = $val;
	 	}
	 	return $this;
	 }

	/**
	*	--------------------------------------------------------------------------------
	*	OR_WHERE PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field may be something else
	*
	*	@usage $mongodb->or_where(array( array('foo'=>'bar', 'bar'=>'foo' ))->get('foobar');
	*/
	public function or_where($wheres = array())
	{
		if (count($wheres) > 0)
		{
			if ( ! isset($this->wheres['$or']) || ! is_array($this->wheres['$or']))
			{
				$this->wheres['$or'] = array();
			}

			foreach ($wheres as $wh => $val)
			{
				$this->wheres['$or'][] = array($wh=>$val);
			}
		}
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE_IN PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is in a given $in array().
	*
	*	@usage $mongodb->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	public function where_in($field = "", $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$in'] = $in;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE_IN_ALL PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is in all of a given $in array().
	*
	*	@usage $mongodb->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	public function where_in_all($field = "", $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$all'] = $in;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE_NOT_IN PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is not in a given $in array().
	*
	*	@usage $mongodb->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	public function where_not_in($field = "", $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$nin'] = $in;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE GREATER THAN PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is greater than $x
	*
	*	@usage $mongodb->where_gt('foo', 20);
	*/
	public function where_gt($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE GREATER THAN OR EQUAL TO PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is greater than or equal to $x
	*
	*	@usage $mongodb->where_gte('foo', 20);
	*/
	public function where_gte($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		return($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE LESS THAN PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is less than $x
	*
	*	@usage $mongodb->where_lt('foo', 20);
	*/
	public function where_lt($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$lt'] = $x;
		return($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE LESS THAN OR EQUAL TO PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is less than or equal to $x
	*
	*	@usage $mongodb->where_lte('foo', 20);
	*/
	public function where_lte($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$lte'] = $x;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE BETWEEN PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is between $x and $y
	*
	*	@usage $mongodb->where_between('foo', 20, 30);
	*/
	public function where_between($field = "", $x, $y)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		$this->wheres[$field]['$lte'] = $y;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE BETWEEN AND NOT EQUAL TO PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is between but not equal to $x and $y
	*
	*	@usage $mongodb->where_between_ne('foo', 20, 30);
	*/
	public function where_between_ne($field = "", $x, $y)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		$this->wheres[$field]['$lt'] = $y;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE NOT EQUAL TO PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is not equal to $x
	*
	*	@usage $mongodb->where_not_equal('foo', 1)->get('foobar');
	*/
	public function where_ne($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$ne'] = $x;
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE NOT EQUAL TO PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents nearest to an array of coordinates (your collection must have a geospatial index)
	*
	*	@usage $mongodb->where_near('foo', array('50','50'))->get('foobar');
	*/
	public function where_near($field = '', $co = array())
	{
		$this->__where_init($field);
		$this->where[$what]['$near'] = $co;  // @TODO : can't work, $what is undefined
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	LIKE PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the (string) value of a $field is like a value. The defaults
	*	allow for a case-insensitive search.
	*
	*	@param $flags
	*	Allows for the typical regular expression flags:
	*		i = case insensitive
	*		m = multiline
	*		x = can contain comments
	*		l = locale
	*		s = dotall, "." matches everything, including newlines
	*		u = match unicode
	*
	*	@param $enable_start_wildcard
	*	If set to anything other than TRUE, a starting line character "^" will be prepended
	*	to the search value, representing only searching for a value at the start of
	*	a new line.
	*
	*	@param $enable_end_wildcard
	*	If set to anything other than TRUE, an ending line character "$" will be appended
	*	to the search value, representing only searching for a value at the end of
	*	a line.
	*
	*	@usage $mongodb->like('foo', 'bar', 'im', FALSE, TRUE);
	*/
	public function like($field = "", $value = "", $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE)
	 {
	 	$field = (string) trim($field);
	 	$this->where_init($field);
	 	$value = (string) trim($value);
	 	$value = quotemeta($value);

	 	if ($enable_start_wildcard !== TRUE)
	 	{
	 		$value = "^" . $value;
	 	}

	 	if ($enable_end_wildcard !== TRUE)
	 	{
	 		$value .= "$";
	 	}

	 	$regex = "/$value/$flags";
	 	$this->wheres[$field] = new MongoRegex($regex);
	 	return $this;
	 }

	/**
	*	--------------------------------------------------------------------------------
	*	ORDER BY PARAMETERS
	*	--------------------------------------------------------------------------------
	*
	*	Sort the documents based on the parameters passed. To set values to descending order,
	*	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	*	set to 1 (ASC).
	*
	*	@usage $mongodb->where_between('foo', 20, 30);
	*/
	public function order_by($fields = array())
	{
		foreach ($fields as $col => $val)
		{
			if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$this->sorts[$col] = -1;
			}
			else
			{
				$this->sorts[$col] = 1;
			}
		}
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	LIMIT DOCUMENTS
	*	--------------------------------------------------------------------------------
	*
	*	Limit the result set to $x number of documents
	*
	*	@usage $mongodb->limit($x);
	*/
	public function limit($x = 99999)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->limit = (int) $x;
		}
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	OFFSET DOCUMENTS
	*	--------------------------------------------------------------------------------
	*
	*	Offset the result set to skip $x number of documents
	*
	*	@usage $mongodb->offset($x);
	*/
	public function offset($x = 0)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->offset = (int) $x;
		}
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	GET_WHERE
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents based upon the passed parameters
	*
	*	@usage $mongodb->get_where('foo', array('bar' => 'something'));
	*/
	public function get_where($collection = "", $where = array(), $limit = 99999)
	{
		return ($this->where($where)->limit($limit)->get($collection));
	}

	/**
	*	--------------------------------------------------------------------------------
	*	GET
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents based upon the passed parameters
	*
	*	@usage $mongodb->get('foo', array('bar' => 'something'));
	*/
	 public function get($collection = "")
	 {
		if (empty($collection))
		{
			throw new \MongoDbException("In order to retreive documents from MongoDB");
		}

		$results = array();

		$documents = $this->db->{$collection}->find($this->wheres, $this->selects)->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);

		$returns = array();

		if ($documents && count($documents) > 0)
		{
			foreach ($documents as $doc)
			{
				$returns[] = $doc;
			}
		}

		return (object)$returns;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	COUNT
	*	--------------------------------------------------------------------------------
	*
	*	Count the documents based upon the passed parameters
	*
	*	@usage $mongodb->get('foo');
	*/

	public function count($collection = "") {
		if (empty($collection))
		{
			throw new \MongoDbException("In order to retreive a count of documents from MongoDB");
		}

		$count = $this->db->{$collection}->find($this->wheres)->limit((int) $this->limit)->skip((int) $this->offset)->count();
		$this->_clear();
		return ($count);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	INSERT
	*	--------------------------------------------------------------------------------
	*
	*	Insert a new document into the passed collection
	*
	*	@usage $mongodb->insert('foo', $data = array());
	*/
	public function insert($collection = "", $insert = array())
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection selected to insert into");
		}

		if (count($insert) == 0 || !is_array($insert))
		{
			throw new \MongoDbException("Nothing to insert into Mongo collection or insert is not an array");
		}

		try
		{
			$this->db->{$collection}->insert($insert, array('fsync' => TRUE));
			if (isset($insert['_id']))
			{
				return ($insert['_id']);
			}
			else
			{
				return (FALSE);
			}
		}
		catch (\MongoCursorException $e)
		{
			throw new \MongoDbException("Insert of data into MongoDB failed: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	UPDATE
	*	--------------------------------------------------------------------------------
	*
	*	Updates a single document
	*
	*	@usage $mongodb->update('foo', $data = array());
	*/
	public function update($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection selected to update");
		}

		if (count($data) == 0 || ! is_array($data))
		{
			throw new \MongoDbException("Nothing to update in Mongo collection or update is not an array");
		}

		try
		{
			$options = array_merge($options, array('fsync' => TRUE, 'multiple' => FALSE));
			$this->db->{$collection}->update($this->wheres, array('$set' => $data), $options);
			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \MongoDbException("Update of data into MongoDB failed: {$e->getMessage()}");
		}

	}

	/**
	*	--------------------------------------------------------------------------------
	*	UPDATE_ALL
	*	--------------------------------------------------------------------------------
	*
	*	Updates a collection of documents
	*
	*	@usage $mongodb->update_all('foo', $data = array());
	*/
	public function update_all($collection = "", $data = array())
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection selected to update");
		}

		if (count($data) == 0 || ! is_array($data))
		{
			throw new \MongoDbException("Nothing to update in Mongo collection or update is not an array");
		}

		try
		{
			$this->db->{$collection}->update($this->wheres, array('$set' => $data), array('fsync' => TRUE, 'multiple' => TRUE));
			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \MongoDbException("Update of data into MongoDB failed: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	DELETE
	*	--------------------------------------------------------------------------------
	*
	*	delete document from the passed collection based upon certain criteria
	*
	*	@usage $mongodb->delete('foo', $data = array());
	*/
	public function delete($collection = "")
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection selected to delete from");
		}

		try
		{
			$this->db->{$collection}->remove($this->wheres, array('fsync' => TRUE, 'justOne' => TRUE));
			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \MongoDbException("Delete of data into MongoDB failed: {$e->getMessage()}");
		}

	}

	/**
	*	--------------------------------------------------------------------------------
	*	DELETE_ALL
	*	--------------------------------------------------------------------------------
	*
	*	Delete all documents from the passed collection based upon certain criteria
	*
	*	@usage $mongodb->delete_all('foo', $data = array());
	*/
	 public function delete_all($collection = "")
	 {
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection selected to delete from");
		}

		try
		{
			$this->db->{$collection}->remove($this->wheres, array('fsync' => TRUE, 'justOne' => FALSE));
			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \MongoDbException("Delete of data into MongoDB failed: {$e->getMessage()}");
		}

	}

	/**
	*	--------------------------------------------------------------------------------
	*	COMMAND
	*	--------------------------------------------------------------------------------
	*
	*	Runs a MongoDB command (such as GeoNear). See the MongoDB documentation for more usage scenarios:
	*	http://dochub.mongodb.org/core/commands
	*
	*	@usage $mongodb->command(array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>TRUE));
	*/
	public function command($query = array())
	{
		try
		{
			$run = $this->db->command($query);
			return $run;
		}

		catch (\MongoCursorException $e)
		{
			throw new \MongoDbException("MongoDB command failed to execute: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	ADD_INDEX
	*	--------------------------------------------------------------------------------
	*
	*	Ensure an index of the keys in a collection with optional parameters. To set values to descending order,
	*	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	*	set to 1 (ASC).
	*
	*	@usage $mongodb->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	*/
	public function add_index($collection = "", $keys = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection specified to add index to");
		}

		if (empty($keys) || ! is_array($keys))
		{
			throw new \MongoDbException("Index could not be created to MongoDB Collection because no keys were specified");
		}

		foreach ($keys as $col => $val)
		{
			if($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$keys[$col] = -1;
			}
			else
			{
				$keys[$col] = 1;
			}
		}

		if ($this->db->{$collection}->ensureIndex($keys, $options) == TRUE)
		{
			$this->_clear();
			return $this;
		}
		else
		{
			throw new \MongoDbException("An error occured when trying to add an index to MongoDB Collection");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	REMOVE_INDEX
	*	--------------------------------------------------------------------------------
	*
	*	Remove an index of the keys in a collection. To set values to descending order,
	*	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	*	set to 1 (ASC).
	*
	*	@usage $mongodb->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	*/
	public function remove_index($collection = "", $keys = array())
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection specified to remove index from");
		}

		if (empty($keys) || ! is_array($keys))
		{
			throw new \MongoDbException("Index could not be removed from MongoDB Collection because no keys were specified");
		}

		if ($this->db->{$collection}->deleteIndex($keys, $options) == TRUE)  // @TODO : can't work, $options is undefined
		{
			$this->_clear();
			return $this;
		}
		else
		{
			throw new \MongoDbException("An error occured when trying to remove an index from MongoDB Collection");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	REMOVE_ALL_INDEXES
	*	--------------------------------------------------------------------------------
	*
	*	Remove all indexes from a collection.
	*
	*	@usage $mongodb->remove_all_index($collection);
	*/
	public function remove_all_indexes($collection = "")
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection specified to remove all indexes from");
		}
		$this->db->{$collection}->deleteIndexes();
		$this->_clear();
		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	LIST_INDEXES
	*	--------------------------------------------------------------------------------
	*
	*	Lists all indexes in a collection.
	*
	*	@usage $mongodb->list_indexes($collection);
	*/
	public function list_indexes($collection = "")
	{
		if (empty($collection))
		{
			throw new \MongoDbException("No Mongo collection specified to remove all indexes from");
		}

		return ($this->db->{$collection}->getIndexInfo());
	}


	/**
	*	--------------------------------------------------------------------------------
	*	_clear
	*	--------------------------------------------------------------------------------
	*
	*	Resets the class variables to default settings
	*/
	protected function _clear()
	{
		$this->selects	= array();
		$this->wheres	= array();
		$this->limit	= 999999;
		$this->offset	= 0;
		$this->sorts	= array();
	}

	/**
	*	--------------------------------------------------------------------------------
	*	WHERE INITIALIZER
	*	--------------------------------------------------------------------------------
	*
	*	Prepares parameters for insertion in $wheres array().
	*/
	protected function _where_init($param)
	{
		if ( ! isset($this->wheres[$param]))
		{
			$this->wheres[ $param ] = array();
		}
	}
}

/* End of file classes/mongodb.php */