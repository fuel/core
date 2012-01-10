<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
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
	 * Loads a config file.
	 *
	 * @param    mixed    $file         string file | config array | Config_Interface instance
	 * @param    mixed    $group        null for no group, true for group is filename, false for not storing in the master config
	 * @param    bool     $overwrite    true for array_merge, false for \Arr::merge
	 * @return   array                  the (loaded) config array
	 */
	public static function load($file, $group = null, $reload = false, $overwrite = false)
	{
		if ( ! $reload and
		     ! is_array($file) and
		     ! is_object($file) and
		    array_key_exists($file, static::$loaded_files))
		{
			return false;
		}

		$config = array();
		if (is_array($file))
		{
			$config = $file;
		}
		elseif (is_string($file))
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
				$file = new $class($file);
			}
			else
			{
				throw new \FuelException(sprintf('Invalid config type "%s".', $type));
			}
		}

		if ($file instanceof Config_Interface)
		{
			try
			{
				$config = $file->load($overwrite);
			}
			catch (\ConfigException $e)
			{
				$config = array();
			}
			$group = $group === true ? $file->group() : $group;
		}

		if ($group === null)
		{
			static::$items = $reload ? $config : ($overwrite ? array_merge(static::$items, $config) : \Arr::merge(static::$items, $config));
		}
		else
		{
			$group = ($group === true) ? $file : $group;
			if ( ! isset(static::$items[$group]) or $reload)
			{
				static::$items[$group] = array();
			}
			static::$items[$group] = $overwrite ? array_merge(static::$items[$group],$config) : \Arr::merge(static::$items[$group],$config);
		}

		return $config;
	}

	/**
	 * Save a config array to disc.
	 *
	 * @param   string          $file      desired file name
	 * @param   string|array    $config    master config array key or config array
	 * @return  bool                       false when config is empty or invalid else \File::update result
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
		$content = <<<CONF
<?php

CONF;
		$content .= 'return '.str_replace(array('  ', 'array ('), array("\t", 'array('), var_export($config, true)).";\n";

		if ( ! $path = \Finder::search('config', $file, '.php'))
		{
			if ($pos = strripos($file, '::'))
			{
				// get the namespace path
				if ($path = \Autoloader::namespace_path('\\'.ucfirst(substr($file, 0, $pos))))
				{
					// strip the namespace from the filename
					$file = substr($file, $pos+2);

					// strip the classes directory as we need the module root
					// and construct the filename
					$path = substr($path,0, -8).'config'.DS.$file.'.php';

				}
				else
				{
					// invalid namespace requested
					return false;
				}
			}

		}

		// make sure we have a fallback
		$path or $path = APPPATH.'config'.DS.$file.'.php';

		$path = pathinfo($path);

		return \File::update($path['dirname'], $path['basename'], $content);
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
		if (isset(static::$items[$item]))
		{
			return static::$items[$item];
		}
		return \Fuel::value(\Arr::get(static::$items, $item, $default));
	}

	/**
	 * Sets a (dot notated) config item
	 *
	 * @param    string    a (dot notated) config key
	 * @param    mixed     the config value
	 * @return   void      the \Arr::set result
	 */
	public static function set($item, $value)
	{
		return \Arr::set(static::$items, $item, \Fuel::value($value));
	}

	/**
	 * Deletes a (dot notated) config item
	 *
	 * @param    string       a (dot notated) config key
	 * @return   array|bool   the \Arr::delete result, success boolean or array of success booleans
	 */
	public static function delete($item)
	{
		return \Arr::delete(static::$items, $item);
	}
}
