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

class Mongo_DbException extends \FuelException {}

class Mongo_Db
{
	/**
	 * Holds the current Mongo connection object
	 *
	 * @var  Mongo
	 */
	protected $connection = false;

	/**
	 * Holds the current DB reference on the connection object
	 *
	 * @var  Object
	 */
	protected $db;

	/**
	 * Whether to use a persistent connection
	 *
	 * @var  bool
	 */
	protected $persist = false;

	/**
	 * Whether to use the profiler
	 *
	 * @var  bool
	 */
	protected $profiling = false;

	/**
	 * Holds all the select options
	 *
	 * @var  array
	 */
	protected $selects = array();

	/**
	 * Holds all the where options.
	 *
	 * @var  array
	 */
	public $wheres = array();

	/**
	 * Holds the sorting options
	 *
	 * @var  array
	 */
	protected $sorts = array();

	/**
	 * Holds the limit of the number of results to return
	 *
	 * @var  int
	 */
	protected $limit = 999999;

	/**
	 * The offset to start from.
	 *
	 * @var  int
	 */
	protected $offset = 0;

	/**
	 * All the Mongo_Db instances
	 *
	 * @var  array
	 */
	protected static $instances = array();

	/**
	 *	Acts as a Multiton.  Will return the requested instance, or will create
	 *	a new one if it does not exist.
	 *
	 *	@param	string	$name	The instance name
	 *	@return	Mongo_Db
	 *	@throws	\Mongo_DbException
	 */
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
			throw new \Mongo_DbException('Invalid instance name given.');
		}

		static::$instances[$name] = new static($config);

		return static::$instances[$name];
	}

	/**
	 *	The class constructor
	 *	Automatically check if the Mongo PECL extension has been installed/enabled.
	 *	Generate the connection string and establish a connection to the MongoDB.
	 *
	 *	@param	array	$config		an array of config values
	 *	@throws	\Mongo_DbException
	 */
	public function __construct(array $config = array())
	{
		if ( ! class_exists('Mongo'))
		{
			throw new \Mongo_DbException("The MongoDB PECL extension has not been installed or enabled");
		}

		// Build up a connect options array for mongo
		$options = array("connect" => true);

		if ( ! empty($config['persistent']))
		{
			$options['persist'] = 'fuel_mongo_persist';
		}

		if ( ! empty($config['replicaset']))
		{
			$options['replicaSet'] = $config['replicaset'];
		}
		
		if ( ! empty($config['readPreference']))
		{
			$options['readPreference'] = $config['readPreference'];
		}

		$connection_string = "mongodb://";

		if (empty($config['hostname']))
		{
			throw new \Mongo_DbException("The host must be set to connect to MongoDB");
		}

		if (empty($config['database']))
		{
			throw new \Mongo_DbException("The database must be set to connect to MongoDB");
		}

		if ( ! empty($config['username']) and ! empty($config['password']))
		{
			$connection_string .= "{$config['username']}:{$config['password']}@";
		}

		if (isset($config['port']) and ! empty($config['port']))
		{
			$connection_string .= "{$config['hostname']}:{$config['port']}";
		}
		else
		{
			$connection_string .= "{$config['hostname']}";
		}

		if (\Arr::get($config, 'profiling') === true)
		{
			$this->profiling = true;
		}

		$connection_string .= "/{$config['database']}";

		// Let's give this a go
		try
		{
			$this->connection = new \MongoClient(trim($connection_string), $options);
			$this->db = $this->connection->{$config['database']};
			return $this;
		}
		catch (\MongoConnectionException $e)
		{
			throw new \Mongo_DbException("Unable to connect to MongoDB: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Drop a Mongo database
	 *
	 *	@param	string	$database		the database name
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->drop_db("foobar");
	 */
	public static function drop_db($database = null)
	{
		if (empty($database))
		{
			throw new \Mongo_DbException('Failed to drop MongoDB database because name is empty');
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
				throw new \Mongo_DbException("Unable to drop Mongo database `{$database}`: {$e->getMessage()}", $e->getCode());
			}

		}
	}

	/**
	 *	Drop a Mongo collection
	 *
	 *	@param	string	$db		the database name
	 *	@param	string	$col		the collection name
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->drop_collection('foo', 'bar');
	 */
	public static function drop_collection($db = '', $col = '')
	{
		if (empty($db))
		{
			throw new \Mongo_DbException('Failed to drop MongoDB collection because database name is empty');
		}

		if (empty($col))
		{
			throw new \Mongo_DbException('Failed to drop MongoDB collection because collection name is empty');
		}

		else
		{
			try
			{
				static::instance($db)->db->{$col}->drop();
				return true;
			}
			catch (\Exception $e)
			{
				throw new \Mongo_DbException("Unable to drop Mongo collection `{$col}`: {$e->getMessage()}", $e->getCode());
			}
		}
	}

	/**
	 *	Determine which fields to include OR which to exclude during the query process.
	 *	Currently, including and excluding at the same time is not available, so the
	 *	$includes array will take precedence over the $excludes array.  If you want to
	 *	only choose fields to exclude, leave $includes an empty array().
	 *
	 *	@param	array	$includes	which fields to include
	 *	@param	array	$excludes	which fields to exclude
	 *	@return	$this
	 *	@usage	$mongodb->select(array('foo', 'bar'))->get('foobar');
	 */
	public function select($includes = array(), $excludes = array())
	{
		if ( ! is_array($includes))
		{
			$includes = array($includes);
		}

		if ( ! is_array($excludes))
		{
			$excludes = array($excludes);
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
	 *	Get the documents based on these search parameters.  The $wheres array should
	 *	be an associative array with the field as the key and the value as the search
	 *	criteria.
	 *
	 *	@param	array	$wheres		an associative array with conditions, array(field => value)
	 *	@return	$this
	 *	@usage	$mongodb->where(array('foo' => 'bar'))->get('foobar');
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
	 *	Get the documents where the value of a $field may be something else
	 *
	 *	@param	array	$wheres		an associative array with conditions, array(field => value)
	 *	@return	$this
	 *	@usage	$mongodb->or_where(array( array('foo'=>'bar', 'bar'=>'foo' ))->get('foobar');
	 */
	public function or_where($wheres = array())
	{
		if (count($wheres) > 0)
		{
			if ( ! isset($this->wheres['$or']) or ! is_array($this->wheres['$or']))
			{
				$this->wheres['$or'] = array();
			}

			foreach ($wheres as $wh => $val)
			{
				$this->wheres['$or'][] = array($wh => $val);
			}
		}
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is in a given $in array().
	 *
	 *	@param	string	$field		the field name
	 *	@param	array	$in			an array of values to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	 */
	public function where_in($field = '', $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$in'] = $in;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is in all of a given $in array().
	 *
	 *	@param	string	$field		the field name
	 *	@param	array	$in			an array of values to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	 */
	public function where_in_all($field = '', $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$all'] = $in;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is not in a given $in array().
	 *
	 *	@param	string	$field		the field name
	 *	@param	array	$in			an array of values to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	 */
	public function where_not_in($field = '', $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$nin'] = $in;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is greater than $x
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the value to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_gt('foo', 20);
	 */
	public function where_gt($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is greater than or equal to $x
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the value to compare to
	 *	@return	Mongo_Db
	 *	@usage	$mongodb->where_gte('foo', 20);
	 */
	public function where_gte($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		return($this);
	}

	/**
	 *	Get the documents where the value of a $field is less than $x
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the value to compare to
	 *	@return	Mongo_Db
	 *	@usage	$mongodb->where_lt('foo', 20);
	 */
	public function where_lt($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$lt'] = $x;
		return($this);
	}

	/**
	 *	Get the documents where the value of a $field is less than or equal to $x
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the value to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_lte('foo', 20);
	 */
	public function where_lte($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$lte'] = $x;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is between $x and $y
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the value to compare to
	 *	@param	mixed	$y			the high value to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_between('foo', 20, 30);
	 */
	public function where_between($field = '', $x, $y)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		$this->wheres[$field]['$lte'] = $y;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is between but not equal to $x and $y
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the low value to compare to
	 *	@param	mixed	$y			the high value to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_between_ne('foo', 20, 30);
	 */
	public function where_between_ne($field = '', $x, $y)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		$this->wheres[$field]['$lt'] = $y;
		return $this;
	}

	/**
	 *	Get the documents where the value of a $field is not equal to $x
	 *
	 *	@param	string	$field		the field name
	 *	@param	mixed	$x			the value to compare to
	 *	@return	$this
	 *	@usage	$mongodb->where_not_equal('foo', 1)->get('foobar');
	 */
	public function where_ne($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$ne'] = $x;
		return $this;
	}

	/**
	 *	Get the documents nearest to an array of coordinates (your collection must have a geospatial index)
	 *
	 *	@param	string	$field		the field name
	 *	@param	array	$co			array of 2 coordinates
	 *	@return	$this
	 *	@usage	$mongodb->where_near('foo', array('50','50'))->get('foobar');
	 */
	public function where_near($field = '', $co = array())
	{
		$this->_where_init($field);
		$this->where[$field]['$near'] = $co;
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
	 *	@param	string	$field
	 *	@param	string	$value
	 *	@param	string	$flags
	 *	Allows for the typical regular expression flags:
	 *		i = case insensitive
	 *		m = multiline
	 *		x = can contain comments
	 *		l = locale
	 *		s = dotall, "." matches everything, including newlines
	 *		u = match unicode
	 *
	 *	@param	bool	$disable_start_wildcard
	 *	If this value evaluates to false, no starting line character "^" will be prepended
	 *	to the search value, representing only searching for a value at the start of
	 *	a new line.
	 *
	 *	@param	bool	$disable_end_wildcard
	 *	If this value evaluates to false, no ending line character "$" will be appended
	 *	to the search value, representing only searching for a value at the end of
	 *	a line.
	 *
	 *	@return	$this
	 *	@usage	$mongodb->like('foo', 'bar', 'im', false, true);
	 */
	public function like($field = '', $value = '', $flags = 'i', $disable_start_wildcard = false, $disable_end_wildcard = false)
	{
		$field = (string) trim($field);
		$this->_where_init($field);

		$value = (string) trim($value);
		$value = quotemeta($value);

		(bool) $disable_start_wildcard === false and $value = '^'.$value;
		(bool) $disable_end_wildcard === false and $value .= '$';

		$regex = "/$value/$flags";
		$this->wheres[$field] = new \MongoRegex($regex);

		return $this;
	}

	/**
	 *	Sort the documents based on the parameters passed. To set values to descending order,
	 *	you must pass values of either -1, false, 'desc', or 'DESC', else they will be
	 *	set to 1 (ASC).
	 *
	 *	@param	array	$fields		an associative array, array(field => direction)
	 *	@return	$this
	 *	@usage	$mongodb->where_between('foo', 20, 30);
	 */
	public function order_by($fields = array())
	{
		foreach ($fields as $col => $val)
		{
			if ($val == -1 or $val === false or strtolower($val) == 'desc')
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
	 *	Limit the result set to $x number of documents
	 *
	 *	@param	integer	$x			the max amount of documents to fetch
	 *	@return	$this
	 *	@usage	$mongodb->limit($x);
	 */
	public function limit($x = 99999)
	{
		if ($x !== null and is_numeric($x) and $x >= 1)
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
	 *	@param	integer	$x			the number of documents to skip
	 *	@return	$this
	 *	@usage	$mongodb->offset($x);
	 */
	public function offset($x = 0)
	{
		if ($x !== null and is_numeric($x) and $x >= 1)
		{
			$this->offset = (int) $x;
		}
		return $this;
	}

	/**
	 *	Get the documents based upon the passed parameters
	 *
	 *	@param	string	$collection		the collection name
	 *	@param	array	$where			an array of conditions, array(field => value)
	 *	@param	integer	$limit			the max amount of documents to fetch
	 *	@return	array
	 *	@usage	$mongodb->get_where('foo', array('bar' => 'something'));
	 */
	public function get_where($collection = '', $where = array(), $limit = 99999)
	{
		return ($this->where($where)->limit($limit)->get($collection));
	}

	/**
	 *	Get the document cursor from mongodb based upon the passed parameters
	 *
	 *	@param	string	$collection		the collection name
	 *	@usage	$mongodb->get_cursor('foo', array('bar' => 'something'));
	 *	@throws	\Mongo_DbException
	 */
	public function get_cursor($collection = "")
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("In order to retrieve documents from MongoDB you must provide a collection name.");
		}

		$documents = $this->db->{$collection}->find($this->wheres, $this->selects)->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);

		$this->_clear();

		return $documents;
	}

	/**
	 *	Get the documents based upon the passed parameters
	 *
	 *	@param	string	$collection		the collection name
	 *	@usage	$mongodb->get('foo', array('bar' => 'something'));
	 *	@return	array
	 *	@throws	\Mongo_DbException
	 */
	public function get($collection = "")
	{
		if ($this->profiling)
		{
			$query = json_encode(array(
			'type'			=> 'find',
			'collection'	=> $collection,
			'select'		=> $this->selects,
			'where'			=> $this->wheres,
			'limit'			=> $this->limit,
			'offset'		=> $this->offset,
			'sort'			=> $this->sorts,
			));

			$benchmark = \Profiler::start((string) $this->db, $query);
		}

		$documents = $this->get_cursor($collection);

		if (isset($benchmark))
		{
			\Profiler::stop($benchmark);
		}

		$returns = array();

		if ($documents and ! empty($documents))
		{
			foreach ($documents as $doc)
			{
				$returns[] = $doc;
			}
		}

		return $returns;
	}

	/**
	 * Get one document based upon the passed parameters
	 *
	 *	@param	string	$collection		the collection name
	 *	@return	mixed
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->get_one('foo');
	 */
	public function get_one($collection = "")
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("In order to retrieve documents from MongoDB you must provide a collection name.");
		}

		if ($this->profiling)
		{
			$query = json_encode(array(
			'type'			=> 'findOne',
			'collection'	=> $collection,
			'select'		=> $this->selects,
			'where'			=> $this->wheres,
			));

			$benchmark = \Profiler::start((string) $this->db, $query);
		}

		$returns = $this->db->{$collection}->findOne($this->wheres, $this->selects);

		if (isset($benchmark))
		{
			\Profiler::stop($benchmark);
		}

		$this->_clear();

		return $returns;
	}

	/**
	 *	Count the documents based upon the passed parameters
	 *
	 *	@param	string	$collection		the collection name
	 *	@param	boolean	$foundonly		send cursor limit and skip information to the count function, if applicable.
	 *	@return	mixed
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->count('foo');
	 */
	public function count($collection = '', $foundonly = false)
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("In order to retrieve a count of documents from MongoDB you must provide a collection name.");
		}

		if ($this->profiling)
		{
			$query = json_encode(array(
			'type'			=> 'count',
			'collection'	=> $collection,
			'where'			=> $this->wheres,
			'limit'			=> $this->limit,
			'offset'		=> $this->offset,
			));

			$benchmark = \Profiler::start((string) $this->db, $query);
		}

		$count = $this->db->{$collection}->find($this->wheres)->limit((int) $this->limit)->skip((int) $this->offset)->count($foundonly);

		if (isset($benchmark))
		{
			\Profiler::stop($benchmark);
		}

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
	 *	@param	string	$collection		the collection name
	 *	@param	array	$insert			an array of values to insert, array(field => value)
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->insert('foo', $data = array());
	 */
	public function insert($collection = '', $insert = array())
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection selected to insert");
		}

		if (empty($insert) or ! is_array($insert))
		{
			throw new \Mongo_DbException("Nothing to insert into Mongo collection or insert value is not an array");
		}

		try
		{
			if ($this->profiling)
			{
				$query = json_encode(array(
				'type'			=> 'insert',
				'collection'	=> $collection,
				'payload'		=> $insert,
				));

				$benchmark = \Profiler::start((string) $this->db, $query);
			}

			$this->db->{$collection}->insert($insert, array('fsync' => true));

			if (isset($benchmark))
			{
				\Profiler::stop($benchmark);
			}

			if (isset($insert['_id']))
			{
				return $insert['_id'];
			}
			else
			{
				return false;
			}
		}
		catch (\MongoCursorException $e)
		{
			throw new \Mongo_DbException("Insert of data into MongoDB failed: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Updates a single document
	 *
	 *	@param	string	$collection		the collection name
	 *	@param	array	$data			an associative array of values, array(field => value)
	 *	@param	array	$options		an associative array of options
	 *	@param	bool	$literal
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->update('foo', $data = array());
	 */
	public function update($collection = '', $data = array(), $options = array(), $literal = false)
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection selected to update");
		}

		if (empty($data) or ! is_array($data))
		{
			throw new \Mongo_DbException("Nothing to update in Mongo collection or update value is not an array");
		}

		try
		{
			$options = array_merge($options, array('fsync' => true, 'multiple' => false));

			if ($this->profiling)
			{
				$query = json_encode(array(
				'type'			=> 'update',
				'collection'	=> $collection,
				'where'			=> $this->wheres,
				'payload'		=> $data,
				'options'		=> $options,
				));

				$benchmark = \Profiler::start((string) $this->db, $query);
			}

			$this->db->{$collection}->update($this->wheres, (($literal) ? $data : array('$set' => $data)), $options);

			if (isset($benchmark))
			{
				\Profiler::stop($benchmark);
			}

			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \Mongo_DbException("Update of data into MongoDB failed: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Updates a collection of documents
	 *
	 *	@param	string	$collection		the collection name
	 *	@param	array	$data			an associative array of values, array(field => value)
	 *	@param	bool	$literal
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->update_all('foo', $data = array());
	 */
	public function update_all($collection = "", $data = array(), $literal = false)
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection selected to update");
		}

		if (empty($data) or ! is_array($data))
		{
			throw new \Mongo_DbException("Nothing to update in Mongo collection or update value is not an array");
		}

		try
		{
			if ($this->profiling)
			{
				$query = json_encode(array(
				'type'			=> 'updateAll',
				'collection'	=> $collection,
				'where'			=> $this->wheres,
				'payload'		=> $data,
				'literal'		=> $literal,
				));

				$benchmark = \Profiler::start((string) $this->db, $query);
			}

			$this->db->{$collection}->update($this->wheres, (($literal) ? $data : array('$set' => $data)), array('fsync' => true, 'multiple' => true));

			if (isset($benchmark))
			{
				\Profiler::stop($benchmark);
			}

			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \Mongo_DbException("Update of data into MongoDB failed: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Delete a document from the passed collection based upon certain criteria
	 *
	 *	@param	string	$collection		the collection name
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->delete('foo');
	 */
	public function delete($collection = '')
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection selected to delete from");
		}

		try
		{
			if ($this->profiling)
			{
				$query = json_encode(array(
				'type'			=> 'delete',
				'collection'	=> $collection,
				'where'			=> $this->wheres,
				));

				$benchmark = \Profiler::start((string) $this->db, $query);
			}

			$this->db->{$collection}->remove($this->wheres, array('fsync' => true, 'justOne' => true));

			if (isset($benchmark))
			{
				\Profiler::stop($benchmark);
			}

			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \Mongo_DbException("Delete of data into MongoDB failed: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Delete all documents from the passed collection based upon certain criteria.
	 *
	 *	@param	string	$collection		the collection name
	 *	@return	bool
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->delete_all('foo');
	 */
	public function delete_all($collection = '')
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection selected to delete from");
		}

		try
		{
			if ($this->profiling)
			{
				$query = json_encode(array(
				'type'			=> 'deleteAll',
				'collection'	=> $collection,
				'where'			=> $this->wheres,
				));

				$benchmark = \Profiler::start((string) $this->db, $query);
			}

			$this->db->{$collection}->remove($this->wheres, array('fsync' => true, 'justOne' => false));

			if (isset($benchmark))
			{
				\Profiler::stop($benchmark);
			}

			$this->_clear();
			return true;
		}
		catch (\MongoCursorException $e)
		{
			throw new \Mongo_DbException("Delete of data from MongoDB failed: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Runs a MongoDB command (such as GeoNear). See the MongoDB documentation for more usage scenarios:
	 *	http://dochub.mongodb.org/core/commands
	 *
	 *	@param	array	$query	a query array
	 *	@return	mixed
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->command(array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>TRUE));
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
			throw new \Mongo_DbException("MongoDB command failed to execute: {$e->getMessage()}", $e->getCode());
		}
	}

	/**
	 *	Ensure an index of the keys in a collection with optional parameters. To set values to descending order,
	 *	you must pass values of either -1, false, 'desc', or 'DESC', else they will be
	 *	set to 1 (ASC).
	 *
	 *	@param	string	$collection		the collection name
	 *	@param	array	$keys			an associative array of keys, array(field => direction)
	 *	@param	array	$options		an associative array of options
	 *	@return	$this
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	 */
	public function add_index($collection = '', $keys = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection specified to add an index to");
		}

		if (empty($keys) or ! is_array($keys))
		{
			throw new \Mongo_DbException("Index could not be created to MongoDB Collection because no keys were specified");
		}

		foreach ($keys as $col => $val)
		{
			if($val == -1 or $val === false or strtolower($val) == 'desc')
			{
				$keys[$col] = -1;
			}
			else
			{
				$keys[$col] = 1;
			}
		}

		if ($this->db->{$collection}->ensureIndex($keys, $options) == true)
		{
			$this->_clear();
			return $this;
		}
		else
		{
			throw new \Mongo_DbException("An error occurred when trying to add an index to MongoDB Collection");
		}
	}

	/**
	 *	Remove an index of the keys in a collection. To set values to descending order,
	 *	you must pass values of either -1, false, 'desc', or 'DESC', else they will be
	 *	set to 1 (ASC).
	 *
	 *	@param	string	$collection		the collection name
	 *	@param	array	$keys			an associative array of keys, array(field => direction)
	 *	@return	$this
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	 */
	public function remove_index($collection = '', $keys = array())
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection specified to remove an index from");
		}

		if (empty($keys) or ! is_array($keys))
		{
			throw new \Mongo_DbException("Index could not be removed from MongoDB Collection because no keys were specified");
		}

		if ($this->db->{$collection}->deleteIndex($keys) == true)
		{
			$this->_clear();
			return $this;
		}
		else
		{
			throw new \Mongo_DbException("An error occurred when trying to remove an index from MongoDB Collection");
		}
	}

	/**
	 *	Remove all indexes from a collection.
	 *
	 *	@param	string	$collection		the collection name
	 *	@return	$this
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->remove_all_index($collection);
	 */
	public function remove_all_indexes($collection = '')
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection specified to remove all indexes from");
		}
		$this->db->{$collection}->deleteIndexes();
		$this->_clear();
		return $this;
	}

	/**
	 *	Lists all indexes in a collection.
	 *
	 *	@param	string	$collection		the collection name
	 *	@return	mixed
	 *	@throws	\Mongo_DbException
	 *	@usage	$mongodb->list_indexes($collection);
	 */
	public function list_indexes($collection = '')
	{
		if (empty($collection))
		{
			throw new \Mongo_DbException("No Mongo collection specified to remove all indexes from");
		}

		return ($this->db->{$collection}->getIndexInfo());
	}

	/**
	 *	Returns a collection object so you can perform advanced queries, upserts, pushes and addtosets
	 *
	 *	@param	string	$collection		the collection name
	 *	@usage	$collection_name = $mongodb->get_collection('collection_name');
	 */
	public function get_collection($collection)
	{
		return ($this->db->{$collection});
	}

	/**
	 *	Returns all collection objects
	 *
	 *	@param	bool	$system_collections  whether or not to include system collections
	 *	@usage	$collections = $mongodb->list_collections();
	 */
	public function list_collections($system_collections = false)
	{
		return ($this->db->listCollections($system_collections));
	}

	/**
	 * Dump database or collection
	 *
	 * @param mixed  $collection_name  The collection name
	 * @param string $path             Path to write the dump do
	 *
	 * @usage $mongodb->dump();
	 * @usage $mongodb->dump(test, APPPATH . "tmp");
	 * @usage $mongodb->dump(["test", "test2"], APPPATH . "tmp");
	 *
	 * @return bool
	 */
	public function dump($collection_name = null, $path = null)
	{
		// set the default dump path if none given
		if (empty($path))
		{
			$path = APPPATH.'tmp'.DS.'mongo-'.date("/Y/m/d");
		}

		// backup full database
		if ($collection_name == null)
		{
			//get all collection in current database
			$mongo_collections = $this->list_collections();
			foreach ($mongo_collections as $mongo_collection)
			{
				$this->_write_dump($path, $mongo_collection);
			}
		}
		// Backup given collection`s
		else
		{
			$collection_name = (array) $collection_name;
			foreach ($collection_name as $name)
			{
				$mongo_collection = $this->get_collection($name);
				$this->_write_dump($path, $mongo_collection);
			}
		}

		return true;
	}

	/**
	 * Collect and write dump
	 *
	 * @param  string          $path
	 * @param  MongoCollection $mongo_collection
	 */
	protected function _write_dump($path, $mongo_collection)
	{
		if ( ! is_dir($path))
		{
			\Config::load('file', true);
			mkdir($path, \Config::get('file.chmod.folders', 0777), true);
		}

		$collection_name = $mongo_collection->getName();

		// get all documents in current collection
		$documents = $mongo_collection->find();

		// collect data
		$array_data = iterator_to_array($documents);
		$json_data = \Format::forge($array_data)->to_json();

		return \File::update($path, $collection_name, $json_data);
	}

	/**
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
	 *	Prepares parameters for insertion in $wheres array().
	 *
	 *	@param	string	$param		the field name
	 */
	protected function _where_init($param)
	{
		if ( ! isset($this->wheres[$param]))
		{
			$this->wheres[ $param ] = array();
		}
	}
}
