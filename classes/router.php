<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel\Core;

class Router {

	public static $routes = array();

	/**
	 * Add one or multiple routes
	 *
	 * @param  string
	 * @param  string|array|Route  either the translation for $path, an array for verb routing or an instance of Route
	 */
	public static function add($path, $options = null)
	{
		if (is_array($path))
		{
			foreach ($path as $p => $t)
			{
				static::add($p, $t);
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

		static::$routes[$name] = new \Route($path, $options);
	}

	/**
	 * Does reverse routing for a named route.  This will return the FULL url
	 * (including the base url and index.php).
	 *
	 * First attempts to find the route by name. If that fails, it tries to
	 * match up route destinations to the given destination, dealing with route
	 * regex as it goes. If that fails, gracefully passes the given destination
	 * to Uri::create.
	 *
	 * Usage:
	 *
	 * <a href="<?php echo Router::get('foo'); ?>">Foo</a>
	 *
	 * @param   string  $destination the name of the route / route destination
	 * @param   array   $named_params  the array of named parameters
	 * @return  string  the full url for the named route
	 */
	public static function get($destination, $named_params = array())
	{
		// First see if we can find a named route with the given name
		if (array_key_exists($destination, static::$routes))
		{
			return \Uri::create(static::$routes[$destination]->path, $named_params);
		}
		// If that fails, try and find a reverse route
		foreach (static::$routes as $route)
		{
			if ($match = $route->match_reverse($destination))
			{
				return \Uri::create($match, $named_params);
			}
		}
		// If that fails, assume it's a forward route
		return \Uri::create($destination, $named_params);
	}

	/**
	 * Processes the given request using the defined routes
	 *
	 * @param	Request		the given Request object
	 * @param	bool		whether to use the defined routes or not
	 * @return	mixed		the match array or false
	 */
	public static function process(\Request $request, $route = true)
	{
		$match = false;

		if ($route)
		{
			foreach (static::$routes as $route)
			{
				if ($match = $route->parse($request))
				{
					break;
				}
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
		$namespace = '\\';
		$segments = $match->segments;
		$module = false;

		// First port of call: request for a module?
		if (\Fuel::module_exists($segments[0]))
		{
			// make the module known to the autoloader
			\Fuel::add_module($segments[0]);
			$match->module = array_shift($segments);
			$namespace .= ucfirst($match->module).'\\';
			$module = $match->module;
		}

		if ($info = static::parse_segments($segments, $namespace, $module))
		{
			$match->controller = $info['controller'];
			$match->action = $info['action'];
			$match->method_params = $info['method_params'];
			return $match;
		}
		else
		{
			return null;
		}
	}

	protected static function parse_segments($segments, $namespace = '\\', $module = false)
	{
		$temp_segments = $segments;

		foreach (array_reverse($segments, true) as $key => $segment)
		{
			$class = $namespace.'Controller_'.\Inflector::words_to_upper(implode('_', $temp_segments));
			array_pop($temp_segments);
			if (class_exists($class))
			{
				return array(
					'controller'    => $class,
					'action'        => isset($segments[$key + 1]) ? $segments[$key + 1] : null,
					'method_params' => array_slice($segments, $key + 2),
				);
			}
		}

		// Fall back for default module controllers
		if ($module)
		{
			$class = $namespace.'Controller_'.$module;
			if (class_exists($class))
			{
				return array(
					'controller'    => $class,
					'action'        => isset($segments[0]) ? $segments[0] : null,
					'method_params' => array_slice($segments, 1),
				);
			}
		}
		return false;
	}
}


