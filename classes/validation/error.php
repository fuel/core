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
 * Validation error
 *
 * Contains all the information about a validation error
 *
 * @package		Fuel
 * @subpackage	Core
 * @category	Core
 * @author		Jelmer Schreuder
 */
class Validation_Error extends \Exception {
	/**
	 * Load validation Language file when errors are thrown
	 */
	public static function _init()
	{
		\Lang::load('validation', true);
	}

	public $field = '';
	public $value = '';
	public $rule = '';
	public $params = array();

	/**
	 * Constructor
	 *
	 * @param	array		Validation field description
	 * @param	mixed		Unvalidated value
	 * @param	callback	Failed rule callback
	 * @param	array		Failed rule callback params
	 */
	public function __construct($field, $value, $callback, $params)
	{
		$this->field   = $field;
		$this->value   = $value;
		$this->params  = $params;
		$this->rule    = key($callback);
	}

	/**
	 * Get Message
	 *
	 * Shows the error message which can be taken from loaded language file.
	 *
	 * @param	string	HTML to prefix error message
	 * @param	string	HTML to postfix error message
	 * @param	string	Message to use, or false to try and load it from Lang class
	 * @return	string
	 */
	public function get_message($msg = false, $open = '', $close = '')
	{
		$open   = \Config::get('validation.open_single_error', $open);
		$close  = \Config::get('validation.close_single_error', $close);

		if ($msg === false)
		{
			$msg = $this->field->fieldset()->validation()->get_message($this->rule);
			$msg = $msg === false
				? __('validation.'.$this->rule) ?: __('validation.'.Arr::element(explode(':', $this->rule), 0))
				: $msg;
		}
		if ($msg == false)
		{
			return $open.'Validation rule '.$this->rule.' failed for '.$this->field->label.$close;
		}

		// only parse when there's tags in the message
		return $open.(strpos(':', $msg) !== false ? $msg : $this->_replace_tags($msg)).$close;
	}

	protected function _replace_tags($msg)
	{
		// prepare label & value
		$label    = is_array($this->field->label) ? $this->field->label['label'] : $this->field->label;
		$value    = is_array($this->value) ? implode(', ', $this->value) : $this->value;
		if (\Config::get('validation.quote_labels', false) and strpos($label, ' ') !== false)
		{
			// put the label in quotes if it contains spaces
			$label = '"'.$label.'"';
		}

		// setup find & replace arrays
		$find     = array(':field', ':label', ':value', ':rule');
		$replace  = array($this->field->name, $label, $value, $this->rule);

		// add the params to the find & replace arrays
		foreach($this->params as $key => $val)
		{
			// Convert array to just a string "(array)", can't reliably implode as contents might be arrays/objects
			if (is_array($val))
			{
				$val = '(array)';
			}
			// Convert object with __toString or just the classname
			elseif (is_object($val))
			{
				$val = method_exists($val, '__toString') ? (string) $val : get_class($val);
			}

			$find[]     = ':param:'.($key + 1);
			$replace[]  = $val;
		}

		// execute find & replace and return
		return str_replace($find, $replace, $msg);
	}

	public function __toString()
	{
		return $this->get_message();
	}
}

/* End of file validation.php */
