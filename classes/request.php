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


class Request404Exception extends \Fuel_Exception {

	/**
	 * When this type of exception isn't caught this method is called by
	 * Error::exception_handler() to deal with the problem.
	 */
	public function handle()
	{
		$response = new \Response(\View::forge('404'), 404);
		\Event::shutdown();
		$response->send(true);
		return;
	}
}


/**
 * The Request class is used to create and manage new and existing requests.  There
 * is a main request which comes in from the browser or command line, then new
 * requests can be created for HMVC requests.
 *
 * Example Usage:
 *
 *     $request = Request::forge('foo/bar')->execute();
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
	 * This method is deprecated...use forge() instead.
	 *
	 * @deprecated until 1.2
	 */
	public static function factory($uri = null, $route = true)
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($uri, $route);
	}

	/**
	 * Generates a new request.  The request is then set to be the active
	 * request.  If this is the first request, then save that as the main
	 * request for the app.
	 *
	 * Usage:
	 *
	 *     Request::forge('hello/world');
	 *
	 * @param   string   The URI of the request
	 * @param   bool     Whether to use the routes to determine the Controller and Action
	 * @return  Request  The new request object
	 */
	public static function forge($uri = null, $route = true)
	{
		logger(Fuel::L_INFO, 'Creating a new Request with URI = "'.$uri.'"', __METHOD__);

		$request = new static($uri, $route);
		if (static::$active)
		{
			$request->parent = static::$active;
			static::$active->children[] = $request;
		}

		return $request;
	}

	/**
	 * Returns the main request instance (the one from the browser or CLI).
	 * This is the first executed Request, not necessarily the root parent of the current request.
	 *
	 * Usage:
	 *
	 *     Request::main();
	 *
	 * @return  Request
	 */
	public static function main()
	{
		return static::$main;
	}

	/**
	 * Returns the active request currently being used.
	 *
	 * Usage:
	 *
	 *     Request::active();
	 *
	 * @param   Request|null|false  overwrite current request before returning, false prevents overwrite
	 * @return  Request
	 */
	public static function active($request = false)
	{
		if ($request !== false)
		{
			static::$active = $request;
		}

		return static::$active;
	}

	/**
	 * Returns the current request is an HMVC request
	 *
	 * Usage:
	 *
	 *     if (Request::is_hmvc())
	 *     {
	 *         // Do something special...
	 *         return;
	 *     }
	 *
	 * @return  bool
	 */
	public static function is_hmvc()
	{
		return static::active() !== static::main();
	}

	/**
	 * Shows a 404.  Checks to see if a 404_override route is set, if not show
	 * a default 404.
	 *
	 * @deprecated  Remove in v1.2
	 * @throws  Request404Exception
	 */
	public static function show_404()
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Request404Exception instead.', __METHOD__);
		throw new \Request404Exception();
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
		// Let's make the previous Request active since we are done executing this one.
		static::$active = static::$active->parent();
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
	 * Request that created this one
	 *
	 * @var  Request
	 */
	protected $parent = null;

	/**
	 * Requests created by this request
	 *
	 * @var  array
	 */
	protected $children = array();

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
		if (count($this->uri->segments) and $module_path = \Fuel::module_exists($this->uri->segments[0]))
		{
			// check if the module has routes
			if (is_file($module_path .= 'config/routes.php'))
			{
				$module = $this->uri->segments[0];

				// load and add the module routes
				$module_routes = \Config::load(\Fuel::load($module_path), $module . '_routes');
				array_walk($module_routes, function ($route, $name) use ($module) {
					if ($name === '_root_')
					{
						$name = $module;
					}
					elseif (strpos($name, $module.'/') !== 0 and $name != $module and $name !== '_404_')
					{
						$name = $module.'/'.$name;
					}
					\Config::set('routes.'.$name, $route);
				});

				// update the loaded list of routes
				\Router::add(\Config::get('routes'));
			}
		}

		$this->route = \Router::process($this, $route);

		if ( ! $this->route)
		{
			return;
		}

		$this->module = $this->route->module;
		$this->controller = $this->route->controller;
		$this->action = $this->route->action;
		$this->method_params = $this->route->method_params;
		$this->named_params = $this->route->named_params;

		if ($this->route->module !== null)
		{
			$this->add_path(\Fuel::module_exists($this->module));
		}
	}

	/**
	 * This executes the request and sets the output to be used later.
	 *
	 * Usage:
	 *
	 *     $request = Request::forge('hello/world')->execute();
	 *
	 * @param  array|null  $method_params  An array of parameters to pass to the method being executed
	 * @return  Request  This request object
	 */
	public function execute($method_params = null)
	{
		if (\Fuel::$profiling)
		{
			\Profiler::mark(__METHOD__.' Start');
		}

		logger(Fuel::L_INFO, 'Called', __METHOD__);

		// Make the current request active
		static::$active = $this;

		// First request called is also the main request
		if ( ! static::$main)
		{
			logger(Fuel::L_INFO, 'Setting main Request', __METHOD__);
			static::$main = $this;
		}

		if ( ! $this->route)
		{
			static::reset_request();
			throw new \Request404Exception();
		}

		if ($this->route->callable !== null)
		{
			logger(Fuel::L_INFO, 'Calling closure', __METHOD__);
			try
			{
				$response = call_user_func_array($this->route->callable, array($this));
			}
			catch (Request404Exception $e)
			{
				static::reset_request();
				throw $e;
			}
		}
		else
		{
			$method_prefix = 'action_';
			$class = $this->controller;

			// If the class doesn't exist then 404
			if ( ! class_exists($class))
			{
				static::reset_request();
				throw new \Request404Exception();
			}

			logger(Fuel::L_INFO, 'Loading controller '.$class, __METHOD__);
			$this->controller_instance = $controller = new $class($this, new \Response);

			$this->action = $this->action ?: (property_exists($controller, 'default_action') ? $controller->default_action : 'index');
			$method = $method_prefix.$this->action;

			// Allow override of method params from execute
			if (is_array($method_params))
			{
				$this->method_params = array_merge($this->method_params, $method_params);
			}

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
				try
				{
					$response = call_user_func_array(array($controller, $method), $this->method_params);
				}
				catch (Request404Exception $e)
				{
					static::reset_request();
					throw $e;
				}

				// Call the after method if it exists
				if (method_exists($controller, 'after'))
				{
					logger(Fuel::L_INFO, 'Calling '.$class.'::after', __METHOD__);
					$response_after = $controller->after($response);

					// @TODO let the after method set the response directly
					if (is_null($response_after))
					{
						logger(\Fuel::L_WARNING, 'The Controller::after() method should accept and return the Controller\'s response, empty return for the after() method is deprecated.', __METHOD__);
					}
					else
					{
						$response = $response_after;
					}
				}
			}
			else
			{
				static::reset_request();
				throw new \Request404Exception();
			}
		}

		// Get the controller's output
		if (is_null($response))
		{
			// @TODO remove this in a future version as we will get rid of it.
			logger(\Fuel::L_WARNING, 'The Controller should return a string or a Response object, support for the $controller->response object is deprecated.', __METHOD__);
			$this->response = $controller->response;
		}
		elseif ($response instanceof \Response)
		{
			$this->response = $response;
		}
		else
		{
			$this->response = \Response::forge($response, 200);
		}

		static::reset_request();

		if (\Fuel::$profiling)
		{
			\Profiler::mark(__METHOD__.' End');
		}

		return $this;
	}

	/**
	 * Gets this Request's Response object;
	 *
	 * Usage:
	 *
	 *     $response = Request::forge('foo/bar')->execute()->response();
	 *
	 * @return  Response  This Request's Response object
	 */
	public function response()
	{
		return $this->response;
	}

	/**
	 * Returns the Request that created this one
	 *
	 * @return  Request|null
	 */
	public function parent()
	{
		return $this->parent;
	}

	/**
	 * Returns an array of Requests created by this one
	 *
	 * @return  array
	 */
	public function children()
	{
		return $this->children;
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
	 * Gets a specific named parameter
	 *
	 * @param   string  $param    Name of the parameter
	 * @param   mixed   $default  Default value
	 * @return  mixed
	 */
	public function param($param, $default = null)
	{
		if ( ! isset($this->named_params[$param]))
		{
			return \Fuel::value($default);
		}

		return $this->named_params[$param];
	}

	/**
	 * Gets all of the named parameters
	 *
	 * @return  array
	 */
	public function params()
	{
		return $this->named_params;
	}

	/**
	 * PHP magic function returns the Output of the request.
	 *
	 * Usage:
	 *
	 *     $request = Request::forge('hello/world')->execute();
	 *     echo $request;
	 *
	 * @return  string  the response
	 */
	public function __toString()
	{
		return (string) $this->response;
	}
}