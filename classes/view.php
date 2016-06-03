<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * View class
 *
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package   Fuel
 * @category  Core
 * @link      http://docs.fuelphp.com/classes/view.html
 */
class View
{
	/**
	 * @var  array  Global view data
	 */
	protected static $global_data = array();

	/**
	 * @var  array  Holds a list of specific filter rules for global variables
	 */
	protected static $global_filter = array();

	/**
	 * @var  array  Current active search paths
	 */
	protected $request_paths = array();

	/**
	 * @var  bool  Whether to auto-filter the view's data
	 */
	protected $auto_filter = true;

	/**
	 * @var  bool  Whether to filter closures
	 */
	protected $filter_closures = true;

	/**
	 * @var  array  Holds a list of specific filter rules for local variables
	 */
	protected $local_filter = array();

	/**
	 * @var  string  The view's filename
	 */
	protected $file_name = null;

	/**
	 * @var  array  The view's data
	 */
	protected $data = array();

	/**
	 * @var  string  The view file extension
	 */
	protected $extension = 'php';

	/**
	 * @var  Request  active request when the View was created
	 */
	protected $active_request = null;

	/**
	 * @var  string  active language at the time the object was created
	 */
	protected $active_language = null;

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [static::set_filename].
	 *
	 *     $view = View::forge($file);
	 *
	 * @param   string  $file         view filename
	 * @param   object  $data         array of values
	 * @param   bool    $auto_filter
	 * @return  View
	 */
	public static function forge($file = null, $data = null, $auto_filter = null)
	{
		return new static($file, $data, $auto_filter);
	}

	/**
	 * Sets the initial view filename and local data.
	 *
	 *     $view = new View($file);
	 *
	 * @param   string  $file    view filename
	 * @param   object  $data    array of values
	 * @param   bool    $filter
	 * @uses    View::set_filename
	 */
	public function __construct($file = null, $data = null, $filter = null)
	{
		if (is_object($data) === true)
		{
			$data = get_object_vars($data);
		}
		elseif ($data and ! is_array($data))
		{
			throw new \InvalidArgumentException('The data parameter only accepts objects and arrays.');
		}

		$this->auto_filter = is_null($filter) ? \Config::get('security.auto_filter_output', true) : $filter;

		$this->filter_closures = \Config::get('filter_closures', true);

		if ($file !== null)
		{
			$this->set_filename($file);
		}

		if ($data !== null)
		{
			// Add the values to the current data
			$this->data = $data;
		}

		// store the current request search paths to deal with out-of-context rendering
		if (class_exists('Request', false) and $active = \Request::active() and \Request::main() !== $active)
		{
			$this->request_paths = $active->get_paths();
		}
		isset($active) and $this->active_request = $active;

		// store the active language, so we can render the view in the correct language later
		$this->active_language = \Config::get('language', 'en');
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $view->foo;
	 *
	 * @param   string  $key  variable name
	 * @return  mixed
	 * @throws  \OutOfBoundsException
	 */
	public function & __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Magic method, calls [static::set] with the same parameters.
	 *
	 *     $view->foo = 'something';
	 *
	 * @param   string  $key    variable name
	 * @param   mixed   $value  value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($view->foo);
	 *
	 * [!!] `null` variables are not considered to be set by [isset](http://php.net/isset).
	 *
	 * @param   string  $key  variable name
	 * @return  boolean
	 */
	public function __isset($key)
	{
		return (isset($this->data[$key]) or isset(static::$global_data[$key]));
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($view->foo);
	 *
	 * @param   string  $key  variable name
	 * @return  void
	 */
	public function __unset($key)
	{
		unset($this->data[$key], static::$global_data[$key]);
	}

	/**
	 * Magic method, returns the output of [static::render].
	 *
	 * @return  string
	 * @uses    View::render
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			\Errorhandler::exception_handler($e);

			return '';
		}
	}

	/**
	 * Captures the output that is generated when a view is included.
	 * The view data will be extracted to make local variables.
	 *
	 *     $output = $this->process_file();
	 *
	 * @param   bool  $file_override  File override
	 * @return  string
	 */
	protected function process_file($file_override = false)
	{
		$clean_room = function($__file_name, array $__data)
		{
			extract($__data, EXTR_REFS);

			// Capture the view output
			ob_start();

			try
			{
				// Load the view within the current scope
				include $__file_name;
			}
			catch (\Exception $e)
			{
				// Delete the output buffer
				ob_end_clean();

				// Re-throw the exception
				throw $e;
			}

			// Get the captured output and close the buffer
			return ob_get_clean();
		};

		// import and process the view file
		$result = $clean_room($file_override ?: $this->file_name, $data = $this->get_data());

		// disable sanitization on objects that support it
		$this->unsanitize($data);

		// return the result
		return $result;
	}

	/**
	 * Retrieves all the data, both local and global.  It filters the data if
	 * necessary.
	 *
	 *     $data = $this->get_data();
	 *
	 * @param   string  $scope  local/glocal/all
	 * @return  array   view data
	 */
	protected function get_data($scope = 'all')
	{
		$filter_closures = $this->filter_closures;
		$clean_it = function ($data, $rules, $auto_filter) use ($filter_closures)
		{
			foreach ($data as $key => &$value)
			{
				$filter = array_key_exists($key, $rules) ? $rules[$key] : null;
				$filter = is_null($filter) ? $auto_filter : $filter;

				if ($filter)
				{
					if ($filter_closures and $value instanceOf \Closure)
					{
						$value = $value();
					}
					$value = \Security::clean($value, null, 'security.output_filter');
				}
			}

			return $data;
		};

		$data = array();

		if ( ! empty($this->data)  and ($scope === 'all' or $scope === 'local'))
		{
			$data += $clean_it($this->data, $this->local_filter, $this->auto_filter);
		}

		if ( ! empty(static::$global_data)  and ($scope === 'all' or $scope === 'global'))
		{
			$data += $clean_it(static::$global_data, static::$global_filter, $this->auto_filter);
		}

		return $data;
	}

	/**
	 * disable sanitation on any objects in the data that support it
	 *
	 * @param   mixed
	 * @return  mixed
	 */
	protected function unsanitize($var)
	{
		// deal with objects that can be sanitized
		if ($var instanceOf \Sanitization)
		{
			$var->unsanitize();
		}

		// deal with array's or array emulating objects
		elseif (is_array($var) or ($var instanceOf \Traversable and $var instanceOf \ArrayAccess))
		{
			// recurse on array values
			foreach($var as $key => $value)
			{
				$var[$key] = $this->unsanitize($value);
			}
		}
	}

	/**
	 * Sets a global variable, similar to [static::set], except that the
	 * variable will be accessible to all views.
	 *
	 *     View::set_global($name, $value);
	 *
	 * @param   string  $key     variable name or an array of variables
	 * @param   mixed   $value   value
	 * @param   bool    $filter  whether to filter the data or not
	 * @return  void
	 */
	public static function set_global($key, $value = null, $filter = null)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				if ($filter !== null)
				{
					static::$global_filter[$name] = $filter;
				}
				static::$global_data[$name] = $value;
			}
		}
		else
		{
			if ($filter !== null)
			{
				static::$global_filter[$key] = $filter;
			}
			static::$global_data[$key] = $value;
		}
	}

	/**
	 * Assigns a global variable by reference, similar to [static::bind], except
	 * that the variable will be accessible to all views.
	 *
	 *     View::bind_global($key, $value);
	 *
	 * @param   string  $key     variable name
	 * @param   mixed   $value   referenced variable
	 * @param   bool    $filter  whether to filter the data or not
	 * @return  void
	 */
	public static function bind_global($key, &$value, $filter = null)
	{
		if ($filter !== null)
		{
			static::$global_filter[$key] = $filter;
		}
		static::$global_data[$key] =& $value;
	}

	/**
	 * Sets whether to filter the data or not.
	 *
	 *     $view->auto_filter(false);
	 *
	 * @param   bool  $filter  whether to auto filter or not
	 * @return  View
	 */
	public function auto_filter($filter = true)
	{
		if (func_num_args() == 0)
		{
			return $this->auto_filter;
		}

		$this->auto_filter = $filter;

		return $this;
	}

	/**
	 * Sets the view filename.
	 *
	 *     $view->set_filename($file);
	 *
	 * @param   string  $file    view filename
	 * @param   bool    $prefix  whether or not to reverse the search
	 * @return  View
	 * @throws  \FuelException
	 */
	public function set_filename($file, $reverse = false)
	{
		// reset the filename
		$this->file_name = null;

		// define the list of files to search
		$searches = array(
			array('file' => $file, 'extension' => $this->extension),
		);

		// if the file contains a dot, is it an extension of a part of the filename?
		if (strpos($file, '.') !== false)
		{
			// strip the extension from it
			$pathinfo = pathinfo($file);

			// add the result to the search list
			if ($reverse)
			{
				array_unshift($searches, array(
					'file' => substr($file, 0, strlen($pathinfo['extension'])*-1 - 1),
					 'extension' => $pathinfo['extension'],
				));
			}
			else
			{
				$searches[] = array(
					'file' => substr($file, 0, strlen($pathinfo['extension'])*-1 - 1),
					 'extension' => $pathinfo['extension'],
				);
			}
		}

		// set find_file's one-time-only search paths
		\Finder::instance()->flash($this->request_paths);

		// locate the view file
		foreach ($searches as $search)
		{
			if ($path = \Finder::search('views', $search['file'], '.'.$search['extension'], false, false))
			{
				// store the file info locally
				$this->file_name = $path;
				$this->extension = $search['extension'];

				break;
			}
		}

		// did we find it?
		if ( ! $this->file_name)
		{
			throw new \FuelException('The requested view could not be found: '.\Fuel::clean_path($search['file']).'.'.$search['extension']);
		}

		return $this;
	}

	/**
	 * Searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $view->get('foo', 'bar');
	 *
	 * If the key is not given or null, the entire data array is returned.
	 *
	 * If a default parameter is not given and the variable does not
	 * exist, it will throw an OutOfBoundsException.
	 *
	 * @param   string  $key      The variable name
	 * @param   mixed   $default  The default value to return
	 * @return  mixed
	 * @throws  \OutOfBoundsException
	 */
	public function &get($key = null, $default = null)
	{
		if (func_num_args() === 0 or $key === null)
		{
			return $this->data;
		}
		elseif (strpos($key, '.') === false)
		{
			if (array_key_exists($key, $this->data))
			{
				return $this->data[$key];
			}
			elseif (array_key_exists($key, static::$global_data))
			{
				return static::$global_data[$key];
			}
		}
		else
		{
			if (($result = \Arr::get($this->data, $key, \Arr::get(static::$global_data, $key, '__KEY__LOOKUP__MISS__'))) !== '__KEY__LOOKUP__MISS__')
			{
				return $result;
			}
		}

		if (is_null($default) and func_num_args() === 1)
		{
			throw new \OutOfBoundsException('View variable is not set: '.$key);
		}
		else
		{
			// assign it first, you can't return a return value by reference directly!
			$default = \Fuel::value($default);
			return $default;
		}
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This value can be accessed as $foo within the view
	 *     $view->set('foo', 'my value');
	 *
	 * You can also use an array to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the view
	 *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * @param   string   $key     variable name or an array of variables
	 * @param   mixed    $value   value
	 * @param   bool     $filter  whether to filter the data or not
	 * @return  $this
	 */
	public function set($key, $value = null, $filter = null)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				$this->set($name, $value, $filter);
			}
		}
		else
		{
			if ($filter !== null)
			{
				$this->local_filter[$key] = $filter;
			}

			if (strpos($key, '.') === false)
			{
				$this->data[$key] = $value;
			}
			else
			{
				\Arr::set($this->data, $key, $value);
			}
		}

		return $this;
	}

	/**
	 * The same as set(), except this defaults to not-encoding the variable
	 * on output.
	 *
	 *     $view->set_safe('foo', 'bar');
	 *
	 * @param   string   $key    variable name or an array of variables
	 * @param   mixed    $value  value
	 * @return  $this
	 */
	public function set_safe($key, $value = null)
	{
		return $this->set($key, $value, false);
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This reference can be accessed as $ref within the view
	 *     $view->bind('ref', $bar);
	 *
	 * @param   string   $key     variable name
	 * @param   mixed    $value   referenced variable
	 * @param   bool     $filter  Whether to filter the var on output
	 * @return  $this
	 */
	public function bind($key, &$value, $filter = null)
	{
		if ($filter !== null)
		{
			$this->local_filter[$key] = $filter;
		}
		$this->data[$key] =& $value;

		return $this;
	}

	/**
	 * Renders the view object to a string. Global and local data are merged
	 * and extracted to create local variables within the view file.
	 *
	 *     $output = $view->render();
	 *
	 * [!!] Global variables with the same key name as local variables will be
	 * overwritten by the local variable.
	 *
	 * @param    $file  string  view filename
	 * @return   string
	 * @throws   \FuelException
	 * @uses     static::capture
	 */
	public function render($file = null)
	{
		// reactivate the correct request
		if (class_exists('Request', false))
		{
			$current_request = \Request::active();
			\Request::active($this->active_request);
		}

		// store the current language, and set the correct render language
		if ($this->active_language)
		{
			$current_language = \Config::get('language', 'en');
			\Config::set('language', $this->active_language);
		}

		// override the view filename if needed
		if ($file !== null)
		{
			$this->set_filename($file);
		}

		// and make sure we have one
		if (empty($this->file_name))
		{
			throw new \FuelException('You must set the file to use within your view before rendering');
		}

		// combine local and global data and capture the output
		$return = $this->process_file();

		// restore the current language setting
		$this->active_language and \Config::set('language', $current_language);

		// and the active request class
		if (isset($current_request))
		{
			\Request::active($current_request);
		}

		return $return;
	}

}
