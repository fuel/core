<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2015 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Router
{
	/**
	 *
	 */
	public static $routes = array();

	/**
	 * Defines the controller class prefix. This allows you to namespace controllers
	 */
	protected static $prefix = '';

	/**
	 * Fetch the controller prefix to be used, or set a default if not defined
	 */
	public static function _init()
	{
		static::$prefix = ltrim(\Config::get('controller_prefix', 'Controller_'), '\\');
	}

	/**
	 * Add one or multiple routes
	 *
	 * @param  string
	 * @param  string|array|Route  either the translation for $path, an array for verb routing or an instance of Route
	 * @param  bool                whether to prepend the route(s) to the routes array
	 * @param  bool                whether to check case sensitive
	 */
	public static function add($path, $options = null, $prepend = false, $case_sensitive = null)
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

		if ($prepend)
		{
			\Arr::prepend(static::$routes, $name, new \Route($path, $options, $case_sensitive, null, $name));
			return;
		}

		static::$routes[$name] = new \Route($path, $options, $case_sensitive, null, $name);
	}

	/**
	 * Does reverse routing for a named route.  This will return the FULL url
	 * (including the base url and index.php).
	 *
	 * WARNING: Reverse routing with routes that contains a regex is still
	 * experimental. The simple ones work, but complex ones might fail!
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
		// check if we have this named route
		if (array_key_exists($name, static::$routes))
		{
			// fetch the url this route defines
			$url = static::$routes[$name]->path;

			// get named parameters regex's out of the way first
			foreach($named_params as $name => $value)
			{
				if (is_string($name) and ($pos = strpos($url, '(:'.$name.')')) !== false)
				{
					$url = substr_replace($url, $value, $pos, strlen($name)+3);
				}
			}

			// deal with regex's groups
			if (preg_match_all('#\((?:\?P<(\w+?)>)?.*?\)#', $url, $matches) !== false)
			{
				if (count($matches) == 2)
				{
					$indexed_group_count = 0;
					foreach($matches[0] as $index => $target)
					{
						$replace = '';
						if (array_key_exists($key = $matches[1][$index], $named_params) ||
						    array_key_exists($key = '$'.($index + 1), $named_params) ||
						    array_key_exists($key = $indexed_group_count++, $named_params))
						{
							$replace = $named_params[$key];
						}

						if (($pos = strpos($url, $target)) !== false)
						{
							$url = substr_replace($url, $replace, $pos, strlen($target));
						}
					}
				}
			}

			// return the created URI, replace any named parameters not in a regex
			return \Uri::create($url, $named_params);
		}
	}

	/**
	 * Delete one or multiple routes
	 *
	 * @param  string|array  route path, or array of route paths
	 * @param  bool          whether to check case sensitive
	 */
	public static function delete($path, $case_sensitive = null)
	{
		// if multiple paths are passed, recurse
		if (is_array($path))
		{
			foreach($path as $p)
			{
				static::delete($p, $case_sensitive);
			}
		}
		else
		{
			$case_sensitive ?: \Config::get('routing.case_sensitive', true);

			// support the usual route path placeholders
			$path = str_replace(array(
				':any',
				':alnum',
				':num',
				':alpha',
				':segment',
			), array(
				'.+',
				'[[:alnum:]]+',
				'[[:digit:]]+',
				'[[:alpha:]]+',
				'[^/]*',
			), $path);

			foreach (static::$routes as $name => $route)
			{
				if ($case_sensitive)
				{
					if (preg_match('#^'.$path.'$#uD', $name))
					{
						unset(static::$routes[$name]);
					}
				}
				else
				{
					if (preg_match('#^'.$path.'$#uiD', $name))
					{
						unset(static::$routes[$name]);
					}
				}
			}
		}
	}

	/**
	 * Processes the given request using the defined routes
	 *
	 * @param   Request     the given Request object
	 * @param   bool        whether to use the defined routes or not
	 * @return  mixed       the match array or false
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
			$match = new \Route(preg_quote($request->uri->get(), '#'), $request->uri->get());
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
	 * @param   Route  $match  the given Route object
	 * @return  mixed  the match array or false
	 */
	protected static function parse_match($match)
	{
		$namespace = '';
		$segments = $match->segments;
		$module = false;

		// First port of call: request for a module?
		if (\Module::exists($segments[0]))
		{
			// make the module known to the autoloader
			\Module::load($segments[0]);
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
		$prefix = static::get_prefix();

		foreach (array_reverse($segments, true) as $key => $segment)
		{
			// determine which classes to check. First, all underscores, or all namespaced
			$classes = array(
				$namespace.$prefix.\Inflector::words_to_upper(implode(substr($prefix, -1, 1), $temp_segments), substr($prefix, -1, 1)),
			);

			// if we're namespacing, check a hybrid version too
			$classes[] = $namespace.$prefix.\Inflector::words_to_upper(implode('_', $temp_segments));

			array_pop($temp_segments);

			foreach ($classes as $class)
			{
				if (static::check_class($class))
				{
					return array(
						'controller'    => $class,
						'action'        => isset($segments[$key + 1]) ? $segments[$key + 1] : null,
						'method_params' => array_slice($segments, $key + 2),
					);
				}
			}
		}

		// Fall back for default module controllers
		if ($module)
		{
			$class = $namespace.$prefix.ucfirst($module);
			if (static::check_class($class))
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

	/**
	 * Checks whether class exists.
	 *
	 * @param string $class The class name to check.
	 * @return bool True if $class exists, false otherwise.
	 */
	protected static function check_class($class)
	{
		return class_exists($class);
	}

	/**
	 * Get prefix.
	 *
	 * @return string Prefix as defined in config controller_prefix.
	 */
	protected static function get_prefix()
	{
		return static::$prefix;
	}
}
