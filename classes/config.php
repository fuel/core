<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Config {

	public static $loaded_files = array();

	public static $items = array();

	public static function load($file, $group = null, $reload = false)
	{
		if ( ! is_array($file) && array_key_exists($file, static::$loaded_files) and ! $reload)
		{
			return false;
		}

		$config = array();
		if (is_array($file))
		{
			$config = $file;
		}
		elseif ($paths = \Fuel::find_file('config', $file, '.php', true))
		{
			// Reverse the file list so that we load the core configs first and
			// the app can override anything.
			$paths = array_reverse($paths);
			foreach ($paths as $path)
			{
				$config = array_merge($config, \Fuel::load($path));
			}
		}

		if ($group === null)
		{
			static::$items = $reload ? $config : array_merge(static::$items, $config);
		}
		else
		{
			$group = ($group === true) ? $file : $group;
			if ( ! isset(static::$items[$group]) or $reload)
			{
				static::$items[$group] = array();
			}
			static::$items[$group] = array_merge(static::$items[$group],$config);
		}

		if ( ! is_array($file))
		{
			static::$loaded_files[$file] = true;
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
 * Fuel is a fast, lightweight, community driven PHP5 framework.
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

		return File::update($path['dirname'], $path['basename'], $content);
	}

	public static function get($item, $default = null)
	{
		return \Fuel::value(\Arr::get(static::$items, $item, $default));
	}

	public static function set($item, $value)
	{
		return \Arr::set(static::$items, $item, \Fuel::value($value));
	}
}
