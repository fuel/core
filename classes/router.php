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

	/**
	 * Add one or multiple routes
	 *
	 * @param  string
	 * @param  string|array|Route  either the translation for $path, an array for verb routing or an instance of Route
	 * @param  bool                whether to prepend the route(s) to the routes array
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
		if (is_array($options) && count(array_filter($options, "is_array"))==count($options)) {
			$route_destination=array();
			foreach ($options as $route_options) {
				$route_destination[] = new \Route($path, $route_options);
			}
		} else {
			$route_destination = new \Route($path, $options);
		}

		if ($prepend)
		{
			\Arr::prepend(static::$routes, $name, $route_destination);
			return;
		}

		static::$routes[$name] = $route_destination;
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
	 * @param   string  $name  the name of the route
	 * @param   array   $named_params  the array of named parameters
	 * @return  string  the full url for the named route
	 */
	public static function get($name, $named_params = array())
	{
		if (array_key_exists($name, static::$routes))
		{
			return \Uri::create(static::$routes[$name]->path, $named_params);
		}
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
				$route_junction = is_array($route) ? $route: array($route);
				foreach ($route_junction as $route) {
					if ($match = $route->parse($request))
					{
						break 2;
					}
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
		$namespace = '';
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

	protected static function parse_segments($segments, $namespace = '', $module = false)
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


