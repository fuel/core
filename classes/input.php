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
 * Input class
 *
 * The input class allows you to access HTTP parameters, load server variables
 * and user agent details.
 *
 * @package		Fuel
 * @category	Core
 * @author		Phil Sturgeon
 * @link		http://fuelphp.com/docs/classes/input.html
 */
class Input {


	/**
	 * @var  $detected_uri  The URI that was detected automatically
	 */
	protected static $detected_uri = null;

	/**
	 * Detects and returns the current URI based on a number of different server
	 * variables.
	 *
	 * @return  string
	 */
	public static function detect_uri()
	{
		if (static::$detected_uri !== null)
		{
			return static::$detected_uri;
		}

		if (\Fuel::$is_cli)
		{
			if ($uri = \Cli::option('uri') !== null)
			{
				static::$detected_uri = $uri;
			}
			else
			{
				static::$detected_uri = \Cli::option(1);
			}

			return static::$detected_uri;
		}

		// We want to use PATH_INFO if we can.
		if ( ! empty($_SERVER['PATH_INFO']))
		{
			$uri = $_SERVER['PATH_INFO'];
		}
		// Only use ORIG_PATH_INFO if it contains the path
		elseif ( ! empty($_SERVER['ORIG_PATH_INFO']) and ($path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['ORIG_PATH_INFO'])) != '')
		{
			$uri = $path;
		}
		else
		{
			// Fall back to parsing the REQUEST URI
			if (isset($_SERVER['REQUEST_URI']))
			{
				// Some servers require 'index.php?' as the index page
				// if we are using mod_rewrite or the server does not require
				// the question mark, then parse the url.
				if (\Config::get('index_file') != 'index.php?')
				{
					$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
				}
				else
				{
					$uri = $_SERVER['REQUEST_URI'];
				}
			}
			else
			{
				throw new \Fuel_Exception('Unable to detect the URI.');
			}

			// Remove the base URL from the URI
			$base_url = parse_url(\Config::get('base_url'), PHP_URL_PATH);
			if ($uri != '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
			{
				$uri = substr($uri, strlen($base_url));
			}

			// If we are using an index file (not mod_rewrite) then remove it
			$index_file = \Config::get('index_file');
			if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
			{
				$uri = substr($uri, strlen($index_file));
			}

			// Lets split the URI up in case it containes a ?.  This would
			// indecate the server requires 'index.php?' and that mod_rewrite
			// is not being used.
			preg_match('#(.*?)\?(.*)#i', $uri, $matches);

			// If there are matches then lets set set everything correctly
			if ( ! empty($matches))
			{
				$uri = $matches[1];
				$_SERVER['QUERY_STRING'] = $matches[2];
				parse_str($matches[2], $_GET);
			}
		}

		// Strip the defined url suffix from the uri if needed
		$ext = \Config::get('url_suffix');
		strrchr($uri, '.') === $ext and $uri = substr($uri,0,-strlen($ext));

		// Do some final clean up of the uri
		static::$detected_uri = \Security::clean_uri(str_replace(array('//', '../'), '/', $uri));

		return static::$detected_uri;
	}

	/**
	 * Get the public ip address of the user.
	 *
	 * @return  string
	 */
	public static function ip()
	{
		if (static::server('REMOTE_ADDR') !== null)
		{
			return static::server('REMOTE_ADDR');
		}
		else
		{
			// detection failed, return a dummy IP
			return '0.0.0.0';
		}
	}

	/**
	 * Get the real ip address of the user.  Even if they are using a proxy.
	 *
	 * @return  string		the real ip address of the user
	 */
	public static function real_ip()
	{
		if (static::server('HTTP_X_CLUSTER_CLIENT_IP') !== null)
		{
			return static::server('HTTP_X_CLUSTER_CLIENT_IP');
		}
		
		if (static::server('HTTP_X_FORWARDED_FOR') !== null)
		{
			return static::server('HTTP_X_FORWARDED_FOR');
		}
		
		if (static::server('HTTP_CLIENT_IP') !== null)
		{
			return static::server('HTTP_CLIENT_IP');
		}
		
		if (static::server('REMOTE_ADDR') !== null)
		{
			return static::server('REMOTE_ADDR');
		}
		
		// detection failed, return a dummy IP
		return '0.0.0.0';
	}

	/**
	 * Return's the protocol that the request was made with
	 *
	 * @return  string
	 */
	public static function protocol()
	{
		return (static::server('HTTPS') !== null and static::server('HTTPS') != 'off') ? 'https' : 'http';
	}

	/**
	 * Return's whether this is an AJAX request or not
	 *
	 * @return  bool
	 */
	public static function is_ajax()
	{
		return (static::server('HTTP_X_REQUESTED_WITH') !== null) and strtolower(static::server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}

	/**
	 * Return's the referrer
	 *
	 * @return  string
	 */
	public static function referrer()
	{
		return static::server('HTTP_REFERER', '');
	}

	/**
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 *
	 * @return  string
	 */
	public static function method()
	{
		return static::server('REQUEST_METHOD', 'GET');
	}

	/**
	 * Return's the user agent
	 *
	 * @return  string
	 */
	public static function user_agent()
	{
		return static::server('HTTP_USER_AGENT', '');
	}

	/**
	 * Fetch an item from the GET array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function get($index = null, $default = null)
	{
		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::_fetch_from_array($_GET, $index, $default);
	}

	/**
	 * Fetch an item from the POST array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function post($index = null, $default = null)
	{
		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::_fetch_from_array($_POST, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for put arguments
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function put($index = null, $default = null)
	{
		static $_PUT;

		if (static::method() !== 'PUT')
		{
			return null;
		}

		if ( ! isset($_PUT))
		{
			parse_str(file_get_contents('php://input'), $_PUT);
			! is_array($_PUT) and $_PUT = array();
		}

		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::_fetch_from_array($_PUT, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for delete arguments
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function delete($index = null, $default = null)
	{
		if (static::method() !== 'DELETE')
		{
			return null;
		}

		if ( ! isset($_DELETE))
		{
			static $_DELETE;
			parse_str(file_get_contents('php://input'), $_DELETE);
		}

		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::_fetch_from_array($_DELETE, $index, $default);
	}

	/**
	 * Fetch an item from either the GET array or the POST
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function get_post($index = null, $default = null)
	{
		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::post($index, null) === null
			? static::get($index, $default)
			: static::post($index, $default);
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return   string|array
	 */
	public static function cookie($index = null, $default = null)
	{
		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::_fetch_from_array($_COOKIE, $index, $default);
	}

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function server($index = null, $default = null)
	{
		// only return full array when called without args
		is_null($index) and func_num_args() > 0 and $index = '';

		return static::_fetch_from_array($_SERVER, ! is_null($index) ? strtoupper($index) : null, $default);
	}

	/**
	 * Retrieve values from global arrays
	 *
	 * @param   array   The array
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	private static function _fetch_from_array(&$array, $index, $default = null)
	{
		if (is_null($index))
		{
			return $array;
		}
		else
		{
			if (strpos($index, '.') !== false)
			{
				$parts = explode('.', $index);

				$return = false;
				foreach ($parts as $part)
				{
					if ($return === false and isset($array[$part]))
					{
						$return = $array[$part];
					}
					elseif (isset($return[$part]))
					{
						$return = $return[$part];
					}
					else
					{
						return ($default instanceof \Closure) ? $default() : $default;
					}
				}

				return $return;

			}
			elseif ( ! isset($array[$index]))
			{
				return ($default instanceof \Closure) ? $default() : $default;
			}

		}

		return $array[$index];
	}

}


