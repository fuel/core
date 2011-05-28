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
	public $callback = '';
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
		$this->field = $field;
		$this->value = $value;
		$this->params = $params;

		/**
		 * Simplify callback for rule, class/object and method are seperated by 1 colon
		 * and objects become their classname without the namespace.
		 * Rules called on a callable are considered without classname & method prefix
		 */
		if (is_array($callback))
		{
			foreach ($field->fieldset()->validation()->callables() as $c)
			{
				if ($c == $callback[0] && substr($callback[1], 0, 12) == '_validation_')
				{
					$callback = substr($callback[1], 12);
					break;
				}
			}
		}
		$this->callback = is_string($callback)
				? str_replace('::', ':', $callback)
				: preg_replace('#^([a-z_]*\\\\)*#i', '', get_class($callback[0])).':'.$callback[1];
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
	public function get_message($msg = false, $open = null, $close = null)
	{
		$open   = \Config::get('validation.open_single_error', '');
		$close  = \Config::get('validation.close_single_error', '');

		if ($msg === false)
		{
			$msg = $this->field->fieldset()->validation()->get_message($this->callback);
			$msg = $msg === false
				? __('validation.'.$this->callback) ?: __('validation.'.Arr::element(explode(':', $this->callback), 0))
				: $msg;
		}
		if ($msg == false)
		{
			return $open.'Validation rule '.$this->callback.' failed for '.$this->field->label.$close;
		}

		// to safe some performance when there are no variables in the $msg
		if (strpos(':', $msg) !== false)
		{
			return $open.$msg.$close;
		}

		$value    = is_array($this->value) ? implode(', ', $this->value) : $this->value;
		$find     = array(':field', ':label', ':value', ':rule');
		$replace  = array($this->field->name, is_array($this->field->label) ? $this->field->label['label'] : $this->field->label, $value, $this->callback);
		foreach($this->params as $key => $val)
		{
			$find[]		= ':param:'.($key + 1);
			$replace[]	= $val;
		}

		return $open.str_replace($find, $replace, $msg).$close;
	}

	public function __toString()
	{
		return $this->get_message();
	}
}

/* End of file validation.php */
