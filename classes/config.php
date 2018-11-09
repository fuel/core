<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class ConfigException extends \FuelException { }

class Config
{
	/**
	 * @var    array    $loaded_files    array of loaded files
	 */
	public static $loaded_files = array();

	/**
	 * @var    array    $items           the master config array
	 */
	public static $items = array();

	/**
	 * @var    array    $itemcache       the dot-notated item cache
	 */
	protected static $itemcache = array();

	/**
	 * Loads a config file.
	 *
	 * @param    mixed    $file         string file | config array | Config_Interface instance
	 * @param    mixed    $group        null for no group, true for group is filename, false for not storing in the master config
	 * @param    bool     $reload       true to force a reload even if the file is already loaded
	 * @param    bool     $overwrite    true for array_merge, false for \Arr::merge
	 * @return   array                  the (loaded) config array
	 * @throws  \FuelException
	 */
	public static function load($file, $group = null, $reload = false, $overwrite = false)
	{
		// storage for the config
		$config = array();

		// Config_Instance class
		$class = null;

		// name of the config group
		$name = $group === true ? $file : ($group === null ? null : $group);

		// need to store flag
		$cache = ($group !== false);

		// process according to input type
		if ( ! empty($file))
		{
			// we've got a config filename
			if (is_string($file))
			{
				// if we have this config in cache, load it
				if ( ! $reload and
					array_key_exists($file, static::$loaded_files))
				{
					if ($group !== false and $name !== null and isset(static::$items[$name]))
					{
						// fetch the cached config
						$config = static::$items[$name];
					}
					else
					{
						// no config fetched
						$config = false;
					}

					// we don't want to cache this config later!
					$cache = false;
				}

				// if not, construct a Config instance and load it
				else
				{
					$info = pathinfo($file);
					$type = 'php';
					if (isset($info['extension']))
					{
						$type = $info['extension'];
						// Keep extension when it's an absolute path, because the finder won't add it
						if ($file[0] !== '/' and $file[1] !== ':')
						{
							$file = substr($file, 0, -(strlen($type) + 1));
						}
					}
					$class = '\\Config_'.ucfirst($type);

					if (class_exists($class))
					{
						static::$loaded_files[$file] = true;
						$class = new $class($file);
					}
					else
					{
						throw new \FuelException(sprintf('Invalid config type "%s".', $type));
					}
				}
			}

			// we've got an array of config data
			elseif (is_array($file))
			{
				$config = $file;
			}

			// we've got an already created Config instance class
			elseif ($file instanceOf Config_Interface)
			{
				$class = $file;
			}

			// don't know what we got, bail out
			else
			{
				throw new \FuelException(sprintf('Invalid config file argument'));
			}

			// if we have a Config instance class?
			if (is_object($class))
			{
				// then load its config
 				try
				{
					$config = $class->load($overwrite, ! $reload);
				}
				catch (\ConfigException $e)
				{
					$config = false;
				}

				// and update the group if needed
				if ($group === true)
				{
					$name = $class->group();
				}
			}
		}

		// no arguments?
		else
		{
			throw new \FuelException(sprintf('No valid config file argument given'));
		}

		// do we have a valid config loaded and do we need to cache it?
		if ( ! empty($config) and $cache)
		{
			// do we need to load it in the global config?
			if ($name === null)
			{
				static::$items = $reload ? $config : ($overwrite ? array_merge(static::$items, $config) : \Arr::merge(static::$items, $config));
				static::$itemcache = array();
			}

			// or in a named config
			else
			{
				if ( ! isset(static::$items[$name]) or $reload)
				{
					static::$items[$name] = array();
				}

				if ($overwrite)
				{
					\Arr::set(static::$items, $name, array_merge(\Arr::get(static::$items, $name, array()), $config));
				}
				else
				{
					\Arr::set(static::$items, $name, \Arr::merge(\Arr::get(static::$items, $name, array()), $config));
				}

				foreach (static::$itemcache as $key => $value)
				{
					if (strpos($key, $name) === 0)
					{
						unset(static::$itemcache[$key]);
					}
				}
			}
		}

		// return the fetched config
		return $config;
	}

	/**
	 * Save a config array to disc.
	 *
	 * @param   string          $file      desired file name
	 * @param   string|array    $config    master config array key or config array
	 * @return  bool                       false when config is empty or invalid else \File::update result
	 * @throws  \FuelException
	 */
	public static function save($file, $config)
	{
		if ( ! is_array($config))
		{
			if ( ! isset(static::$items[$config]))
			{
				return false;
			}
			$config = static::$items[$config];
		}

		$info = pathinfo($file);
		$type = 'php';

		if (isset($info['extension']))
		{
			$type = $info['extension'];
			// Keep extension when it's an absolute path, because the finder won't add it
			if ($file[0] !== '/' and $file[1] !== ':')
			{
				$file = substr($file, 0, -(strlen($type) + 1));
			}
		}

		$class = '\\Config_'.ucfirst($type);

		if ( ! class_exists($class))
		{
			throw new \FuelException(sprintf('Invalid config type "%s".', $type));
		}

		$driver = new $class($file);

		return $driver->save($config);
	}

	/**
	 * Returns a (dot notated) config setting
	 *
	 * @param   string   $item      name of the config item, can be dot notated
	 * @param   mixed    $default   the return value if the item isn't found
	 * @return  mixed               the config setting or default if not found
	 */
	public static function get($item, $default = null)
	{
		if (array_key_exists($item, static::$items))
		{
			return static::$items[$item];
		}
		elseif ( ! array_key_exists($item, static::$itemcache))
		{
			// cook up something unique
			$miss = new \stdClass();

			$val = \Arr::get(static::$items, $item, $miss);

			// so we can detect a miss here...
			if ($val === $miss)
			{
				return $default;
			}

			static::$itemcache[$item] = $val;
		}

		return \Fuel::value(static::$itemcache[$item]);
	}

	/**
	 * Sets a (dot notated) config item
	 *
	 * @param    string   $item   a (dot notated) config key
	 * @param    mixed    $value  the config value
	 */
	public static function set($item, $value)
	{
		strpos($item, '.') === false or static::$itemcache[$item] = $value;
		\Arr::set(static::$items, $item, $value);
	}

	/**
	 * Deletes a (dot notated) config item
	 *
	 * @param    string       $item  a (dot notated) config key
	 * @return   array|bool          the \Arr::delete result, success boolean or array of success booleans
	 */
	public static function delete($item)
	{
		if (isset(static::$itemcache[$item]))
		{
			unset(static::$itemcache[$item]);
		}
		return \Arr::delete(static::$items, $item);
	}
}
