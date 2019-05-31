<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Database_Query
{
	/**
	 * @var  int  Query type
	 */
	protected $_type;

	/**
	 * @var  int  Cache lifetime
	 */
	protected $_lifetime;

	/**
	 * @var  string  Cache key
	 */
	protected $_cache_key = null;

	/**
	 * @var  boolean  Cache all results
	 */
	protected $_cache_all = true;

	/**
	 * @var  boolean  To allow restore of the global caching status
	 */
	protected $_caching = null;

	/**
	 * @var  string  SQL statement
	 */
	protected $_sql;

	/**
	 * @var  array  Quoted query parameters
	 */
	protected $_parameters = array();

	/**
	 * @var  bool  Return results as associative arrays or objects
	 */
	protected $_as_object = false;

	/**
	 * @var  Database_Connection  Connection to use when compiling the SQL
	 */
	protected $_connection = null;

	/**
	 * Creates a new SQL query of the specified type.
	 *
	 * @param string $sql   query string
	 * @param integer $type query type: DB::SELECT, DB::INSERT, etc
	*/
	public function __construct($sql, $type = null)
	{
		$this->_type = $type;
		$this->_sql = $sql;
	}

	/**
	 * Return the SQL query string.
	 *
	 * @return  string
	 */
	final public function __toString()
	{
		try
		{
			// Return the SQL string
			return $this->compile();
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Get the type of the query.
	 *
	 * @return  integer
	 */
	public function type()
	{
		return $this->_type;
	}

	/**
	 * Enables the query to be cached for a specified amount of time.
	 *
	 * @param   integer $lifetime  number of seconds to cache or null for default
	 * @param   string  $cache_key name of the cache key to be used or null for default
	 * @param   boolean $cache_all if true, cache all results, even empty ones
	 *
	 * @return  $this
	 */
	public function cached($lifetime = null, $cache_key = null, $cache_all = true)
	{
		$this->_lifetime = $lifetime;
		$this->_cache_all = (bool) $cache_all;
		is_string($cache_key) and $this->_cache_key = $cache_key;

		return $this;
	}
	/**
	 * Per query cache controller setter/getter
	 *
	 * @param   bool   $bool  whether to enable it [optional]
	 *
	 * @return  $this
	 */
	public function caching($bool = null)
	{
		if (is_bool($bool) or is_null($bool))
		{
			$this->_caching = $bool;
		}

		return $this;
	}


	/**
	 * Returns results as associative arrays
	 *
	 * @return  $this
	 */
	public function as_assoc()
	{
		$this->_as_object = false;

		return $this;
	}

	/**
	 * Returns results as objects
	 *
	 * @param   mixed $class classname or true for stdClass
	 *
	 * @return  $this
	 */
	public function as_object($class = true)
	{
		$this->_as_object = $class;

		return $this;
	}

	/**
	 * Set the value of a parameter in the query.
	 *
	 * @param   string $param parameter key to replace
	 * @param   mixed  $value value to use
	 *
	 * @return  $this
	 */
	public function param($param, $value)
	{
		// Add or overload a new parameter
		$this->_parameters[$param] = $value;

		return $this;
	}

	/**
	 * Bind a variable to a parameter in the query.
	 *
	 * @param  string $param parameter key to replace
	 * @param  mixed  $var   variable to use
	 *
	 * @return $this
	 */
	public function bind($param, & $var)
	{
		// Bind a value to a variable
		$this->_parameters[$param] =& $var;

		return $this;
	}

	/**
	 * Add multiple parameters to the query.
	 *
	 * @param array $params list of parameters
	 *
	 * @return  $this
	 */
	public function parameters(array $params)
	{
		// Merge the new parameters in
		$this->_parameters = $params + $this->_parameters;

		return $this;
	}

	/**
	 * Set a DB connection to use when compiling the SQL
	 *
	 * @param  mixed  $db
	 *
	 * @return  $this
	 */
	public function set_connection($db)
	{
		if ( ! $db instanceof \Database_Connection)
		{
			// Get the database instance
			$db = \Database_Connection::instance($db);
		}
		$this->_connection = $db;

		return $this;
	}

	/**
	 * Compile the SQL query and return it. Replaces any parameters with their
	 * given values.
	 *
	 * @param   mixed $db Database instance or instance name
	 *
	 * @return  string
	 */
	public function compile($db = null)
	{
		if ($this->_connection !== null and $db === null)
		{
			$db = $this->_connection;
		}

		if ( ! $db instanceof \Database_Connection)
		{
			// Get the database instance
			$db = $this->_connection ?: \Database_Connection::instance($db);
		}

		// Import the SQL locally
		$sql = $this->_sql;

		if ( ! empty($this->_parameters))
		{
			// Quote all of the values
			$values = array_map(array($db, 'quote'), $this->_parameters);

			// Replace the values in the SQL
			$sql = \Str::tr($sql, $values);
		}

		return trim($sql);
	}

	/**
	 * Execute the current query on the given database.
	 *
	 * @param   mixed   $db Database instance or name of instance
	 *
	 * @return  object   Database_Result for SELECT queries
	 * @return  mixed    the insert id for INSERT queries
	 * @return  integer  number of affected rows for all other queries
	 */
	public function execute($db = null)
	{
		if ($this->_connection !== null and $db === null)
		{
			$db = $this->_connection;
		}

		if ( ! is_object($db))
		{
			// Get the database instance. If this query is a instance of
			// Database_Query_Builder_Select then use the slave connection if configured
			$db = \Database_Connection::instance($db, null, ! $this instanceof \Database_Query_Builder_Select);
		}

		// Compile the SQL query
		$sql = $this->compile($db);

		// make sure we have a SQL type to work with
		if (is_null($this->_type))
		{
			// get the SQL statement type without having to duplicate the entire statement
			$stmt = preg_split('/[\s]+/', ltrim(substr($sql, 0, 11), '('), 2);
			switch(strtoupper(reset($stmt)))
			{
				case 'DESCRIBE':
				case 'EXECUTE':
				case 'EXPLAIN':
				case 'SELECT':
				case 'SHOW':
					$this->_type = \DB::SELECT;
					break;
				case 'INSERT':
				case 'REPLACE':
					$this->_type = \DB::INSERT;
					break;
				case 'UPDATE':
					$this->_type = \DB::UPDATE;
					break;
				case 'DELETE':
					$this->_type = \DB::DELETE;
					break;
				default:
					$this->_type = 0;
			}
		}

		// fetch the result caching flag
		$caching = $this->_caching or $db->caching();

		if ($caching and ! empty($this->_lifetime) and $this->_type === \DB::SELECT)
		{
			$cache_key = empty($this->_cache_key) ?
				'db.'.md5('Database_Connection::query("'.$db.'", "'.$sql.'")') : $this->_cache_key;
			$cache = \Cache::forge($cache_key);
			try
			{
				return $db->cache($cache->get(), $sql, $this->_as_object);
			}
			catch (\CacheNotFoundException $e) {}
		}

		// Execute the query
		\DB::$query_count++;
		$result = $db->query($this->_type, $sql, $this->_as_object, $caching);

		// Cache the result if needed
		if (isset($cache) and ($this->_cache_all or $result->count()))
		{
			$cache->set_expiration($this->_lifetime)->set_contents($result->as_array())->set();
		}

		return $result;
	}

}
