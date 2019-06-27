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

class Cache_Storage_Xcache extends \Cache_Storage_Driver
{
	/**
	 * @const  string  Tag used for opening & closing cache properties
	 */
	const PROPS_TAG = 'Fuel_Cache_Properties';

	/**
	 * @var  array  driver specific configuration
	 */
	protected $config = array();

	// ---------------------------------------------------------------------

	public function __construct($identifier, $config)
	{
		parent::__construct($identifier, $config);

		$this->config = isset($config['xcache']) ? $config['xcache'] : array();

		// make sure we have an id
		$this->config['cache_id'] = $this->_validate_config('cache_id', isset($this->config['cache_id'])
			? $this->config['cache_id'] : 'fuel');

		// check for an expiration override
		$this->expiration = $this->_validate_config('expiration', isset($this->config['expiration'])
			? $this->config['expiration'] : $this->expiration);

		// do we have the PHP XCache extension available
		if ( ! function_exists('xcache_set') )
		{
			throw new \FuelException('Your PHP installation doesn\'t have XCache loaded.');
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
			$index = xcache_get($this->config['cache_id'].$sections);

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
		// get the XCache key for the cache identifier
		$key = $this->_get_key(true);

		// delete the key from the xcache store
		$key and xcache_unset($key);

		$this->reset();
	}

	/**
	 * Purge all caches
	 *
	 * @param   string  $section  limit purge to subsection
	 * @return  bool
	 */
	public function delete_all($section)
	{
		// determine the section index name
		$section = $this->config['cache_id'].(empty($section) ? '' : '.'.$section);

		// get the directory index
		$index = xcache_get($this->config['cache_id'].'__DIR__');

		if (is_array($index))
		{
			$dirs = array();
			foreach ($index as $dir)
			{
				if (strpos($dir, $section) === 0)
				{
					$dirs[] = $dir;
					$list = xcache_get($dir);
					foreach ($list as $item)
					{
						xcache_unset($item[0]);
					}
					xcache_unset($dir);
				}
			}

			// update the directory index
			$dirs and xcache_set($this->config['cache_id'].'__DIR__', array_diff($index, $dirs));
		}
	}

	// ---------------------------------------------------------------------

	/**
	 * Prepend the cache properties
	 *
	 * @return  string
	 */
	protected function prep_contents()
	{
		$properties = array(
			'created'          => $this->created,
			'expiration'       => $this->expiration,
			'dependencies'     => $this->dependencies,
			'content_handler'  => $this->content_handler,
		);
		$properties = '{{'.static::PROPS_TAG.'}}'.json_encode($properties).'{{/'.static::PROPS_TAG.'}}';

		return $properties.$this->contents;
	}

	/**
	 * Remove the prepended cache properties and save them in class properties
	 *
	 * @param   string  $payload
	 * @throws  \UnexpectedValueException
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
		// get the xcache key for the cache identifier
		$key = $this->_get_key();

		$payload = $this->prep_contents();

		// adjust the expiration, xcache uses a TTL instead of a timestamp
		$expiration = is_null($this->expiration) ? 0 : (int) ($this->expiration - $this->created);

		// write it to the xcache store
		if (xcache_set($key, $payload, $expiration) === false)
		{
			throw new \RuntimeException('Xcache returned failed to write. Check your configuration.');
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
		// get the xcache key for the cache identifier
		$key = $this->_get_key();

		// fetch the cached data from the xcache store
		$payload = xcache_get($key);

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
	 * @param   string  $name   name of the config variable to validate
	 * @param   mixed   $value
	 * @return  mixed
	 */
	private function _validate_config($name, $value)
	{
		switch ($name)
		{
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
	 * get's the xcache key belonging to the cache identifier
	 *
	 * @param   bool  $remove  if true, remove the key retrieved from the index
	 * @return  string
	 */
	protected function _get_key($remove = false)
	{
		// get the current index information
		list($identifier, $sections, $index) = $this->_get_index();

		// get the key from the index
		$key = isset($index[$identifier][0]) ? $index[$identifier][0] : false;

		if ($remove === true)
		{
			if ( $key !== false )
			{
				unset($index[$identifier]);
				xcache_set($this->config['cache_id'].$sections, $index);
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
		return array($identifier, $sections, xcache_get($this->config['cache_id'].$sections));
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

		// store the key in the index and write the index back
		$index[$identifier] = array($key, $this->created);
		xcache_set($this->config['cache_id'].$sections, array_merge($index, array($identifier => array($key, $this->created))));

		// get the directory index
		$index = xcache_get($this->config['cache_id'].'__DIR__');

		if (is_array($index))
		{
			if (!in_array($this->config['cache_id'].$sections, $index))
			{
				$index[] = $this->config['cache_id'].$sections;
			}
		}
		else
		{
			$index = array($this->config['cache_id'].$sections);
		}

		// update the directory index
		xcache_set($this->config['cache_id'].'__DIR__', $index, 0);
	}
}
