<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;



class Cache_Storage_Redis extends \Cache_Storage_Driver
{

	/**
	 * @const  string  Tag used for opening & closing cache properties
	 */
	const PROPS_TAG = 'Fuel_Cache_Properties';

	/**
	 * @var  array  driver specific configuration
	 */
	protected $config = array();

	/*
	 * @var  Redis  storage for the redis object
	 */
	protected static $redis = false;

	// ---------------------------------------------------------------------

	public function __construct($identifier, $config)
	{
		parent::__construct($identifier, $config);

		$this->config = isset($config['redis']) ? $config['redis'] : array();

		// make sure we have a redis id
		$this->config['cache_id'] = $this->_validate_config('cache_id', isset($this->config['cache_id'])
			? $this->config['cache_id'] : 'fuel');

		// check for an expiration override
		$this->expiration = $this->_validate_config('expiration', isset($this->config['expiration'])
			? $this->config['expiration'] : $this->expiration);

		// make sure we have a redis database configured
		$this->config['database'] = $this->_validate_config('database', isset($this->config['database'])
			? $this->config['database'] : 'default');

		if (static::$redis === false)
		{
			// get the redis database instance
			try
			{
				static::$redis = \Redis_Db::instance($this->config['database']);
			}
			catch (\Exception $e)
			{
				throw new \FuelException('Can not connect to the Redis engine. The error message says "'.$e->getMessage().'".');
			}

			// get the redis version
			preg_match('/redis_version:(.*?)\n/', static::$redis->info(), $info);
			if (version_compare(trim($info[1]), '1.2') < 0)
			{
				throw new \FuelException('Version 1.2 or higher of the Redis NoSQL engine is required to use the redis cache driver.');
			}
		}
	}

	// ---------------------------------------------------------------------

	/**
	 * Check if other caches or files have been changed since cache creation
	 *
	 * @param   array
	 * @return  bool
	 */
	public function check_dependencies(array $dependencies)
	{
		foreach($dependencies as $dep)
		{
			// get the section name and identifier
			$sections = explode('.', $dep);
			if (count($sections) > 1)
			{
				$identifier = array_pop($sections);
				$sections = '.'.implode('.', $sections);

			}
			else
			{
				$identifier = $dep;
				$sections = '';
			}

			// get the cache index
			$index = static::$redis->get($this->config['cache_id'].':index:'.$sections);
			is_null($index) or $index = $this->_unserialize($index);

			// get the key from the index
			$key = isset($index[$identifier][0]) ? $index[$identifier] : false;

			// key found and newer?
			if ($key === false or $key[1] > $this->created)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Delete Cache
	 */
	public function delete()
	{
		// get the key for the cache identifier
		$key = $this->_get_key(true);

		// delete the key from the redis server
		if ($key and static::$redis->del($key) === false)
		{
			// do something here?
		}

		$this->reset();
	}

	/**
	 * Purge all caches
	 *
	 * @param   limit purge to subsection
	 * @return  bool
	 */
	public function delete_all($section)
	{
		// determine the section index name
		$section = empty($section) ? '' : '.'.$section;

		// get the directory index
		$index = static::$redis->get($this->config['cache_id'].':dir:');
		is_null($index) or $index = $this->_unserialize($index);

		if (is_array($index))
		{
			if (!empty($section))
			{
				// limit the delete if we have a valid section
				$dirs = array();
				foreach ($index as $entry)
				{
					if ($entry == $section or strpos($entry, $section.'.') === 0)
					{
						$dirs[] = $entry;
					}
				}
			}
			else
			{
				// else delete the entire contents of the cache
				$dirs = $index;
			}

			// loop through the selected indexes
			foreach ($dirs as $dir)
			{
				// get the stored cache entries for this index
				$list = static::$redis->get($this->config['cache_id'].':index:'.$dir);
				if (is_null($list))
				{
					$list = array();
				}
				else
				{
					$list = $this->_unserialize($list);
				}

				// delete all stored keys
				foreach($list as $item)
				{
					static::$redis->del($item[0]);
				}

				// and delete the index itself
				static::$redis->del($this->config['cache_id'].':index:'.$dir);
			}

			// update the directory index
			static::$redis->set($this->config['cache_id'].':dir:', $this->_serialize(array_diff($index, $dirs)));
		}
	}

	// ---------------------------------------------------------------------

	/**
	 * Translates a given identifier to a valid redis key
	 *
	 * @param   string
	 * @return  string
	 */
	protected function identifier_to_key( $identifier )
	{
		return $this->config['cache_id'].':'.$identifier;
	}

	/**
	 * Prepend the cache properties
	 *
	 * @return string
	 */
	protected function prep_contents()
	{
		$properties = array(
			'created'          => $this->created,
			'expiration'       => $this->expiration,
			'dependencies'     => $this->dependencies,
			'content_handler'  => $this->content_handler
		);
		$properties = '{{'.static::PROPS_TAG.'}}'.json_encode($properties).'{{/'.static::PROPS_TAG.'}}';

		return $properties.$this->contents;
	}

	/**
	 * Remove the prepended cache properties and save them in class properties
	 *
	 * @param   string
	 * @throws  UnexpectedValueException
	 */
	protected function unprep_contents($payload)
	{
		$properties_end = strpos($payload, '{{/'.static::PROPS_TAG.'}}');
		if ($properties_end === FALSE)
		{
			throw new \UnexpectedValueException('Cache has bad formatting');
		}

		$this->contents = substr($payload, $properties_end + strlen('{{/'.static::PROPS_TAG.'}}'));
		$props = substr(substr($payload, 0, $properties_end), strlen('{{'.static::PROPS_TAG.'}}'));
		$props = json_decode($props, true);
		if ($props === NULL)
		{
			throw new \UnexpectedValueException('Cache properties retrieval failed');
		}

		$this->created          = $props['created'];
		$this->expiration       = is_null($props['expiration']) ? null : (int) ($props['expiration'] - time());
		$this->dependencies     = $props['dependencies'];
		$this->content_handler  = $props['content_handler'];
	}

	/**
	 * Save a cache, this does the generic pre-processing
	 *
	 * @return  bool  success
	 */
	protected function _set()
	{
		// get the key for the cache identifier
		$key = $this->_get_key();

		// write the cache
		static::$redis->set($key, $this->prep_contents());
		if ( ! empty($this->expiration))
		{
			static::$redis->expireat($key, $this->expiration);
		}

		// update the index
		$this->_update_index($key);

		return true;
	}

	/**
	 * Load a cache, this does the generic post-processing
	 *
	 * @return  bool  success
	 */
	protected function _get()
	{
		// get the key for the cache identifier
		$key = $this->_get_key();

		// fetch the session data from the redis server
		$payload = static::$redis->get($key);
		try
		{
			$this->unprep_contents($payload);
		}
		catch (\UnexpectedValueException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * validate a driver config value
	 *
	 * @param   string  name of the config variable to validate
	 * @param   mixed   value
	 * @return  mixed
	 */
	protected function _validate_config($name, $value)
	{
		switch ($name)
		{
			case 'database':
				// do we have a database config
				if (empty($value) or ! is_string($value))
				{
					$value = 'default';
				}
			break;

			case 'cache_id':
				if (empty($value) or ! is_string($value))
				{
					$value = 'fuel';
				}
			break;

			case 'expiration':
				if (empty($value) or ! is_numeric($value))
				{
					$value = null;
				}
			break;

			default:
			break;
		}

		return $value;
	}

	/**
	 * get's the redis key belonging to the cache identifier
	 *
	 * @param   bool  if true, remove the key retrieved from the index
	 * @return  string
	 */
	protected function _get_key($remove = false)
	{
		// get the current index information
		list($identifier, $sections, $index) = $this->_get_index();
		$index = $index === null ? array() : $index = $this->_unserialize($index);

		// get the key from the index
		$key = isset($index[$identifier][0]) ? $index[$identifier][0] : false;

		if ($remove === true)
		{
			if ( $key !== false )
			{
				unset($index[$identifier]);
				static::$redis->set($this->config['cache_id'].':index:'.$sections, $this->_serialize($index));
			}
		}
		else
		{
			// create a new key if needed
			$key === false and $key = $this->_new_key();
		}

		return $key;
	}

	/**
	 * generate a new unique key for the current identifier
	 *
	 * @return  string
	 */
	protected function _new_key()
	{
		$key = '';
		while (strlen($key) < 32)
		{
			$key .= mt_rand(0, mt_getrandmax());
		}
		return md5($this->config['cache_id'].'_'.uniqid($key, TRUE));
	}

	/**
	 * Get the section index
	 *
	 * @return  array  containing the identifier, the sections, and the section index
	 */
	protected function _get_index()
	{
		// get the section name and identifier
		$sections = explode('.', $this->identifier);
		if (count($sections) > 1)
		{
			$identifier = array_pop($sections);
			$sections = '.'.implode('.', $sections);
		}
		else
		{
			$identifier = $this->identifier;
			$sections = '';
		}

		// get the cache index and return it
		return array($identifier, $sections, static::$redis->get($this->config['cache_id'].':index:'.$sections));
	}

	/**
	 * Update the section index
	 *
	 * @param  string  cache key
	 */
	protected function _update_index($key)
	{
		// get the current index information
		list($identifier, $sections, $index) = $this->_get_index();
		$index = $index === null ? array() : $index = $this->_unserialize($index);

		// store the key in the index and write the index back
		$index[$identifier] = array($key, $this->created);

		static::$redis->set($this->config['cache_id'].':index:'.$sections, $this->_serialize($index));

		// get the directory index
		$index = static::$redis->get($this->config['cache_id'].':dir:');
		$index = $index === null ? array() : $index = $this->_unserialize($index);

		if (is_array($index))
		{
			if ( ! in_array($sections, $index))
			{
				$index[] = $sections;
			}
		}
		else
		{
			$index = array($sections);
		}

		// update the directory index
		static::$redis->set($this->config['cache_id'].':dir:', $this->_serialize($index));
	}

	/**
	 * Serialize an array
	 *
	 * This function first converts any slashes found in the array to a temporary
	 * marker, so when it gets unserialized the slashes will be preserved
	 *
	 * @param   array
	 * @return  string
	 */
	protected function _serialize($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				if (is_string($val))
				{
					$data[$key] = str_replace('\\', '{{slash}}', $val);
				}
			}
		}
		else
		{
			if (is_string($data))
			{
				$data = str_replace('\\', '{{slash}}', $data);
			}
		}

		return serialize($data);
	}

	/**
	 * Unserialize
	 *
	 * This function unserializes a data string, then converts any
	 * temporary slash markers back to actual slashes
	 *
	 * @param   array
	 * @return  string
	 */
	protected function _unserialize($data)
	{
		$data = @unserialize(stripslashes($data));

		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				if (is_string($val))
				{
					$data[$key] = str_replace('{{slash}}', '\\', $val);
				}
			}

			return $data;
		}

		return (is_string($data)) ? str_replace('{{slash}}', '\\', $data) : $data;
	}
}
