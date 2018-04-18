<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Input class
 *
 * The input class allows you to access HTTP parameters, load server variables
 * and user agent details.
 *
 * @package   Fuel
 * @category  Core
 * @link      http://docs.fuelphp.com/classes/input.html
 */
class Input
{
	/**
	 * global static input instance
	 */
	protected static $instance;

	/**
	 * Forge a new instance
	 *
	 * @param  $new     Request         New request instance this input instance is tied to
	 * @param  $active  Input_Instance  Currently active input instance
	 *
	 * @return Input_Instance
	 */
	public static function forge(Request $new = null, Input_Instance $input = null)
	{
		if ($new)
		{
			return new \Input_Instance($new, $input);
		}

		if ( ! static::$instance)
		{
			static::$instance = new \Input_Instance();
		}

		return static::$instance;
	}

	/**
	 * Return the current input instance
	 *
	 * @return  Input_Instance
	 */
	public static function instance()
	{
		if ($request = \Request::active())
		{
			return $request->input();
		}

		return static::forge();
	}

	/**
	 * Static calls to the current input instance
	 */
	public static function __callStatic($method, $arguments)
	{
		return call_fuel_func_array(array(static::instance(), $method), $arguments);
	}

	/**
	 * Get the public ip address of the user.
	 *
	 * @param   string $default
	 * @return  array|string
	 */
	public static function ip($default = '0.0.0.0')
	{
		return static::server('REMOTE_ADDR', $default);
	}

	/**
	 * Get the real ip address of the user.  Even if they are using a proxy.
	 *
	 * @param	string	$default           the default to return on failure
	 * @param	bool	$exclude_reserved  exclude private and reserved IPs
	 * @return  string  the real ip address of the user
	 */
	public static function real_ip($default = '0.0.0.0', $exclude_reserved = false)
	{
		static $server_keys = null;

		if (empty($server_keys))
		{
			$server_keys = array('HTTP_CLIENT_IP', 'REMOTE_ADDR');
			if (\Config::get('security.allow_x_headers', false))
			{
				$server_keys = array_merge(array('HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_FORWARDED_FOR'), $server_keys);
			}
		}

		foreach ($server_keys as $key)
		{
			if ( ! static::server($key))
			{
				continue;
			}

			$ips = explode(',', static::server($key));
			array_walk($ips, function (&$ip) {
				$ip = trim($ip);
			});

			$ips = array_filter($ips, function($ip) use($exclude_reserved) {
				return filter_var($ip, FILTER_VALIDATE_IP, $exclude_reserved ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : null);
			});

			if ($ips)
			{
				return reset($ips);
			}
		}

		return \Fuel::value($default);
	}

	/**
	 * Return's the protocol that the request was made with
	 *
	 * @return  string
	 */
	public static function protocol()
	{
		if (static::server('HTTPS') == 'on' or
			static::server('HTTPS') == 1 or
			static::server('SERVER_PORT') == 443 or
			(\Config::get('security.allow_x_headers', false) and static::server('HTTP_X_FORWARDED_PROTO') == 'https') or
			(\Config::get('security.allow_x_headers', false) and static::server('HTTP_X_FORWARDED_PORT') == 443))
		{
			return 'https';
		}

		return 'http';
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
	 * @param   string $default
	 * @return  string
	 */
	public static function referrer($default = '')
	{
		return static::server('HTTP_REFERER', $default);
	}

	/**
	 * Return's the user agent
	 *
	 * @param   $default
	 * @return  string
	 */
	public static function user_agent($default = '')
	{
		return static::server('HTTP_USER_AGENT', $default);
	}

	/**
	 * Fetch an item from the FILE array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function file($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_FILES : \Arr::get($_FILES, $index, $default);
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param    string  $index    The index key
	 * @param    mixed   $default  The default value
	 * @return   string|array
	 */
	public static function cookie($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_COOKIE : \Arr::get($_COOKIE, $index, $default);
	}

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function server($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_SERVER : \Arr::get($_SERVER, strtoupper($index), $default);
	}

	/**
	 * Fetch a item from the HTTP request headers
	 *
	 * @param   mixed $index
	 * @param   mixed $default
	 * @return  array
	 */
	public static function headers($index = null, $default = null)
	{
		static $headers = null;

		// do we need to fetch the headers?
		if ($headers === null)
		{
			// deal with fcgi or nginx installs
			if ( ! function_exists('getallheaders'))
			{
				$server = \Arr::filter_prefixed(static::server(), 'HTTP_', true);

				foreach ($server as $key => $value)
				{
					$key = join('-', array_map('ucfirst', explode('_', strtolower($key))));

					$headers[$key] = $value;
				}

				$value = static::server('Content_Type', static::server('Content-Type')) and $headers['Content-Type'] = $value;
				$value = static::server('Content_Length', static::server('Content-Length')) and $headers['Content-Length'] = $value;
			}
			else
			{
				$headers = getallheaders();
			}
		}

		return empty($headers) ? $default : ((func_num_args() === 0) ? $headers : \Arr::get(array_change_key_case($headers), strtolower($index), $default));
	}

	/**
	 * Return's the query string
	 *
	 * @param   string $default
	 * @return  string
	 */
	public static function query_string($default = '')
	{
		return static::server('QUERY_STRING', $default);
	}
}
