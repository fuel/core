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
 * View class
 *
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package   Fuel
 * @category  Core
 * @link      http://fuelphp.com/docs/classes/view.html
 */
class View {

	/**
	 * @var  array  Global view data
	 */
	protected static $global_data = array();

	/**
	 * @var  array  Holds a list of specific encode rules for global variables
	 */
	protected static $global_encode = array();

	/**
	 * @var  array  Current active search paths
	 */
	protected $request_paths = array();

	/**
	 * @var  bool  Whether to auto-encode the view's data
	 */
	protected $auto_encode = true;

	/**
	 * @var  array  Holds a list of specific encode rules for local variables
	 */
	protected $local_encode = array();

	/**
	 * @var  string  The view's filename
	 */
	protected $file_name;

	/**
	 * @var  array  The view's data
	 */
	protected $data = array();

	/**
	 * @var  string  The view file extension
	 */
	protected $extension = 'php';

	/**
	 * This method is deprecated...use forge() instead.
	 * 
	 * @deprecated until 1.2
	 */
	public static function factory($file = null, $data = null, $auto_encode = null)
	{
		\Log::warning('This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($file, $data, $auto_encode);
	}

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [static::set_filename].
	 *
	 *     $view = View::forge($file);
	 *
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  View
	 */
	public static function forge($file = null, $data = null, $auto_encode = null)
	{
		return new static($file, $data, $auto_encode);
	}

	/**
	 * Sets the initial view filename and local data.
	 *
	 *     $view = new View($file);
	 *
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  void
	 * @uses    View::set_filename
	 */
	public function __construct($file = null, $data = null, $encode = null)
	{
		if (is_object($data) === true)
		{
			$data = get_object_vars($data);
		}
		elseif ($data and ! is_array($data))
		{
			throw new \InvalidArgumentException('The data parameter only accepts objects and arrays.');
		}

		$this->auto_encode = is_null($encode) ? \Config::get('security.auto_encode_view_data', true) : $encode;

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
		if (class_exists('Request', false) and $active = \Request::active() and \Request::main() != $active)
		{
			$this->request_paths = $active->get_paths();
		}
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $view->foo;
	 *
	 * @param   string  variable name
	 * @return  mixed
	 * @throws  OutOfBoundsException
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
	 * @param   string  variable name
	 * @param   mixed   value
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
	 * @param   string  variable name
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
	 * @param   string  variable name
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
			\Error::exception_handler($e);

			return '';
		}
	}

	/**
	 * Captures the output that is generated when a view is included.
	 * The view data will be extracted to make local variables. This method
	 * is static to prevent object scope resolution.
	 *
	 *     $output = $this->process_file();
	 *
	 * @param   string  File override
	 * @param   array   variables
	 * @return  string
	 */
	protected function process_file($file_override = false)
	{
		$clean_room = function ($__file_name, array $__data) {
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
		return $clean_room($file_override ?: $this->file_name, $this->get_data());
	}

	/**
	 * Retrieves all the data, both local and global.  It encodes the data if
	 * necessary.
	 *
	 *     $data = $this->get_data();
	 *
	 * @return  array
	 */
	protected function get_data()
	{
		$clean_it = function ($data, $rules, $auto_encode) {
			foreach ($data as $key => $value)
			{
				if (array_key_exists($key, $rules))
				{
					$encode = $rules[$key];
				}
				$value = \Fuel::value($value);
				$encode = isset($encode) ? $encode : $auto_encode;

				$data[$key] = $encode ? \Security::clean($value, null, 'security.output_filter') : $value;
			}

			return $data;
		};

		$data = array();

		if ( ! empty($this->data))
		{
			$data += $clean_it($this->data, $this->local_encode, $this->auto_encode);
		}

		if ( ! empty(static::$global_data))
		{
			$data += $clean_it(static::$global_data, static::$global_encode, $this->auto_encode);
		}

		return $data;
	}

	/**
	 * Sets a global variable, similar to [static::set], except that the
	 * variable will be accessible to all views.
	 *
	 *     View::set_global($name, $value);
	 *
	 * @param   string  variable name or an array of variables
	 * @param   mixed   value
	 * @param   bool    whether to encode the data or not
	 * @return  void
	 */
	public static function set_global($key, $value = null, $encode = null)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				if ($encode !== null)
				{
					static::$global_encode[$name] = $encode;
				}
				static::$global_data[$name] = $value;
			}
		}
		else
		{
			if ($encode !== null)
			{
				static::$global_encode[$key] = $encode;
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
	 * @param   string  variable name
	 * @param   mixed   referenced variable
	 * @param   bool    whether to encode the data or not
	 * @return  void
	 */
	public static function bind_global($key, &$value, $encode = null)
	{
		if ($encode !== null)
		{
			static::$global_encode[$key] = $encode;
		}
		static::$global_data[$key] =& $value;
	}

	/**
	 * Sets whether to encode the data or not.
	 *
	 *     $view->auto_encode(false);
	 *
	 * @param   bool  whether to auto encode or not
	 * @return  View
	 */
	public function auto_encode($encode = true)
	{
		$this->auto_encode = $encode;

		return $this;
	}


	/**
	 * Sets the view filename.
	 *
	 *     $view->set_filename($file);
	 *
	 * @param   string  view filename
	 * @return  View
	 * @throws  Fuel_Exception
	 */
	public function set_filename($file)
	{
		// set find_file's one-time-only search paths
		\Fuel::$volatile_paths = $this->request_paths;

		// locate the view file
		if (($path = \Fuel::find_file('views', $file, '.'.$this->extension, false, false)) === false)
		{
			throw new \Fuel_Exception('The requested view could not be found: '.\Fuel::clean_path($file));
		}

		// Store the file path locally
		$this->file_name = $path;

		return $this;
	}

	/**
	 * Searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $view->get('foo', 'bar');
	 *
	 * If a default parameter is not given and the variable does not
	 * exist, it will throw an OutOfBoundsException.
	 *
	 * @param   string  The variable name
	 * @param   mixed   The default value to return
	 * @return  mixed
	 * @throws  OutOfBoundsException
	 */
	public function &get($key, $default = null)
	{
		if (array_key_exists($key, $this->data))
		{
			$value = $this->data[$key];

			if (array_key_exists($key, $this->local_encode))
			{
				$encode = $this->local_encode[$key];
			}
		}
		elseif (array_key_exists($key, static::$global_data))
		{
			$value = static::$global_data[$key];

			if (array_key_exists($key, static::$global_encode))
			{
				$encode = static::$global_encode[$key];
			}
		}

		if (isset($value))
		{
			$value = \Fuel::value($value);
			$encode = isset($encode) ? $encode : $this->auto_encode;

			return $encode ? \Security::clean($value, null, 'security.output_filter') : $value;
		}

		if (is_null($default) and func_num_args() === 0)
		{
			throw new \OutOfBoundsException('View variable is not set: '.$key);
		}
		else
		{
			return \Fuel::value($default);
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
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @param   bool     whether to encode the data or not
	 * @return  $this
	 */
	public function set($key, $value = null, $encode = null)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				if ($encode !== null)
				{
					$this->local_encode[$name] = $encode;
				}
				$this->data[$name] = $value;
			}
		}
		else
		{
			if ($encode !== null)
			{
				$this->local_encode[$key] = $encode;
			}
			$this->data[$key] = $value;
		}

		return $this;
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
	 * @param   string   variable name
	 * @param   mixed    referenced variable
	 * @param   bool     Whether to encode the var on output
	 * @return  $this
	 */
	public function bind($key, &$value, $encode = null)
	{
		if ($encode !== null)
		{
			$this->local_encode[$key] = $encode;
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
	 * @param    string  view filename
	 * @return   string
	 * @throws   Fuel_Exception
	 * @uses     static::capture
	 */
	public function render($file = null)
	{
		if ($file !== null)
		{
			$this->set_filename($file);
		}

		if (empty($this->file_name))
		{
			throw new \Fuel_Exception('You must set the file to use within your view before rendering');
		}

		// Combine local and global data and capture the output
		return $this->process_file();
	}

}
