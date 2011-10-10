<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class ConfigException extends \FuelException { }

class Config {

	public static $loaded_files = array();

	public static $items = array();

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
			$type = isset($info['extension']) ? $info['extension'] : 'php';
			$file = $info['filename'];
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
/**
 * Part of the Fuel framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2011 Fuel Development Team
 * @link		http://fuelphp.com
 */


CONF;
		$content .= 'return '.str_replace('  ', "\t", var_export($config, true)).';';

		if ( ! $path = \Fuel::find_file('config', $file, '.php'))
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

		$content .= <<<CONF



CONF;

		// make sure we have a fallback
		$path or $path = APPPATH.'config'.DS.$file.'.php';

		$path = pathinfo($path);

		return \File::update($path['dirname'], $path['basename'], $content);
	}

	public static function get($item, $default = null)
	{
		if (isset(static::$items[$item]))
		{
			return static::$items[$item];
		}
		return \Fuel::value(\Arr::get(static::$items, $item, $default));
	}

	public static function set($item, $value)
	{
		return \Arr::set(static::$items, $item, \Fuel::value($value));
	}

	public static function delete($item)
	{
		return \Arr::delete(static::$items, $item);
	}
}
