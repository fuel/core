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
 * NOTE: This class has been taken from the Kohana framework and slightly modified,
 * but on the whole all credit goes to them. Over time this will be worked on.
 *
 * @package		Fuel
 * @category	Core
 * @author		Kohana Team
 * @modified	Phil Sturgeon - Fuel Development Team
 * @copyright	(c) 2008-2010 Kohana Team
 * @license		http://kohanaframework.org/license
 * @link		http://fuelphp.com/docs/classes/view.html
 */
class View {

	// array of global view data
	protected static $_global_data = array();

	// Current active search paths
	protected $request_paths = array();

	// Output encoding setting
	public static $auto_encode = true;

	// View filename
	protected $_file;

	// array of local variables
	protected $_data = array();

	// File extension used for views
	protected $extension = 'php';

	/*
	 * initialisation and auto configuration
	 */
	public static function _init()
	{
		static::$auto_encode = \Config::get('security.auto_encode_view_data', true);
	}

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [static::set_filename].
	 *
	 *     $view = View::factory($file);
	 *
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  View
	 */
	public static function factory($file = null, $data = null, $auto_encode = null)
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

		$encode === null and $encode = static::$auto_encode;

		if ($file !== null)
		{
			$this->set_filename($file);
		}

		if ($data !== null)
		{
			if ($encode)
			{
				foreach ($data as $k => $v)
				{
					$data[$k] = \Security::htmlentities($v);
				}
			}

			// Add the values to the current data
			$this->_data = $data + $this->_data;
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
	 * [!!] If the variable has not yet been set, an exception will be thrown.
	 *
	 * @param   string  variable name
	 * @return  mixed
	 * @throws  OutOfBoundsException
	 */
	public function & __get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
			return $this->_data[$key];
		}
		elseif (array_key_exists($key, static::$_global_data))
		{
			return static::$_global_data[$key];
		}
		else
		{
			throw new \OutOfBoundsException('View variable is not set: '.$key);
		}
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
		$this->set($key, $value, static::$auto_encode);
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
		return (isset($this->_data[$key]) or isset(static::$_global_data[$key]));
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
		unset($this->_data[$key], static::$_global_data[$key]);
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
	 *     $output = View::capture($file, $data);
	 *
	 * @param   string  filename
	 * @param   array   variables
	 * @return  string
	 */
	protected static function capture($view_filename, array $view_data)
	{
		// Import the view variables to local namespace
		$view_data AND extract($view_data, EXTR_SKIP);

		if (static::$_global_data)
		{
			// Import the global view variables to local namespace and maintain references
			extract(static::$_global_data, EXTR_REFS);
		}

		// Capture the view output
		ob_start();

		try
		{
			// Load the view within the current scope
			include $view_filename;
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
		$value = ($value instanceof \Closure) ? $value() : $value;

		$encode === null and $encode = static::$auto_encode;

		if (is_array($key))
		{
			foreach ($key as $key2 => $value)
			{
				static::$_global_data[$key2] = $encode ? \Security::htmlentities($value) : $value;
			}
		}
		else
		{
			static::$_global_data[$key] = $encode ? \Security::htmlentities($value) : $value;
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
	 * @return  void
	 */
	public static function bind_global($key, & $value)
	{
		static::$_global_data[$key] =& $value;
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
		static::$auto_encode = $encode;

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
		$this->_file = $path;

		return $this;
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
		$value = ($value instanceof \Closure) ? $value() : $value;

		$encode === null and $encode = static::$auto_encode;

		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				$this->_data[$name] = $encode ? \Security::clean($value, null, 'security.output_filter') : $value;
			}
		}
		else
		{
			$this->_data[$key] = $encode ? \Security::clean($value, null, 'security.output_filter') : $value;
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
	 * @return  $this
	 */
	public function bind($key, & $value)
	{
		$this->_data[$key] =& $value;

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

		if (empty($this->_file))
		{
			throw new \Fuel_Exception('You must set the file to use within your view before rendering');
		}

		// Combine local and global data and capture the output
		return static::capture($this->_file, $this->_data);
	}

}


