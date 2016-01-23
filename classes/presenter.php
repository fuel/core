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
 * Presenter
 *
 * @package	    Fuel
 * @subpackage  Core
 * @category    Core * @author      Jelmer Schreuder
 */
abstract class Presenter
{
	// namespace prefix
	protected static $ns_prefix = 'Presenter_';

	/**
	 * Factory for fetching the Presenter
	 *
	 * @param   string  $presenter    Presenter classname without View_ prefix or full classname
	 * @param   string  $method       Method to execute
	 * @param   bool    $auto_filter  Auto filter the view data
	 * @param   string  $view         View to associate with this presenter
	 * @return  Presenter
	 */
	public static function forge($presenter, $method = 'view', $auto_filter = null, $view = null)
	{
		// determine the presenter namespace from the current request context
		$namespace = \Request::active() ? ucfirst(\Request::active()->module) : '';

		// create the list of possible class prefixes
		$prefixes = array(static::$ns_prefix, $namespace.'\\');

		/**
		 * Add non prefixed classnames to the list as well, for BC reasons
		 *
		 * @deprecated 1.6
		 */
		if ( ! empty($namespace))
		{
			array_unshift($prefixes, $namespace.'\\'.static::$ns_prefix);
			$prefixes[] = '';
		}

		// loading from a specific namespace?
		if (strpos($presenter, '::') !== false)
		{
			$split = explode('::', $presenter, 2);
			if (isset($split[1]))
			{
				array_unshift($prefixes, ucfirst($split[0]).'\\'.static::$ns_prefix);
				$presenter = $split[1];
			}
		}

		// if no custom view is given, make it equal to the presenter name
		is_null($view) and $view = $presenter;

		// strip any extensions from the view name to determine the presenter to load
		$presenter = \Inflector::words_to_upper(str_replace(
			array('/', DS),
			'_',
			strpos($presenter, '.') === false ? $presenter : substr($presenter, 0, -strlen(strrchr($presenter, '.')))
		));

		// create the list of possible presenter classnames, start with the namespaced one
		$classes = array();
		foreach ($prefixes as $prefix)
		{
			$classes[] = $prefix.$presenter;
		}

		// check if we can find one
		foreach ($classes as $class)
		{
			if (class_exists($class))
			{
				return new $class($method, $auto_filter, $view);
			}
		}

		throw new \OutOfBoundsException('Presenter "'.reset($classes).'" could not be found.');
	}

	/**
	 * @var  string  method to execute when rendering
	 */
	protected $_method;

	/**
	 * @var  string|View  view name, after instantiation a View object
	 */
	protected $_view;

	/**
	 * @var  bool  whether or not to use auto filtering
	 */
	protected $_auto_filter;

	/**
	 * @var  Request  active request during Presenter creation for proper context
	 */
	protected $_active_request;

	protected function __construct($method, $auto_filter = null, $view = null)
	{
		$this->_auto_filter = $auto_filter;
		$this->_view === null and $this->_view = $view;
		class_exists('Request', false) and $this->_active_request = \Request::active();

		if (empty($this->_view))
		{
			// Take the class name and guess the view name
			$class = get_class($this);
			$this->_view = strtolower(str_replace('_', DS, preg_replace('#^([a-z0-9_]*\\\\)?(View_)?#i', '', $class)));
		}

		$this->set_view();

		$this->_method = $method;
	}

	/**
	 * Returns the View object associated with this Presenter
	 *
	 * @return  View
	 */
	public function get_view()
	{
		return $this->_view;
	}

	/**
	 * Construct the View object
	 */
	protected function set_view()
	{
		$this->_view instanceOf View or $this->_view = \View::forge($this->_view);
	}

	/**
	 * Returns the active request object.
	 *
	 * @return  Request
	 */
	protected function request()
	{
		return $this->_active_request;
	}

	/**
	 * Executed before the view method
	 */
	public function before() {}

	/**
	 * The default view method
	 * Should set all expected variables upon itself
	 */
	public function view() {}

	/**
	 * Executed after the view method
	 */
	public function after() {}

	/**
	 * Fetches an existing value from the template
	 *
	 * @param   mixed  $name
	 * @return  mixed
	 */
	public function & __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Gets a variable from the template
	 *
	 * @param   null  $key
	 * @param   null  $default
	 * @return  string
	 */
	public function & get($key = null, $default = null)
	{
		if (is_null($default) and func_num_args() === 1)
		{
			return $this->_view->get($key);
		}
		return $this->_view->get($key, $default);
	}

	/**
	 * Sets and sanitizes a variable on the template
	 *
	 * @param   string  $key
	 * @param   mixed   $value
	 * @return  Presenter
	 */
	public function __set($key, $value)
	{
		return $this->set($key, $value);
	}

	/**
	 * Sets a variable on the template
	 *
	 * @param   string     $key
	 * @param   mixed      $value
	 * @param   bool|null  $filter
	 * @return  $this
	 */
	public function set($key, $value = null, $filter = null)
	{
		is_null($filter) and $filter = $this->_auto_filter;
		$this->_view->set($key, $value, $filter);

		return $this;
	}

	/**
	 * The same as set(), except this defaults to not-encoding the variable
	 * on output.
	 *
	 *     $view->set_safe('foo', 'bar');
	 *
	 * @param   string  $key    variable name or an array of variables
	 * @param   mixed   $value  value
	 * @return  $this
	 */
	public function set_safe($key, $value = null)
	{
		return $this->set($key, $value, false);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($view->foo);
	 *
	 * @param   string  $key	variable name
	 * @return  boolean
	 */
	public function __isset($key)
	{
		return isset($this->_view->$key);
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($view->foo);
	 *
	 * @param   string  $key	variable name
	 * @return  void
	 */
	public function __unset($key)
	{
		unset($this->_view->$key);
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     $this->bind('ref', $bar);
	 *
	 * @param   string   $key     variable name
	 * @param   mixed    $value   referenced variable
	 * @param   bool     $filter  Whether to filter the var on output
	 * @return  $this
	 */
	public function bind($key, &$value, $filter = null)
	{
		$this->_view->bind($key, $value, $filter);

		return $this;
	}

	/**
	 * Change auto filter setting
	 *
	 * @param   null|bool  change setting (bool) or get the current setting (null)
	 * @return  void|bool  returns current setting or nothing when it is changed
	 */
	public function auto_filter($setting = null)
	{
		if (func_num_args() == 0)
		{
			return $this->_view->auto_filter();
		}

		return $this->_view->auto_filter($setting);
	}

	/**
	 * Add variables through method and after() and create template as a string
	 */
	public function render()
	{
		if (class_exists('Request', false))
		{
			$current_request = \Request::active();
			\Request::active($this->_active_request);
		}

		$this->before();
		$this->{$this->_method}();
		$this->after();

		$return = $this->_view->render();

		if (class_exists('Request', false))
		{
			\Request::active($current_request);
		}

		return $return;
	}

	/**
	 * Auto-render on toString
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
}
