<?php
/**
 * Part of the Fuel framework.
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
 * Form Class
 *
 * Helper for creating forms with support for creating dynamic form objects.
 *
 * @package   Fuel
 * @category  Core
 */
class Form
{

	/**
	 * Valid types for input tags (including HTML5)
	 */
	protected static $_valid_inputs = array(
		'button', 'checkbox', 'color', 'date', 'datetime',
		'datetime-local', 'email', 'file', 'hidden', 'image',
		'month', 'number', 'password', 'radio', 'range',
		'reset', 'search', 'submit', 'tel', 'text', 'time',
		'url', 'week'
	);

	/**
	 * When autoloaded this will method will be fired, load once and once only
	 *
	 * @param   string  Ftp filename
	 * @param   array   array of values
	 * @return  void
	 */
	public static function _init()
	{
		\Config::load('form', true);
	}

	/**
	 * This method is deprecated...use forge() instead.
	 *
	 * @deprecated until 1.2
	 */
	public static function factory($fieldset = 'default', array $config = array())
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($fieldset, $config);
	}

	public static function forge($fieldset = 'default', array $config = array())
	{
		if (is_string($fieldset))
		{
			($set = \Fieldset::instance($fieldset)) and $fieldset = $set;
		}

		if ($fieldset instanceof Fieldset)
		{
			if ($fieldset->form(false) != null)
			{
				throw new \DomainException('Form instance already exists, cannot be recreated. Use instance() instead of factory() to retrieve the existing instance.');
			}
		}

		return new static($fieldset, $config);
	}

	public static function instance($name = null)
	{
		$fieldset = \Fieldset::instance($name);
		return $fieldset === false ? false : $fieldset->form();
	}

	/**
	 * Create a form open tag
	 *
	 * @param   string|array  action string or array with more tag attribute settings
	 * @return  string
	 */
	public static function open($attributes = array(), array $hidden = array())
	{
		$attributes = ! is_array($attributes) ? array('action' => $attributes) : $attributes;

		// If there is still no action set, Form-post
		if( ! array_key_exists('action', $attributes) or $attributes['action'] === null)
		{
			$attributes['action'] = \Uri::main();
		}


		// If not a full URL, create one
		elseif ( ! strpos($attributes['action'], '://'))
		{
			$attributes['action'] = \Uri::create($attributes['action']);
		}

		if (empty($attributes['accept-charset']))
		{
			$attributes['accept-charset'] = strtolower(\Fuel::$encoding);
		}

		// If method is empty, use POST
		! empty($attributes['method']) || $attributes['method'] = \Config::get('form.form_method', 'post');

		$form = '<form';
		foreach ($attributes as $prop => $value)
		{
			$form .= ' '.$prop.'="'.$value.'"';
		}
		$form .= '>';

		// Add hidden fields when given
		foreach ($hidden as $field => $value)
		{
			$form .= PHP_EOL.static::hidden($field, $value);
		}

		return $form;
	}

	/**
	 * Create a form close tag
	 *
	 * @return  string
	 */
	public static function close()
	{
		return '</form>';
	}

	/**
	 * Create a fieldset open tag
	 *
	 * @param   array   array with tag attribute settings
	 * @param   string  string for the fieldset legend
	 * @return  string
	 */
	public static function fieldset_open($attributes = array(), $legend = null)
	{
		$fieldset_open = '<fieldset ' . array_to_attr($attributes) . ' >';

		! is_null($legend) and $attributes['legend'] = $legend;
		if ( ! empty($attributes['legend']))
		{
			$fieldset_open.= "\n<legend>".$attributes['legend']."</legend>";
		}

		return $fieldset_open;
	}

	/**
	 * Create a fieldset close tag
	 *
	 * @return string
	 */
	public static function fieldset_close()
	{
		return '</fieldset>';
	}

	/**
	 * Create a form input
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function input($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
			! array_key_exists('value', $attributes) and $attributes['value'] = '';
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}

		$attributes['type'] = empty($attributes['type']) ? 'text' : $attributes['type'];

		if ( ! in_array($attributes['type'], static::$_valid_inputs))
		{
			throw new \InvalidArgumentException(sprintf('"%s" is not a valid input type.', $attributes['type']));
		}

		if (\Config::get('form.prep_value', true) && empty($attributes['dont_prep']))
		{
			$attributes['value'] = static::prep_value($attributes['value']);
			unset($attributes['dont_prep']);
		}

		if (empty($attributes['id']) && \Config::get('form.auto_id', false) == true)
		{
			$attributes['id'] = \Config::get('form.auto_id_prefix', 'form_').$attributes['name'];
		}

		return html_tag('input', static::attr_to_string($attributes));
	}

	/**
	 * Create a hidden field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function hidden($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'hidden';

		return static::input($attributes);
	}

	/**
	 * Create a password input field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function password($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'password';

		return static::input($attributes);
	}

	/**
	 * Create a radio button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function radio($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'radio';

		return static::input($attributes);
	}

	/**
	 * Create a checkbox
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function checkbox($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'checkbox';

		return static::input($attributes);
	}

	/**
	 * Create a file upload input field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   array
	 * @return  string
	 */
	public static function file($field, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
		}
		$attributes['type'] = 'file';

		return static::input($attributes);
	}

	/**
	 * Create a button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function button($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
			$value = isset($attributes['value']) ? $attributes['value'] : $value;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$value = isset($value) ? $value :  $attributes['name'];
		}

		return html_tag('button', static::attr_to_string($attributes), $value);
	}

	/**
	 * Create a reset button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function reset($field = 'reset', $value = 'Reset', array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'reset';

		return static::input($attributes);
	}

	/**
	 * Create a submit button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function submit($field = 'submit', $value = 'Submit', array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'submit';

		return static::input($attributes);
	}

	/**
	 * Create a textarea field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function textarea($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}

		$value = empty($attributes['value']) ? '' : $attributes['value'];
		unset($attributes['value']);

		if (\Config::get('form.prep_value', true) && empty($attributes['dont_prep']))
		{
			$value = static::prep_value($value);
			unset($attributes['dont_prep']);
		}

		if (empty($attributes['id']) && \Config::get('form.auto_id', false) == true)
		{
			$attributes['id'] = \Config::get('form.auto_id_prefix', '').$attributes['name'];
		}

		return html_tag('textarea', static::attr_to_string($attributes), $value);
	}

	/**
	 * Select
	 *
	 * Generates a html select element based on the given parameters
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string  selected value(s)
	 * @param   array   array of options and option groups
	 * @param   array
	 * @return  string
	 */
	public static function select($field, $values = null, array $options = array(), array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;

			if ( ! isset($attributes['selected']))
			{
				$attributes['selected'] = ! isset($attributes['value']) ? null : $attributes['value'];
			}
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['selected'] = $values;
			$attributes['options'] = $options;
		}
		unset($attributes['value']);

		if ( ! isset($attributes['options']) || ! is_array($attributes['options']))
		{
			throw new \InvalidArgumentException(sprintf('Select element "%s" is either missing the "options" or "options" is not array.', $attributes['name']));
		}
		// Get the options then unset them from the array
		$options = $attributes['options'];
		unset($attributes['options']);

		// Get the selected options then unset it from the array
		// and make sure they're all strings to avoid type conversions
		$selected = ! isset($attributes['selected']) ? array() : array_map(function($a) { return (string) $a; }, array_values((array) $attributes['selected']));

		unset($attributes['selected']);

		// closure to recusively process the options array
		$listoptions = function (array $options, $selected, $level = 1) use (&$listoptions) {

			$input = PHP_EOL;
			foreach ($options as $key => $val)
			{
				if (is_array($val))
				{
					$optgroup = $listoptions($val, $selected, $level + 1);
					$optgroup .= str_repeat("\t", $level);
					$input .= str_repeat("\t", $level).html_tag('optgroup', array('label' => $key , 'style' => 'text-indent: '.(10*($level-1)).'px;'), $optgroup).PHP_EOL;
				}
				else
				{
					$opt_attr = array('value' => $key, 'style' => 'text-indent: '.(10*($level-1)).'px;');
					(in_array((string)$key, $selected, TRUE)) && $opt_attr[] = 'selected';
					$input .= str_repeat("\t", $level);
					$opt_attr['value'] = (\Config::get('form.prep_value', true) && empty($attributes['dont_prep'])) ?
						\Form::prep_value($opt_attr['value']) : $opt_attr['value'];
					$input .= html_tag('option', $opt_attr, $val).PHP_EOL;
				}
			}

			return $input;
		};

		// generate the select options list
		$input = $listoptions($options, $selected).str_repeat("\t", 0);

		if (empty($attributes['id']) && \Config::get('form.auto_id', false) == true)
		{
			$attributes['id'] = \Config::get('form.auto_id_prefix', '').$attributes['name'];
		}

		return html_tag('select', static::attr_to_string($attributes), $input);
	}

	/**
	 * Create a label field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function label($label, $id = null, array $attributes = array())
	{
		if (is_array($label))
		{
			$attributes = $label;
			$label = $attributes['label'];
			isset($attributes['id']) and $id = $attributes['id'];
		}

		if (empty($attributes['for']) && \Config::get('form.auto_id', false) == true)
		{
			$attributes['for'] = \Config::get('form.auto_id_prefix', 'form_').$id;
		}
		
		unset($attributes['label']);
		unset($attributes['id']);

		return html_tag('label', $attributes, \Lang::get($label) ?: $label);
	}

	/**
	 * Prep Value
	 *
	 * Prepares the value for display in the form
	 *
	 * @param   string
	 * @return  string
	 */
	public static function prep_value($value)
	{
		$value = htmlspecialchars($value);
		$value = str_replace(array("'", '"'), array("&#39;", "&quot;"), $value);

		return $value;
	}

	/**
	 * Attr to String
	 *
	 * Wraps the global attributes function and does some form specific work
	 *
	 * @param   array  $attr
	 * @return  string
	 */
	protected static function attr_to_string($attr)
	{
		unset($attr['label']);
		return array_to_attr($attr);
	}

	/* ---------------------------------------------------------------------------- */

	/**
	 * @var  Fieldset
	 */
	protected $fieldset;

	protected function __construct($fieldset, array $config = array())
	{
		if ($fieldset instanceof Fieldset)
		{
			$fieldset->form($this);
			$this->fieldset = $fieldset;
		}
		else
		{
			$this->fieldset = \Fieldset::forge($fieldset, array('form_instance' => $this));
		}

		foreach ($config as $key => $val)
		{
			$this->set_config($key, $val);
		}
	}

	/**
	 * Returns the related fieldset
	 *
	 * @return  Fieldset
	 */
	public function fieldset()
	{
		return $this->fieldset;
	}

	/**
	 * Build & template individual field
	 *
	 * @param   string|Fieldset_Field  field instance or name of a field in this form's fieldset
	 * @return  string
	 * @depricated until v1.2
	 */
	public function build_field($field)
	{
		! $field instanceof Fieldset_Field && $field = $this->field($field);

		return $field->build();
	}

	/**
	 * Add a CSRF token and a validation rule to check it
	 */
	public function add_csrf()
	{
		$this->add(\Config::get('security.csrf_token_key', 'fuel_csrf_token'), 'CSRF Token')
			->set_type('hidden')
			->set_value(\Security::fetch_token())
			->add_rule(array('Security', 'check_token'));

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
		$this->fieldset->set_config($config, $value);

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
			return $this->fieldset->get_config();
		}

		if (is_array($key))
		{
			$output = array();
			foreach ($key as $k)
			{
				$output[$k] = $this->fieldset->get_config($k, null) === null
					? $this->fieldset->get_config($k, $default)
					: \Config::get('form.'.$k, $default);
			}
			return $output;
		}

		return $this->fieldset->get_config($key, null) !== null
			? $this->fieldset->get_config($key, $default)
			: \Config::get('form.'.$key, $default);
	}

	/**
	 * Set form attribute
	 *
	 * @param  string
	 * @param  mixed
	 */
	public function set_attribute($key, $value)
	{
		$attributes = $this->get_config('form_attributes', array());
		$attributes[$key] = $value;
		$this->set_config('form_attributes', $attributes);

		return $this;
	}

	/**
	 * Get form attribute
	 *
	 * @param  string
	 * @param  mixed
	 */
	public function get_attribute($key, $default = null)
	{
		$attributes = $this->get_config('form_attributes', array());

		return array_key_exists($key, $attributes) ? $attributes[$key] : $default;
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
	 * Alias for $this->fieldset->build()
	 */
	public function build($action = null)
	{
		return $this->fieldset()->build($action);
	}

	/**
	 * Alias for $this->fieldset->add()
	 */
	public function add($name, $label = '', array $attributes = array(), array $rules = array())
	{
		return $this->fieldset->add($name, $label, $attributes, $rules);
	}

	/**
	 * Alias for $this->fieldset->add_model()
	 *
	 * @return	Validation	this, to allow chaining
	 */
	public function add_model($class, $instance = null, $method = 'set_form_fields')
	{
		$this->fieldset->add_model($class, $instance, $method);

		return $this;
	}

	/**
	 * Alias for $this->fieldset->field()
	 */
	public function field($name = null, $flatten = false)
	{
		return $this->fieldset->field($name, $flatten);
	}

	/**
	 * Alias for $this->fieldset->populate() for this fieldset
	 */
	public function populate($input, $repopulate = false)
	{
		$this->fieldset->populate($input, $repopulate);
	}

	/**
	 * Alias for $this->fieldset->repopulate() for this fieldset
	 */
	public function repopulate()
	{
		$this->fieldset->repopulate();
	}
}


