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



// ------------------------------------------------------------------------

/**
 * Fieldset Class
 *
 * Define a set of fields that can be used to generate a form or to validate input.
 *
 * @package		Fuel
 * @category	Core
 * @author		Jelmer Schreuder
 */
class Fieldset
{
	/**
	 * @var	Fieldset
	 */
	protected static $_instance;

	/**
	 * @var	array	contains references to all instantiations of Fieldset
	 */
	protected static $_instances = array();

	/**
	 * This method is deprecated...use forge() instead.
	 * 
	 * @deprecated until 1.2
	 */
	public static function factory($name = 'default', array $config = array())
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($name, $config);
	}

	public static function forge($name = 'default', array $config = array())
	{
		if ($exists = static::instance($name))
		{
			\Error::notice('Fieldset with this name exists already, cannot be overwritten.');
			return $exists;
		}

		static::$_instances[$name] = new static($name, $config);

		if ($name == 'default')
		{
			static::$_instance = static::$_instances[$name];
		}

		return static::$_instances[$name];
	}

	/**
	 * Return a specific instance, or the default instance (is created if necessary)
	 *
	 * @param	string	driver id
	 * @return	Auth_Login_Driver
	 */
	public static function instance($instance = null)
	{
		if ($instance !== null)
		{
			if ( ! array_key_exists($instance, static::$_instances))
			{
				return false;
			}

			return static::$_instances[$instance];
		}

		if (static::$_instance === null)
		{
			static::$_instance = static::forge();
		}

		return static::$_instance;
	}

	/**
	 * @var	string	instance id
	 */
	protected $name;

	/**
	 * @var	array	array of Fieldset_Field objects
	 */
	protected $fields = array();

	/**
	 * @var	Validation	instance of validation
	 */
	protected $validation;

	/**
	 * @var	Form	instance of form
	 */
	protected $form;

	/**
	 * @var	array	configuration array
	 */
	protected $config = array();

	/**
	 * Class constructor
	 *
	 * @param  string
	 * @param  array
	 */
	protected function __construct($name, array $config = array())
	{
		if (isset($config['validation_instance']))
		{
			$this->validation($config['validation_instance']);
			unset($config['validation_instance']);
		}
		if (isset($config['form_instance']))
		{
			$this->form($config['form_instance']);
			unset($config['form_instance']);
		}

		$this->name = (string) $name;
		$this->config = $config;
	}

	/**
	 * Get related Validation instance or create it
	 *
	 * @param   bool|Validation
	 * @return  Validation
	 */
	public function validation($instance = true)
	{
		if ($instance instanceof Validation)
		{
			$this->validation = $instance;
			return $instance;
		}

		if (empty($this->validation) and $instance === true)
		{
			$this->validation = \Validation::forge($this);
		}

		return $this->validation;
	}

	/**
	 * Get related Form instance or create it
	 *
	 * @param   bool|Form
	 * @return  Form
	 */
	public function form($instance = true)
	{
		if ($instance instanceof Form)
		{
			$this->form = $instance;
			return $instance;
		}

		if (empty($this->form) and $instance === true)
		{
			$this->form = \Form::forge($this);
		}

		return $this->form;
	}

	/**
	 * Factory for Fieldset_Field objects
	 *
	 * @param	string
	 * @param	string
	 * @param	array
	 * @param	array
	 * @return	Fieldset_Field
	 */
	public function add($name, $label = '', array $attributes = array(), array $rules = array())
	{
		if (empty($name) || (is_array($name) and empty($name['name'])))
		{
			throw new \InvalidArgumentException('Cannot create field without name.');
		}

		// Allow passing the whole config in an array, will overwrite other values if that's the case
		if (is_array($name))
		{
			$attributes = $name;
			$label = isset($name['label']) ? $name['label'] : '';
			$rules = isset($name['rules']) ? $name['rules'] : array();
			$name = $name['name'];
		}

		// Check if it exists already, if so: return and give notice
		if ($field = static::field($name))
		{
			\Error::notice('Field with this name exists already, cannot be overwritten through add().');
			return $field;
		}

		$field = new \Fieldset_Field($name, $label, $attributes, $rules, $this);
		$this->fields[$name] = $field;

		return $field;
	}

	/**
	 * Get Field instance
	 *
	 * @param	string					null to fetch an array of all
	 * @return	Fieldset_Field|false	returns false when field wasn't found
	 */
	public function field($name = null)
	{
		if ($name === null)
		{
			return $this->fields;
		}

		if ( ! array_key_exists($name, $this->fields))
		{
			return false;
		}

		return $this->fields[$name];
	}

	/**
	 * Add a model's fields
	 * The model must have a method "set_form_fields" that takes this Fieldset instance
	 * and adds fields to it.
	 *
	 * @param	string|Object	either a full classname (including full namespace) or object instance
	 * @param	array|Object	array or object that has the exactly same named properties to populate the fields
	 * @param	string			method name to call on model for field fetching
	 * @return	Fieldset		this, to allow chaining
	 */
	public function add_model($class, $instance = null, $method = 'set_form_fields')
	{
		// Add model to validation callables for validation rules
		$this->validation()->add_callable($class);

		if ((is_string($class) and is_callable($callback = array('\\'.$class, $method)))
			|| is_callable($callback = array($class, $method)))
		{
			$instance ? call_user_func($callback, $this, $instance) : call_user_func($callback, $this);
		}

		return $this;
	}

	/**
	 * Sets a config value on the fieldset
	 *
	 * @param	string
	 * @param	mixed
	 * @return	Fieldset	this, to allow chaining
	 */
	public function set_config($config, $value = null)
	{
		$config = is_array($config) ? $config : array($config => $value);
		foreach ($config as $key => $value)
		{
			$this->config[$key] = $value;
		}

		return $this;
	}

	/**
	 * Get a single or multiple config values by key
	 *
	 * @param	string|array	a single key or multiple in an array, empty to fetch all
	 * @param	mixed			default output when config wasn't set
	 * @return	mixed|array		a single config value or multiple in an array when $key input was an array
	 */
	public function get_config($key = null, $default = null)
	{
		if ($key === null)
		{
			return $this->config;
		}

		if (is_array($key))
		{
			$output = array();
			foreach ($key as $k)
			{
				$output[$k] = array_key_exists($k, $this->config) ? $this->config[$k] : $default;
			}
			return $output;
		}

		return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
	}

	/**
	 * Populate the form's values using an input array or object
	 *
	 * @param   array|object
	 * @param   bool
	 * @return  Fieldset  this, to allow chaining
	 */
	public function populate($input, $repopulate = false)
	{
		foreach ($this->fields as $f)
		{
			if (is_array($input) or $input instanceof \ArrayAccess)
			{
				if (isset($input[$f->name]))
				{
					$f->set_value($input[$f->name], true);
				}
			}
			elseif (is_object($input) and property_exists($input, $f->name))
			{
				$f->set_value($input->{$f->name}, true);
			}
		}

		// Optionally overwrite values using post/get
		if ($repopulate)
		{
			$this->repopulate();
		}

		return $this;
	}

	/**
	 * Set all fields to the input from get or post (depends on the form method attribute)
	 *
	 * @param   array|object  input for initial population of fields, this is deprecated - you should use populate() instea
	 * @return  Fieldset  this, to allow chaining
	 */
	public function repopulate($deprecated = null)
	{
		// The following usage will be deprecated in Fuel 1.1
		if ( ! is_null($deprecated))
		{
			return $this->populate($deprecated, true);
		}

		foreach ($this->fields as $f)
		{
			// Don't repopulate the CSRF field
			if ($f->name === \Config::get('security.csrf_token_key', 'fuel_csrf_token'))
			{
				continue;
			}

			if (strtolower($this->form()->get_attribute('method', 'post')) == 'get')
			{
				if (($value = \Input::get($f->name, null)) !== null)
				{
					$f->set_value($value, true);
				}
			}
			else
			{
				if (($value = \Input::post($f->name, null)) !== null)
				{
					$f->set_value($value, true);
				}
			}
		}

		return $this;
	}

	/**
	 * Magic method toString that will build this as a form
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->build();
	}

	/**
	 * Alias for $this->form()->build() for this fieldset
	 */
	public function build($action = null)
	{
		return $this->form()->build($action);
	}

	/**
	 * Alias for $this->validation()->input()
	 */
	public function input($field = null)
	{
		return $this->validation()->input($field);
	}

	/**
	 * Alias for $this->validation()->validated()
	 */
	public function validated($field = null)
	{
		return $this->validation()->validated($field);
	}

	/**
	 * Alias for $this->validation()->errors()
	 */
	public function errors($field = null)
	{
		return $this->validation()->errors($field);
	}

	/**
	 * Alias for $this->validation()->show_errors()
	 */
	public function show_errors(array $config = array())
	{
		return $this->validation()->show_errors($config);
	}
}


