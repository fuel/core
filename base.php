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

/**
 * Loads in a core class and optionally an app class override if it exists.
 *
 * @param   string  $path
 * @param   string  $folder
 * @return  void
 */
if ( ! function_exists('import'))
{
	function import($path, $folder = 'classes')
	{
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		require_once COREPATH.$folder.DIRECTORY_SEPARATOR.$path.'.php';

		if (is_file(APPPATH.$folder.DIRECTORY_SEPARATOR.$path.'.php'))
		{
			require_once APPPATH.$folder.DIRECTORY_SEPARATOR.$path.'.php';
		}
	}
}


if ( ! function_exists('logger'))
{
	function logger($level, $msg, $method = null)
	{
		if ($level > \Config::get('log_threshold'))
		{
			return false;
		}

		! class_exists('Fuel\\Core\\Log') and import('log');
		! class_exists('Log') and class_alias('Fuel\\Core\\Log', 'Log');

		return \Log::write($level, $msg, $method);
	}
}


/**
 * Takes an array of attributes and turns it into a string for an html tag
 *
 * @param	array	$attr
 * @return	string
 */
if ( ! function_exists('array_to_attr'))
{
	function array_to_attr($attr)
	{
		$attr_str = '';

		if ( ! is_array($attr))
		{
			$attr = (array) $attr;
		}

		foreach ($attr as $property => $value)
		{
			// Ignore null values
			if (is_null($value))
			{
				continue;
			}

			// If the key is numeric then it must be something like selected="selected"
			if (is_numeric($property))
			{
				$property = $value;
			}

			$attr_str .= $property.'="'.$value.'" ';
		}

		// We strip off the last space for return
		return trim($attr_str);
	}
}

/**
 * Create a XHTML tag
 *
 * @param	string			The tag name
 * @param	array|string	The tag attributes
 * @param	string|bool		The content to place in the tag, or false for no closing tag
 * @return	string
 */
if ( ! function_exists('html_tag'))
{
	function html_tag($tag, $attr = array(), $content = false)
	{
		$has_content = (bool) ($content !== false and $content !== null);
		$html = '<'.$tag;

		$html .= ( ! empty($attr)) ? ' '.(is_array($attr) ? array_to_attr($attr) : $attr) : '';
		$html .= $has_content ? '>' : ' />';
		$html .= $has_content ? $content.'</'.$tag.'>' : '';

		return $html;
	}
}

/**
 * A case-insensitive version of in_array.
 *
 * @param	mixed	$needle
 * @param	array	$haystack
 * @return	bool
 */
if ( ! function_exists('in_arrayi'))
{
	function in_arrayi($needle, $haystack)
	{
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}
}

/**
 * Gets all the public vars for an object.  Use this if you need to get all the
 * public vars of $this inside an object.
 *
 * @return	array
 */
if ( ! function_exists('get_object_public_vars'))
{
	function get_object_public_vars($obj)
	{
		return get_object_vars($obj);
	}
}

/**
 * Renders a view and returns the output.
 *
 * @param   string	The view name/path
 * @param   array	The data for the view
 * @param   bool    Auto filter override
 * @return  string
 */
if ( ! function_exists('render'))
{
	function render($view, $data = null, $auto_filter = null)
	{
		return \View::forge($view, $data, $auto_filter)->render();
	}
}

/**
 * A wrapper function for Lang::get()
 *
 * @param	mixed	The string to translate
 * @param	array	The parameters
 * @return	string
 */
if ( ! function_exists('__'))
{
	function __($string, $params = array())
	{
		return \Lang::get($string, $params, $string);
	}
}

/**
 * Encodes the given string.  This is just a wrapper function for Security::htmlentities()
 *
 * @param	mixed	The string to encode
 * @return	string
 */
if ( ! function_exists('e'))
{
	function e($string)
	{
		return Security::htmlentities($string);
	}
}

/**
 * Takes a classname and returns the actual classname for an alias or just the classname
 * if it's a normal class.
 *
 * @param   string  classname to check
 * @return  string  real classname
 */
if ( ! function_exists('get_real_class'))
{
	function get_real_class($class)
	{
		static $classes = array();

		if ( ! array_key_exists($class, $classes))
		{
			$reflect = new ReflectionClass($class);
			$classes[$class] = $reflect->getName();
		}

		return $classes[$class];
	}
}

/**
 * Loads in the classes used for the error handlers.  The class_exists() calls
 * will trigger the autoloader if it is loaded, if not, then it will import
 * the classes and do the work itself.
 *
 * @return  void
 */
if ( ! function_exists('load_error_classes'))
{
	function load_error_classes()
	{
		! class_exists('Fuel\\Core\\Error') and import('error');
		! class_exists('Error') and class_alias('Fuel\\Core\\Error', 'Error');

		! class_exists('Fuel\\Core\\Debug') and import('debug');
		! class_exists('Debug') and class_alias('Fuel\\Core\\Debug', 'Debug');

		! class_exists('Fuel\\Core\\View') and import('view');
		! class_exists('View') and class_alias('Fuel\\Core\\View', 'View');
	}
}