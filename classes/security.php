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
 * Security Class
 *
 * @package		Fuel
 * @category	Core
 * @author		Dan Horrigan
 * @link		http://fuelphp.com/docs/classes/security.html
 */
class Security {

	/**
	 * @var  string  the array key for cookie & post vars to check for the token
	 */
	protected static $csrf_token_key = false;

	/**
	 * Class init
	 *
	 * Fetches CSRF settings and current token
	 */
	public static function _init()
	{
		static::$csrf_token_key = \Config::get('security.csrf_token_key', 'fuel_csrf_token');

		if (\Config::get('security.csrf_autoload', true))
		{
			static::check_token();
		}
	}

	/**
	 * Cleans the request URI
	 */
	public static function clean_uri($uri)
	{
		$filters = \Config::get('security.uri_filter', array());
		$filters = is_array($filters) ? $filters : array($filters);

		return static::clean($uri, $filters);
	}

	/**
	 * Cleans the global $_GET, $_POST and $_COOKIE arrays
	 */
	public static function clean_input()
	{
		$_GET		= static::clean($_GET);
		$_POST		= static::clean($_POST);
		$_COOKIE	= static::clean($_COOKIE);
	}

	/**
	 * Generic variable clean method
	 */
	public static function clean($var, $filters = null)
	{
		is_null($filters) and $filters = \Config::get('security.input_filter', array());
		$filters = is_array($filters) ? $filters : array($filters);

		foreach ($filters as $filter)
		{
			// is this filter a callable local function?
			if (is_string($filter) and is_callable('static::'.$filter))
			{
				$var = static::$filter($var);
			}

			// is this filter a callable function?
			elseif (is_callable($filter))
			{
				if (is_array($var))
				{
					foreach($var as $key => $value)
					{
						$var[$key] = call_user_func($filter, $value);
					}
				}
				else
				{
					$var = call_user_func($filter, $var);
				}
			}

			// assume it's a regex of characters to filter
			else
			{
				if (is_array($var))
				{
					foreach($var as $key => $value)
					{
						$var[$key] = preg_replace('#['.$filter.']#ui', '', $value);
					}
				}
				else
				{
					$var = preg_replace('#['.$filter.']#ui', '', $var);
				}
			}
		}
		return $var;
	}

	public static function xss_clean($value)
	{
		if ( ! is_array($value))
		{
			if ( ! function_exists('htmLawed'))
			{
				import('htmlawed/htmlawed', 'vendor');
			}

			return htmLawed($value, array('safe' => 1, 'balanced' => 0));
		}

		foreach ($value as $k => $v)
		{
			$value[$k] = static::xss_clean($v);
		}

		return $value;
	}

	public static function strip_tags($value)
	{
		if ( ! is_array($value))
		{
			$value = filter_var($value, FILTER_SANITIZE_STRING);
		}
		else
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = static::strip_tags($v);
			}
		}

		return $value;
	}

	public static function htmlentities($value)
	{
		static $already_cleaned = array();

		// Nothing to escape for non-string scalars, or for already processed values
		if (is_bool($value) or is_int($value) or is_float($value) or in_array($value, $already_cleaned, true))
		{
			return $value;
		}

		if (is_string($value))
		{
			$value = htmlentities($value, ENT_COMPAT, \Fuel::$encoding, false);
		}
		elseif (is_array($value) || $value instanceof \Iterator)
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = static::htmlentities($v);
			}

			// Add to $already_cleaned variable when object
			is_object($value) and $already_cleaned[] = $value;
		}
		elseif (is_object($value))
		{
			// Check if the object is whitelisted and return when that's the case
			foreach (\Config::get('security.whitelisted_classes') as $class)
			{
				if (is_a($value, $class))
				{
					// Add to $already_cleaned variable
					$already_cleaned[] = $value;

					return $value;
				}
			}

			// Throw exception when it wasn't whitelisted and can't be converted to String
			if ( ! method_exists($value, '__toString'))
			{
				throw new \RuntimeException('Object class "'.get_class($value).'" could not be converted to string or '.
					'sanitized as ArrayAcces. Whitelist it in security.whitelisted_classes in app/config/config.php '.
					'to allow it to be passed unchecked.');
			}

			$value = static::htmlentities((string) $value);
		}

		return $value;
	}

	/**
	 * Check CSRF Token
	 *
	 * @param   string  CSRF token to be checked, checks post when empty
	 * @return  bool
	 */
	public static function check_token($value = null)
	{
		$value = $value ?: \Input::post(static::$csrf_token_key, 'fail');

		return $value === static::fetch_token();
	}

	/**
	 * Get CSRF Token, which is created by generating the HMAC of the session ID 
	 *
	 * @return  string
	 */
	public static function fetch_token()
	{
		return \Crypt::hmac(\Session::key('session_id'));
	}
}


