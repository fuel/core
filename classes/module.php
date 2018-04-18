<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * This exception is thrown when a module cannot be found.
 *
 * @package     Core
 */
class ModuleNotFoundException extends \FuelException { }

/**
 * Handles all the loading, unloading and management of modules.
 *
 * @package     Core
 */
class Module
{
	/**
	 * @var  array  $modules  Holds all the loaded module information.
	 */
	protected static $modules = array();

	/**
	 * Loads the given module.  If a path is not given, then 'module_paths' is used.
	 * It also accepts an array of modules as the first parameter.
	 *
	 * @param   string|array  $module  The module name or array of modules.
	 * @param   string|null   $path    The path to the module
	 * @return  bool  True on success, False on fail or already loaded.
	 * @throws  \ModuleNotFoundException
	 */
	public static function load($module, $path = null)
	{
		if (is_array($module))
		{
			$result = true;
			foreach ($module as $mod => $path)
			{
				if (is_numeric($mod))
				{
					$mod = $path;
					$path = null;
				}
				$result = $result and static::load($mod, $path);
			}
			return $result;
		}

		if (static::loaded($module))
		{
			return false;
		}

		// if no path is given, try to locate the module
		if ($path === null)
		{
			$paths = \Config::get('module_paths', array());

			if ( ! empty($paths))
			{
				foreach ($paths as $modpath)
				{
					if (is_dir($path = $modpath.strtolower($module).DS))
					{
						break;
					}
				}
			}

		}
		else
		{
			// make sure it's terminated properly
			$path = rtrim($path, DS).DS;
		}

		// make sure the path exists
		if ( ! is_dir($path))
		{
			throw new \ModuleNotFoundException("Module '$module' could not be found at '".\Fuel::clean_path($path)."'");
		}

		// determine the module namespace
		$ns = '\\'.ucfirst($module);

		// add the namespace to the autoloader
		\Autoloader::add_namespaces(array(
			$ns  => $path.'classes'.DS,
		), true);

		// load module routes if required
		if (\Config::get('routing.module_routes', false) and $routes = Config::load($module.'::routes', 'routes'))
		{
			\Router::add($routes);
		}

		// mark the module as loaded
		static::$modules[$module] = $path;

		return true;
	}

	/**
	 * Unloads a module from the stack.
	 *
	 * @param   string  $module  The module name
	 * @return  void
	 */
	public static function unload($module)
	{
		// we can only unload a loaded module
		if (isset(static::$modules[$module]))
		{
			$path = static::$modules[$module];

			if (is_file($path .= 'config/routes.php'))
			{
				// load and add the module routes
				$module_routes = \Fuel::load($path);

				$route_names = array();
				foreach($module_routes as $name => $_route)
				{
					if ($name === '_root_')
					{
						$name = $module;
					}
					elseif (strpos($name, $module.'/') !== 0 and $name != $module and $name !== '_404_')
					{
						$name = $module.'/'.$name;
					}

					$route_names[] = $name;
				};

				// delete the defined module routes
				\Router::delete($route_names);
			}
		}

		// delete this module
		unset(static::$modules[$module]);
	}

	/**
	 * Checks if the given module is loaded, if no module is given then
	 * all loaded modules are returned.
	 *
	 * @param   string|null  $module  The module name or null
	 * @return  bool|array  Whether the module is loaded, or all modules
	 */
	public static function loaded($module = null)
	{
		if ($module === null)
		{
			return static::$modules;
		}

		return array_key_exists($module, static::$modules);
	}

	/**
	 * Checks if the given module exists.
	 *
	 * @param   string  $module  The module name
	 * @return  bool|string  Path to the module found, or false if not found
	 */
	public static function exists($module)
	{
		if (array_key_exists($module, static::$modules))
		{
			return static::$modules[$module];
		}
		else
		{
			$paths = \Config::get('module_paths', array());
			$module = strtolower($module);

			foreach ($paths as $path)
			{
				if (is_dir($path.$module))
				{
					return $path.$module.DS;
				}
			}
		}

		return false;
	}
}
