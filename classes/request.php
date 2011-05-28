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


/**
 * The Request class is used to create and manage new and existing requests.  There
 * is a main request which comes in from the browser or command line, then new
 * requests can be created for HMVC requests.
 *
 * Example Usage:
 *
 *     $request = Request::factory('foo/bar')->execute();
 *     echo $request->response();
 *
 * @package     Fuel
 * @subpackage  Core
 */
class Request {

	/**
	 * Holds the main request instance
	 *
	 * @var  Request
	 */
	protected static $main = false;

	/**
	 * Holds the global active request instance
	 *
	 * @var  Request
	 */
	protected static $active = false;

	/**
	 * Holds the previous request
	 *
	 * @var  Request
	 */
	protected static $previous = false;

	/**
	 * Generates a new request.  The request is then set to be the active
	 * request.  If this is the first request, then save that as the main
	 * request for the app.
	 *
	 * Usage:
	 *
	 *     Request::factory('hello/world');
	 *
	 * @param   string   The URI of the request
	 * @param   bool     Whether to use the routes to determine the Controller and Action
	 * @return  Request  The new request object
	 */
	public static function factory($uri = null, $route = true)
	{
		logger(Fuel::L_INFO, 'Creating a new Request with URI = "'.$uri.'"', __METHOD__);

		if (static::$active)
		{
			static::$previous = static::$active;
		}

		static::$active = new static($uri, $route);

		if ( ! static::$main)
		{
			logger(Fuel::L_INFO, 'Setting main Request', __METHOD__);
			static::$main = static::$active;
		}

		return static::$active;
	}

	/**
	 * Returns the main request instance (the one from the browser or CLI).
	 *
	 * Usage:
	 *
	 *     Request::main();
	 *
	 * @return  Request
	 */
	public static function main()
	{
		logger(Fuel::L_INFO, 'Called', __METHOD__);

		return static::$main;
	}

	/**
	 * Returns the active request currently being used.
	 *
	 * Usage:
	 *
	 *     Request::active();
	 *
	 * @return  Request
	 */
	public static function active()
	{
		class_exists('Log', false) and logger(Fuel::L_INFO, 'Called', __METHOD__);

		return static::$active;
	}

	/**
	 * Shows a 404.  Checks to see if a 404_override route is set, if not show
	 * a default 404.
	 *
	 * Usage:
	 *
	 *     Request::show_404();
	 *
	 * @param   bool         Whether to return the 404 output or just output and exit
	 * @return  void|string  Void if $return is false, the output if $return is true
	 */
	public static function show_404($return = false)
	{
		logger(Fuel::L_INFO, 'Called', __METHOD__);

		// This ensures that show_404 doesn't recurse indefinately
		static $call_count = 0;
		$call_count++;

		if ($call_count == 1)
		{
			// first call, route the 404 route
			$route_request = true;
		}
		elseif ($call_count == 2)
		{
			// second call, try the 404 route without routing
			$route_request = false;
		}
		else
		{
			// third call, there's something seriously wrong now
			throw new \Fuel_Exception('It appears your _404_ route is incorrect.  Multiple Recursion has happened.');
		}

		if (\Config::get('routes._404_') === null)
		{
			$response = new \Response(\View::factory('404'), 404);

			if ($return)
			{
				return $response;
			}

			$response->send(true);
			exit;
		}
		else
		{
			$request = \Request::factory(\Config::get('routes._404_'), $route_request)->execute();

			if ($return)
			{
				return $request->response;
			}

			$request->response->send(true);
			exit;
		}
	}

	/**
	 * Reset's the active request with the previous one.  This is needed after
	 * the active request is finished.
	 *
	 * Usage:
	 *
	 *    Request::reset_request();
	 *
	 * @return  void
	 */
	public static function reset_request()
	{
		// Let's make the previous Request active since we are don't executing this one.
		if (static::$previous)
		{
			static::$active = static::$previous;
		}
	}


	/**
	 * Holds the response object of the request.
	 *
	 * @var  Response
	 */
	public $response = null;

	/**
	 * The Request's URI object.
	 *
	 * @var  Uri
	 */
	public $uri = null;

	/**
	 * The request's route object
	 *
	 * @var  Route
	 */
	public $route = null;

	/**
	 * The current module
	 *
	 * @var  string
	 */
	public $module = '';

	/**
	 * The current controller directory
	 *
	 * @var  string
	 */
	public $directory = '';

	/**
	 * The request's controller
	 *
	 * @var  string
	 */
	public $controller = '';

	/**
	 * The request's controller action
	 *
	 * @var  string
	 */
	public $action = '';

	/**
	 * The request's method params
	 *
	 * @var  array
	 */
	public $method_params = array();

	/**
	 * The request's named params
	 *
	 * @var  array
	 */
	public $named_params = array();

	/**
	 * Controller instance once instantiated
	 *
	 * @var  Controller
	 */
	public $controller_instance;

	/**
	 * Search paths for the current active request
	 *
	 * @var  array
	 */
	public $paths = array();

	/**
	 * Creates the new Request object by getting a new URI object, then parsing
	 * the uri with the Route class.
	 *
	 * Usage:
	 *
	 *     $request = new Request('foo/bar');
	 *
	 * @param   string  the uri string
	 * @param   bool    whether or not to route the URI
	 * @return  void
	 */
	public function __construct($uri, $route = true)
	{
		$this->uri = new \Uri($uri);

		// check if a module was requested
		if (count($this->uri->segments) and $modpath = \Fuel::module_exists($this->uri->segments[0]))
		{
			// check if the module has custom routes
			if (file_exists($modpath .= 'config/routes.php'))
			{
				// load and add the routes
				\Config::load(\Fuel::load($modpath), 'routes');
				\Router::add(\Config::get('routes'));
			}
		}

		$this->route = \Router::process($this, $route);

		if ( ! $this->route)
		{
			return false;
		}

		if ($this->route->module !== null)
		{
			$this->module = $this->route->module;
			\Fuel::add_module($this->module);
			$this->add_path(\Fuel::module_exists($this->module));
		}

		$this->directory = $this->route->directory;
		$this->controller = $this->route->controller;
		$this->action = $this->route->action;
		$this->method_params = $this->route->method_params;
		$this->named_params = $this->route->named_params;
	}

	/**
	 * This executes the request and sets the output to be used later.
	 *
	 * Usage:
	 *
	 *     $request = Request::factory('hello/world')->execute();
	 *
	 * @return  Request  This request object
	 */
	public function execute()
	{
		logger(Fuel::L_INFO, 'Called', __METHOD__);

		if ( ! $this->route)
		{
			$this->response = static::show_404(true);
			static::reset_request();
			return $this;
		}

		$controller_prefix = '\\'.($this->module ? ucfirst($this->module).'\\' : '').'Controller_';
		$method_prefix = 'action_';

		$class = $controller_prefix.($this->directory ? ucfirst($this->directory).'_' : '').ucfirst($this->controller);
		$method = $this->action;

		// If the class doesn't exist then 404
		if ( ! class_exists($class))
		{
			$this->response = static::show_404(true);
			static::reset_request();
			return $this;
		}

		logger(Fuel::L_INFO, 'Loading controller '.$class, __METHOD__);
		$this->controller_instance = $controller = new $class($this, new \Response);

		$method = $method_prefix.($method ?: (property_exists($controller, 'default_action') ? $controller->default_action : 'index'));


		// Allow to do in controller routing if method router(action, params) exists
		if (method_exists($controller, 'router'))
		{
			$method = 'router';
			$this->method_params = array($this->action, $this->method_params);
		}

		if (is_callable(array($controller, $method)))
		{
			// Call the before method if it exists
			if (method_exists($controller, 'before'))
			{
				logger(Fuel::L_INFO, 'Calling '.$class.'::before', __METHOD__);
				$controller->before();
			}

			logger(Fuel::L_INFO, 'Calling '.$class.'::'.$method, __METHOD__);
			call_user_func_array(array($controller, $method), $this->method_params);

			// Call the after method if it exists
			if (method_exists($controller, 'after'))
			{
				logger(Fuel::L_INFO, 'Calling '.$class.'::after', __METHOD__);
				$controller->after();
			}

			// Get the controller's output
			$this->response =& $controller->response;
		}
		else
		{
			$this->response = static::show_404(true);
		}

		static::reset_request();
		return $this;
	}

	/**
	 * Gets this Request's Response object;
	 *
	 * Usage:
	 *
	 *     $response = Request::factory('foo/bar')->execute()->response();
	 *
	 * @return  Response  This Request's Response object
	 */
	public function response()
	{
		return $this->response;
	}

	/**
	 * Add to paths which are used by Fuel::find_file()
	 *
	 * @param   string  the new path
	 * @param   bool    whether to add to the front or the back of the array
	 * @return  void
	 */
	public function add_path($path, $prefix = false)
	{
		if ($prefix)
		{
			// prefix the path to the paths array
			array_unshift($this->paths, $path);
		}
		else
		{
			// add the new path
			$this->paths[] = $path;
		}
	}

	/**
	 * Returns the array of currently loaded search paths.
	 *
	 * @return  array  the array of paths
	 */
	public function get_paths()
	{
		return $this->paths;
	}

	/**
	 * PHP magic function returns the Output of the request.
	 *
	 * Usage:
	 *
	 *     $request = Request::factory('hello/world')->execute();
	 *     echo $request;
	 *
	 * @return  string  the response
	 */
	public function __toString()
	{
		return (string) $this->response;
	}
}

/* End of file request.php */
