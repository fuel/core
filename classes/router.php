<?php
/**
 * Part of the Fuel framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel\Core;

class Router
{

	public static $routes = array();
	public static $modules_routing_definitions = array();

	/**
	 * Add one or multiple routes
	 *
	 * @param                      string
	 * @param  string|array|Route  either              the translation for $path, an array for verb routing or an instance of Route
	 * @param                      bool                whether to prepend the route(s) to the routes array
	 */
	public static function add($path, $options = null, $prepend = false)
	{
		if (is_array($path))
		{
			// Reverse to keep correct order in prepending
			$prepend and $path = array_reverse($path, true);
			foreach ($path as $p => $t)
			{
				static::add($p, $t, $prepend);
			}
			return;
		}
		elseif ($options instanceof Route)
		{
			static::$routes[$path] = $options;
			return;
		}

		$name = $path;
		if (is_array($options) and array_key_exists('name', $options))
		{
			$name = $options['name'];
			unset($options['name']);
			if (count($options) == 1 and ! is_array($options[0]))
			{
				$options = $options[0];
			}
		}
		// If there are only arrays in options then this contain multiple routes
		if (is_array($options) && count(array_filter($options, "is_array")) == count($options))
		{
			$route_destination = array();
			foreach ($options as $route_options)
			{
				$route_destination[] = new \Route($path, $route_options);
			}
		}
		else
		{
			$route_destination = new \Route($path, $options);
		}

		if ($prepend)
		{
			\Arr::prepend(static::$routes, $name, $route_destination);
			return;
		}

		static::$routes[$name] = $route_destination;
		return $route_destination;
	}


	public static function load_definitions_from_modules()
	{
		$finder = \Finder::forge(\Config::get('module_paths', array()));
		foreach ($finder->list_files('*/config', 'routes.*') as $routes_file)
		{
			// load and add the module routes
			$routes_definitions = \Fuel::load($routes_file);
			$module_name        = basename(dirname(dirname($routes_file)));
			$prepped_routes     = array();
			foreach ($routes_definitions as $path => $options)
			{
				if ($path === '_root_' || $path === '_404_') continue;
				$prepped_routes[$path] = $options;
			}

			static::$modules_routing_definitions[$module_name] = $prepped_routes;
		}
	}

	public static function get_modules_definitions()
	{
		return static::$modules_routing_definitions;
	}

	/**
	 * Does reverse routing for a named route.  This will return the FULL url
	 * (including the base url and index.php).
	 *
	 * WARNING: This is VERY limited at this point.  Does not work if there is
	 * any regex in the route.
	 *
	 * Usage:
	 *
	 * <a href="<?php echo Router::get('foo'); ?>">Foo</a>
	 *
	 * @param   string  $name          the name of the route
	 * @param   array   $named_params  the array of named parameters
	 * @param   boolean $secure        whether the url should be forced as secure
	 * @return  string  the full url for the named route
	 */
	public static function get($name = null, $named_params = array(), $secure = null)
	{
		$path = false;
		is_null($named_params) and $named_params = array();
		if (is_null($name)) return \Uri::create('');
		if ($name == '/') return \Uri::create('/');
		if (array_key_exists($name, static::$routes))
		{
			$route = static::$routes[$name];
		}
		else
		{
			$route = static::find_in_modules_by_name($name);
		}
		if ($route)
		{
			$path = $route->path;
			is_null($secure) and $secure = $route->is_secure;
		}
		return \Uri::create($path, $named_params, array(), $secure);
	}


	public static function find_in_modules_by_name($name)
	{
		$module_routing_definitions = static::get_modules_definitions();
		foreach ($module_routing_definitions as $module => $routing_definitions)
		{
			foreach ($routing_definitions as $path => $options)
			{
				if ($name == $path) return $path;
				if (isset($options['name']) and $options['name'] == $name)
				{
					$route         = static::add($path, $options);
					$route->module = $module;
					return $route;
				}
			}
		}
		return false;
	}

	public static function match(\Request $request, \Route $route)
	{
		$route_junction = is_array($route) ? $route : array($route);
		foreach ($route_junction as $route)
		{
			if ($match = $route->parse($request))
			{
				return $match;
			}
		}
		return false;
	}

	public static function match_in_modules(\Request $request)
	{
		foreach (static::get_modules_definitions() as $module => $module_routing_definitions)
		{
			foreach ($module_routing_definitions as $path => $options)
			{
				$route = static::add($path, $options);
				$match = static::match($request, $route);
				if ($match)
				{
					$route->module = $module;
					return $route;
				}
			}
		}
		return false;
	}

	/**
	 * Processes the given request using the defined routes
	 *
	 * @param	Request		the given Request object
	 * @param	bool           whether to use the defined routes or not
	 * @return	mixed		the match array or false
	 */
	public static function process(\Request $request, $route = true)
	{
		$match = false;

		if ($route)
		{
			// Match against the loaded routes
			foreach (static::$routes as $route)
			{
				$match = static::match($request, $route);
				if ($match) break;
			}
			if (! $match)
			{
				//try matching the $request
				$match = static::match_in_modules($request);
			}
		}

		if ( ! $match)
		{
			// Since we didn't find a match, we will create a new route.
			$match = new Route(preg_quote($request->uri->get(), '#'), $request->uri->get());
			$match->parse($request);
		}

		if ($match->callable !== null)
		{
			return $match;
		}

		return static::parse_match($match);
	}

	/**
	 * Find the controller that matches the route requested
	 *
	 * @param	Route  $match  the given Route object
	 * @return	mixed  the match array or false
	 */
	protected static function parse_match($match)
	{
		$namespace = '';
		$segments  = $match->segments;
		$module    = $match->module;

		// First port of call: request for a module?
		if ($module)
		{
			$segments[0] == $module and array_shift($segments);
			// make the module known to the autoloader
			\Fuel::add_module($module);
			$namespace = ucfirst($module).'\\';
		}

		if ($info = static::parse_segments($segments, $namespace, $module))
		{
			$match->controller    = $info['controller'];
			$match->action        = $info['action'];
			$match->method_params = $info['method_params'];
			return $match;
		}
		else
		{
			return null;
		}
	}

	protected static function parse_segments($segments, $namespace = '', $module = false)
	{
		$temp_segments = $segments;

		foreach (array_reverse($segments, true) as $key => $segment)
		{
			$class = $namespace.'Controller_'.\Inflector::words_to_upper(implode('_', $temp_segments));
			array_pop($temp_segments);
			if (class_exists($class))
			{
				return array('controller'    => $class,
							 'action'        => isset($segments[$key + 1]) ? $segments[$key + 1] : null,
							 'method_params' => array_slice($segments, $key + 2),
				);
			}
		}

		// Fall back for default module controllers
		if ($module)
		{
			$class = $namespace.'Controller_'.ucfirst($module);
			if (class_exists($class))
			{
				return array('controller'    => $class,
							 'action'        => isset($segments[0]) ? $segments[0] : null,
							 'method_params' => array_slice($segments, 1),
				);
			}
		}
		return false;
	}

	public static function get_name(\Route $route)
	{
		return array_search($route, static::$routes);
	}

}