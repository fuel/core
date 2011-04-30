<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Alex Bilbie
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

/**
 * This code is based on Redisent, a Redis interface for the modest.
 *
 * It has been modified to work with Fuel and to improve the code slightly.
 *
 * @author Justin Poliey <jdp34@njit.edu>
 * @copyright 2009 Justin Poliey <jdp34@njit.edu>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Fuel\Core;

class Mongodb {
		
	private $connection;
	private $db;
	protected $connection_string;
	private $persist = FALSE;
	
	private $selects = array();
	public  $wheres = array();	// $wheres is public for sanity reasons - useful for debuggging
	private $sorts = array();
	private $limit = 999999;
	private $offset = 0;
	
	/**
	*	--------------------------------------------------------------------------------
	*	CONSTRUCTOR
	*	--------------------------------------------------------------------------------
	*
	*	Automatically check if the Mongo PECL extension has been installed/enabled.
	*	Generate the connection string and establish a connection to the MongoDB.
	*/
	
	public function __construct()
	{
		if ( ! class_exists('Mongo'))
		{
			throw new \Mongodb_Exception("The MongoDB PECL extension has not been installed or enabled");
		}
		
		$this->connection_string();
		$this->connect();
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	Switch_db
	*	--------------------------------------------------------------------------------
	*
	*	Switch from default database to a different db
	*/
	public function switch_db($database = null)
	{
		if (empty($database))
		{
			throw new \Mongodb_Exception("To switch to a different MongoDB database, a new database name must be specified");
		}
		
		$this->dbname = $database;
		
		try
		{
			$this->db = $this->connection->{$this->dbname};
			return (TRUE);
		}
		catch (Exception $e)
		{
			throw new \Mongodb_Exception("Unable to switch Mongo Databases: {$e->getMessage()}");
		}
	}
		
	/**
	*	--------------------------------------------------------------------------------
	*	Drop_db
	*	--------------------------------------------------------------------------------
	*
	*	Drop a Mongo database
	*	@usage $this->mongo_db->drop_db("foobar");
	*/
	public function drop_db($database = '')
	{
		if (empty($database))
		{
			throw new \Mongodb_Exception('Failed to drop MongoDB database because name is empty');
		}
		
		else
		{
			try
			{
				$this->connection->{$database}->drop();
				return (TRUE);
			}
			catch (Exception $e)
			{
				throw new \Mongodb_Exception("Unable to drop Mongo database `{$database}`: {$e->getMessage()}");
			}
			
		}
	}
		
	/**
	*	--------------------------------------------------------------------------------
	*	Drop_collection
	*	--------------------------------------------------------------------------------
	*
	*	Drop a Mongo collection
	*	@usage $this->mongo_db->drop_collection('foo', 'bar');
	*/
	public function drop_collection($db = "", $col = "")
	{
		if (empty($db))
		{
			throw new \Mongodb_Exception('Failed to drop MongoDB collection because database name is empty');
		}
	
		if (empty($col))
		{
			throw new \Mongodb_Exception('Failed to drop MongoDB collection because collection name is empty');
		}
		
		else
		{
			try
			{
				$this->connection->{$db}->{$col}->drop();
				return TRUE;
			}
			catch (Exception $e)
			{
				throw new \Mongodb_Exception("Unable to drop Mongo collection `{$col}`: {$e->getMessage()}");
			}
		}
		
		return($this);
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
	*	@usage $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
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
	*	@usage $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
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
	*	@usage $this->mongo_db->or_where(array( array('foo'=>'bar', 'bar'=>'foo' ))->get('foobar');
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
	*	@usage $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
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
	*	@usage $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
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
	*	@usage $this->mongo_db->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
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
	*	@usage $this->mongo_db->where_gt('foo', 20);
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
	*	@usage $this->mongo_db->where_gte('foo', 20);
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
	*	@usage $this->mongo_db->where_lt('foo', 20);
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
	*	@usage $this->mongo_db->where_lte('foo', 20);
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
	*	@usage $this->mongo_db->where_between('foo', 20, 30);
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
	*	@usage $this->mongo_db->where_between_ne('foo', 20, 30);
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
	*	@usage $this->mongo_db->where_not_equal('foo', 1)->get('foobar');
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
	*	@usage $this->mongo_db->where_near('foo', array('50','50'))->get('foobar');
	*/
	
	function where_near($field = '', $co = array())
	{
		$this->__where_init($field);
		$this->where[$what]['$near'] = $co;
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
	*	@usage $this->mongo_db->like('foo', 'bar', 'im', FALSE, TRUE);
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
	*	@usage $this->mongo_db->where_between('foo', 20, 30);
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
	*	@usage $this->mongo_db->limit($x);
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
	*	@usage $this->mongo_db->offset($x);
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
	*	@usage $this->mongo_db->get_where('foo', array('bar' => 'something'));
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
	*	@usage $this->mongo_db->get('foo', array('bar' => 'something'));
	*/
	
	 public function get($collection = "")
	 {
	 	if (empty($collection))
	 	{
	 		throw new \Mongodb_Exception("In order to retreive documents from MongoDB");
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
	*	@usage $this->mongo_db->get('foo');
	*/
	
	public function count($collection = "") {
		if (empty($collection))
		{
			throw new \Mongodb_Exception("In order to retreive a count of documents from MongoDB");
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
	*	@usage $this->mongo_db->insert('foo', $data = array());
string|bool
	*/
	
	public function insert($collection = "", $insert = array())
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection selected to insert into");
		}
		
		if (count($insert) == 0 || !is_array($insert))
		{
			throw new \Mongodb_Exception("Nothing to insert into Mongo collection or insert is not an array");
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
		catch (MongoCursorException $e)
		{
			throw new \Mongodb_Exception("Insert of data into MongoDB failed: {$e->getMessage()}");
		}
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	UPDATE
	*	--------------------------------------------------------------------------------
	*
	*	Updates a single document
	*
	*	@usage $this->mongo_db->update('foo', $data = array());
	*/
	
	public function update($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection selected to update");
		}
		
		if (count($data) == 0 || ! is_array($data))
		{
			throw new \Mongodb_Exception("Nothing to update in Mongo collection or update is not an array");
		}
		
		try
		{
			$options = array_merge($options, array('fsync' => TRUE, 'multiple' => FALSE));
			$this->db->{$collection}->update($this->wheres, array('$set' => $data), $options);
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			throw new \Mongodb_Exception("Update of data into MongoDB failed: {$e->getMessage()}");
		}
		
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	UPDATE_ALL
	*	--------------------------------------------------------------------------------
	*
	*	Updates a collection of documents
	*
	*	@usage $this->mongo_db->update_all('foo', $data = array());
	*/
	
	public function update_all($collection = "", $data = array())
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection selected to update");
		}
		
		if (count($data) == 0 || ! is_array($data))
		{
			throw new \Mongodb_Exception("Nothing to update in Mongo collection or update is not an array");
		}
		
		try
		{
			$this->db->{$collection}->update($this->wheres, array('$set' => $data), array('fsync' => TRUE, 'multiple' => TRUE));
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			throw new \Mongodb_Exception("Update of data into MongoDB failed: {$e->getMessage()}");
		}
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	DELETE
	*	--------------------------------------------------------------------------------
	*
	*	delete document from the passed collection based upon certain criteria
	*
	*	@usage $this->mongo_db->delete('foo', $data = array());
	*/
	
	public function delete($collection = "")
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection selected to delete from");
		}
		
		try
		{
			$this->db->{$collection}->remove($this->wheres, array('fsync' => TRUE, 'justOne' => TRUE));
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			throw new \Mongodb_Exception("Delete of data into MongoDB failed: {$e->getMessage()}");
		}
		
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	DELETE_ALL
	*	--------------------------------------------------------------------------------
	*
	*	Delete all documents from the passed collection based upon certain criteria
	*
	*	@usage $this->mongo_db->delete_all('foo', $data = array());
	*/
	
	 public function delete_all($collection = "")
	 {
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection selected to delete from");
		}
		
		try
		{
			$this->db->{$collection}->remove($this->wheres, array('fsync' => TRUE, 'justOne' => FALSE));
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			throw new \Mongodb_Exception("Delete of data into MongoDB failed: {$e->getMessage()}");
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
	*	@usage $this->mongo_db->command(array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>TRUE));
	*/
	
	public function command($query = array())
	{
		try
		{
			$run = $this->db->command($query);
			return $run;
		}
		
		catch (MongoCursorException $e)
		{
			throw new \Mongodb_Exception("MongoDB command failed to execute: {$e->getMessage()}");
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
	*	@usage $this->mongo_db->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	*/
	
	public function add_index($collection = "", $keys = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection specified to add index to");
		}
		
		if (empty($keys) || ! is_array($keys))
		{
			throw new \Mongodb_Exception("Index could not be created to MongoDB Collection because no keys were specified");
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
			throw new \Mongodb_Exception("An error occured when trying to add an index to MongoDB Collection");
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
	*	@usage $this->mongo_db->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	*/
	
	public function remove_index($collection = "", $keys = array())
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection specified to remove index from");
		}
		
		if (empty($keys) || ! is_array($keys))
		{
			throw new \Mongodb_Exception("Index could not be removed from MongoDB Collection because no keys were specified");
		}
		
		if ($this->db->{$collection}->deleteIndex($keys, $options) == TRUE)
		{
			$this->_clear();
			return $this;
		}
		else
		{
			throw new \Mongodb_Exception("An error occured when trying to remove an index from MongoDB Collection");
		}
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	REMOVE_ALL_INDEXES
	*	--------------------------------------------------------------------------------
	*
	*	Remove all indexes from a collection.
	*
	*	@usage $this->mongo_db->remove_all_index($collection);
	*/
	
	public function remove_all_indexes($collection = "")
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection specified to remove all indexes from");
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
	*	@usage $this->mongo_db->list_indexes($collection);
	*/
	public function list_indexes($collection = "")
	{
		if (empty($collection))
		{
			throw new \Mongodb_Exception("No Mongo collection specified to remove all indexes from");
		}
		
		return ($this->db->{$collection}->getIndexInfo());
	}
	

	/**
	*	--------------------------------------------------------------------------------
	*	CONNECT TO MONGODB
	*	--------------------------------------------------------------------------------
	*
	*	Establish a connection to MongoDB using the connection string generated in
	*	the connection_string() method.  If 'mongo_persist_key' was set to TRUE in the
	*	config file, establish a persistent connection.  We allow for only the 'persist'
	*	option to be set because we want to establish a connection immediately.
	*/
	
	private function connect()
	{
		$options = array("connect" => TRUE);
		if ($this->persist === TRUE)
		{
			$options['persist'] = 'fuel_mongo_persist';
		}
		
		try
		{
			$this->connection = new Mongo($this->connection_string, $options);
			$this->db = $this->connection->{$this->dbname};
			return $this;	
		} 
		catch (MongoConnectionException $e)
		{
			throw new \Mongodb_Exception("Unable to connect to MongoDB: {$e->getMessage()}");
		}
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	BUILD CONNECTION STRING
	*	--------------------------------------------------------------------------------
	*
	*	Build the connection string from the config file.
	*/
	
	private function connection_string() 
	{
		\Config::load('db', TRUE);
		
		if ( ! ($config = \Config::get('db.mongodb.default')))
		{
			throw new \Mongodb_Exception('Missing configuration.');
		}
		
		extract($config);
		
		if($persistent === TRUE)
		{
			$this->persist = TRUE;
		}
				
		$connection_string = "mongodb://";
		
		if (empty($hostname))
		{
			throw new \Mongodb_Exception("The host must be set to connect to MongoDB");
		}
		
		if (empty($database))
		{
			throw new \Mongodb_Exception("The database must be set to connect to MongoDB");
		}
		
		if ( ! empty($username) && ! empty($password))
		{
			$connection_string .= "{$username}:{$password}@";
		}
		
		if (isset($port) && ! empty($port))
		{
			$connection_string .= "{$hostname}:{$port}";
		}
		else
		{
			$connection_string .= "{$hostname}";
		}
		
		$this->connection_string = trim($connection_string);
	}
	
	/**
	*	--------------------------------------------------------------------------------
	*	_clear
	*	--------------------------------------------------------------------------------
	*
	*	Resets the class variables to default settings
	*/
	
	private function _clear()
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
	
	private function _where_init($param)
	{
		if ( ! isset($this->wheres[$param]))
		{
			$this->wheres[ $param ] = array();
		}
	}
	
} // EOF