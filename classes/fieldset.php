<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;



// ------------------------------------------------------------------------

/**
 * Fieldset Class
 *
 * Define a set of fields that can be used to generate a form or to validate input.
 *
 * @package   Fuel
 * @category  Core
 */
class Fieldset
{
	/**
	 * @var  Fieldset
	 */
	protected static $_instance;

	/**
	 * @var  array  contains references to all instantiations of Fieldset
	 */
	protected static $_instances = array();

	/**
	 * Create Fieldset object
	 *
	 * @param   string    Identifier for this fieldset
	 * @param   array     Configuration array
	 * @return  Fieldset
	 */
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
	 * @param   string  driver id
	 * @return  Fieldset
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
	 * @var  string  instance id
	 */
	protected $name;

	/**
	 * @var  string  tag used to wrap this instance
	 */
	protected $fieldset_tag = null;

	/**
	 * @var  Fieldset  instance to which this instance belongs
	 */
	protected $fieldset_parent = null;

	/**
	 * @var  array  instances that belong to this one
	 */
	protected $fieldset_children = array();

	/**
	 * @var  array  array of Fieldset_Field objects
	 */
	protected $fields = array();

	/**
	 * @var  Validation  instance of validation
	 */
	protected $validation;

	/**
	 * @var  Form  instance of form
	 */
	protected $form;

	/**
	 * @var  array  configuration array
	 */
	protected $config = array();

	/**
	 * Object constructor
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
	 * Set the tag to be used for this fieldset
	 *
	 * @param  string  $tag
	 */
	public function set_fieldset_tag($tag)
	{
		$this->fieldset_tag = $tag;
	}

	/**
	 * Set the parent Fieldset instance
	 *
	 * @param   Fieldset  parent fieldset to which this belongs
	 * @return  Fieldset
	 */
	public function set_parent(Fieldset $fieldset)
	{
		if ( ! empty($this->fieldset_parent))
		{
			throw new \RuntimeException('Fieldset already has a parent, belongs to "'.$this->parent()->name.'".');
		}

		$children = $fieldset->children();
		while ($child = array_shift($children))
		{
			if ($child === $this)
			{
				throw new \RuntimeException('Circular reference detected, adding a Fieldset that\'s already a child as a parent.');
			}
			$children = array_merge($child->children(), $children);
		}

		$this->fieldset_parent = $fieldset;
		$fieldset->add_child($this);
		return $this;
	}

	/**
	 * Add a child Fieldset instance
	 *
	 * @param   Fieldset  $fieldset
	 * @return  Fieldset
	 */
	protected function add_child(Fieldset $fieldset)
	{
		if (is_null($fieldset->fieldset_tag))
		{
			$fieldset->fieldset_tag = 'fieldset';
		}

		$this->fieldset_children[$fieldset->name] = $fieldset;
		return $this;
	}

	/**
	 * Factory for Fieldset_Field objects
	 *
	 * @param   string
	 * @param   string
	 * @param   array
	 * @param   array
	 * @return  Fieldset_Field
	 */
	public function add($name, $label = '', array $attributes = array(), array $rules = array())
	{
		if ($name instanceof Fieldset_Field)
		{
			if ($name->name == '' or $this->field($name->name) !== false)
			{
				throw new \RuntimeException('Fieldname empty or already exists in this Fieldset: "'.$name->name.'".');
			}

			$name->set_fieldset($this);
			$this->fields[$name->name] = $name;
			return $name;
		}
		elseif ($name instanceof Fieldset)
		{
			if (empty($name->name) or $this->field($name->name) !== false)
			{
				throw new \RuntimeException('Fieldset name empty or already exists in this Fieldset: "'.$name->name.'".');
			}

			$name->set_parent($this);
			$this->fields[$name->name] = $name;
			return $name;
		}

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
		if ($field = $this->field($name))
		{
			\Error::notice('Field with this name exists already in this fieldset: "'.$name.'".');
			return $field;
		}

		$this->fields[$name] = new \Fieldset_Field($name, $label, $attributes, $rules, $this);

		return $this->fields[$name];
	}

	/**
	 * Add a new Fieldset_Field before an existing field in a Fieldset
	 *
	 * @param   string  $name
	 * @param   string  $label
	 * @param   array   $attributes
	 * @param   array   $rules
	 * @param   string  $fieldname   fieldname before which the new field is inserted in the fieldset
	 * @return  Fieldset_Field
	 */
	public function add_before($name, $label = '', array $attributes = array(), array $rules = array(), $fieldname = null)
	{
		$field = $this->add($name, $label, $attributes, $rules);

		// Remove from tail and reinsert at correct location
		unset($this->fields[$field->name]);
		if ( ! \Arr::insert_before_key($this->fields, array($name => $field), $fieldname))
		{
			throw new \RuntimeException('Field "'.$fieldname.'" does not exist in this Fieldset. Field "'.$name.'" can not be added.');
		}

		return $field;
	}

	/**
	 * Add a new Fieldset_Field after an existing field in a Fieldset
	 *
	 * @param   string  $name
	 * @param   string  $label
	 * @param   array   $attributes
	 * @param   array   $rules
	 * @param   string  $fieldname   fieldname after which the new field is inserted in the fieldset
	 * @return  Fieldset_Field
	 */
	public function add_after($name, $label = '', array $attributes = array(), array $rules = array(), $fieldname = null)
	{
		$field = $this->add($name, $label, $attributes, $rules);

		// Remove from tail and reinsert at correct location
		unset($this->fields[$field->name]);
		if ( ! \Arr::insert_after_key($this->fields, array($name => $field), $fieldname))
		{
			throw new \RuntimeException('Field "'.$fieldname.'" does not exist in this Fieldset. Field "'.$name.'" can not be added.');
		}

		return $field;
	}

	/**
	 * Get Field instance
	 *
	 * @param   string|null           field name or null to fetch an array of all
	 * @param   bool                  whether to get the fields array or flattened array
	 * @return  Fieldset_Field|false  returns false when field wasn't found
	 */
	public function field($name = null, $flatten = false)
	{
		if ($name === null)
		{
			if ( ! $flatten)
			{
				return $this->fields;
			}

			$fields = $this->fields;
			foreach ($this->fieldset_children as $fs_name => $fieldset)
			{
				\Arr::insert_after_key($fields, $fieldset->field(null, true), $fs_name);
				unset($fields[$fs_name]);
			}
			return $fields;
		}

		if ( ! array_key_exists($name, $this->fields))
		{
			foreach ($this->fieldset_children as $fieldset)
			{
				if (($field = $fieldset->field($name)) !== false)
				{
					return $field;
				}
			}
			return false;
		}

		return $this->fields[$name];
	}

	/**
	 * Add a model's fields
	 * The model must have a method "set_form_fields" that takes this Fieldset instance
	 * and adds fields to it.
	 *
	 * @param   string|Object  either a full classname (including full namespace) or object instance
	 * @param   array|Object   array or object that has the exactly same named properties to populate the fields
	 * @param   string         method name to call on model for field fetching
	 * @return  Fieldset       this, to allow chaining
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
	 * @param   string
	 * @param   mixed
	 * @return  Fieldset  this, to allow chaining
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
	 * @param   string|array  a single key or multiple in an array, empty to fetch all
	 * @param   mixed         default output when config wasn't set
	 * @return  mixed|array   a single config value or multiple in an array when $key input was an array
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
		$fields = $this->field(null, true);
		foreach ($fields as $f)
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
	 * @return  Fieldset      this, to allow chaining
	 */
	public function repopulate()
	{
		$fields = $this->field(null, true);
		foreach ($fields as $f)
		{
			// Don't repopulate the CSRF field
			if ($f->name === \Config::get('security.csrf_token_key', 'fuel_csrf_token'))
			{
				continue;
			}

			if (($value = $f->input()) !== null)
			{
				$f->set_value($value, true);
			}
		}

		return $this;
	}

	/**
	 * Build the fieldset HTML
	 *
	 * @return  string
	 */
	public function build($action = null)
	{
		$attributes = $this->get_config('form_attributes');
		if ($action and ($this->fieldset_tag == 'form' or empty($this->fieldset_tag)))
		{
			$attributes['action'] = $action;
		}

		$open = ($this->fieldset_tag == 'form' or empty($this->fieldset_tag))
			? $this->form()->open($attributes).PHP_EOL
			: $this->form()->{$this->fieldset_tag.'_open'}($attributes);

		$fields_output = '';
		foreach ($this->field() as $f)
		{
			$fields_output .= $f->build().PHP_EOL;
		}

		$close = ($this->fieldset_tag == 'form' or empty($this->fieldset_tag))
			? $this->form()->close($attributes).PHP_EOL
			: $this->form()->{$this->fieldset_tag.'_close'}($attributes);

		$template = $this->form()->get_config((empty($this->fieldset_tag) ? 'form' : $this->fieldset_tag).'_template',
			"\n\t\t{open}\n\t\t<table>\n{fields}\n\t\t</table>\n\t\t{close}\n");
		$template = str_replace(array('{form_open}', '{open}', '{fields}', '{form_close}', '{close}'),
			array($open, $open, $fields_output, $close, $close),
			$template);

		return $template;
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
	 * Return parent Fieldset
	 *
	 * @return Fieldset
	 */
	public function parent()
	{
		return $this->fieldset_parent;
	}

	/**
	 * Return the child fieldset instances
	 *
	 * @return  array
	 */
	public function children()
	{
		return $this->fieldset_children;
	}

	/**
	 * Alias for $this->validation()->input()
	 *
	 * @return  mixed
	 */
	public function input($field = null)
	{
		return $this->validation()->input($field);
	}

	/**
	 * Alias for $this->validation()->validated()
	 *
	 * @return  mixed
	 */
	public function validated($field = null)
	{
		return $this->validation()->validated($field);
	}

	/**
	 * Alias for $this->validation()->error()
	 *
	 * @return  Validation_Error|array
	 */
	public function error($field = null)
	{
		return $this->validation()->error($field);
	}

	/**
	 * Alias for $this->validation()->show_errors()
	 *
	 * @return  string
	 */
	public function show_errors(array $config = array())
	{
		return $this->validation()->show_errors($config);
	}
}


