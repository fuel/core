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
	 * @var  $detected_uri  The URI that was detected automatically
	 */
	protected static $detected_uri = null;

	/**
	 * @var  $detected_ext  The URI extension that was detected automatically
	 */
	protected static $detected_ext = null;

	/**
	 * @var  array  $input  All of the input (GET, POST, PUT, DELETE, PATCH)
	 */
	protected static $input = null;

	/**
	 * @var  array  $put_patch_delete  All of the put or delete vars
	 */
	protected static $put_patch_delete = null;

	/**
	 * @var  $php_input  Cache for the php://input stream
	 */
	protected static $php_input = null;

	/**
	 * @var  $json  parsed request body as json
	 */
	protected static $json = null;

	/**
	 * @var  $xml  parsed request body as xml
	 */
	protected static $xml = null;

	/**
	 * Get the request body interpreted as JSON.
	 *
	 * @param   mixed  $index
	 * @param   mixed  $default
	 * @return  array  parsed request body content.
	 */
	public static function json($index = null, $default = null)
	{
		static::$json === null and static::hydrate_raw_input('json');
		return (func_num_args() === 0) ? static::$json : \Arr::get(static::$json, $index, $default);
	}

	/**
	 * Get the request body interpreted as XML.
	 *
	 * @param   mixed  $index
	 * @param   mixed  $default
	 * @return  array  parsed request body content.
	 */
	public static function xml($index = null, $default = null)
	{
		static::$xml === null and static::hydrate_raw_input('xml');
		return (func_num_args() === 0) ? static::$xml : \Arr::get(static::$xml, $index, $default);
	}

	/**
	 * Hydration from raw request (xml/json requests)
	 *
	 * @param  string  $type  input type
	 */
	protected static function hydrate_raw_input($type)
	{
		static::$php_input === null and static::$php_input = file_get_contents('php://input');
		static::$$type = \Security::clean(\Format::forge(static::$php_input, $type)->to_array());
	}

	/**
	 * Detects and returns the current URI based on a number of different server
	 * variables.
	 *
	 * @throws \FuelException
	 * @return  string
	 */
	public static function uri()
	{
		if (static::$detected_uri !== null)
		{
			return static::$detected_uri;
		}

		if (\Fuel::$is_cli)
		{
			if (($uri = \Cli::option('uri')) !== null)
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
				$uri = strpos($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI']) !== 0 ? $_SERVER['REQUEST_URI'] : '';
			}
			else
			{
				throw new \FuelException('Unable to detect the URI.');
			}

			// Remove the base URL from the URI
			$base_url = parse_url(\Config::get('base_url'), PHP_URL_PATH);
			if ($uri != '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
			{
				$uri = substr($uri, strlen($base_url) - 1);
			}

			// If we are using an index file (not mod_rewrite) then remove it
			$index_file = \Config::get('index_file');
			if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
			{
				$uri = substr($uri, strlen($index_file));
			}

			// When index.php? is used and the config is set wrong, lets just
			// be nice and help them out.
			if ($index_file and strncmp($uri, '?/', 2) === 0)
			{
				$uri = substr($uri, 1);
			}

			// decode the uri, and put any + back (does not mean a space in the url path)
			$uri = str_replace("\r", '+', urldecode(str_replace('+', "\r", $uri)));

			// Lets split the URI up in case it contains a ?.  This would
			// indicate the server requires 'index.php?' and that mod_rewrite
			// is not being used.
			preg_match('#(.*?)\?(.*)#i', $uri, $matches);

			// If there are matches then lets set set everything correctly
			if ( ! empty($matches))
			{
				$uri = $matches[1];

				// only reconstruct $_GET if we didn't have a query string
				if (empty($_SERVER['QUERY_STRING']))
				{
					$_SERVER['QUERY_STRING'] = $matches[2];
					parse_str($matches[2], $_GET);
					$_GET = \Security::clean($_GET);
				}
			}
		}

		// Deal with any trailing dots
		$uri = rtrim($uri, '.');

		// Do we have a URI and does it not end on a slash?
		if ($uri and substr($uri, -1) !== '/')
		{
			// Strip the defined url suffix from the uri if needed
			$ext = strrchr($uri, '.');
			$path = $ext === false ? $uri : substr($uri, 0, -strlen($ext));

			// Did we detect something that looks like an extension?
			if ( ! empty($ext))
			{
				// if it has a slash in it, it's a URI segment with a dot in it
				if (strpos($ext, '/') === false)
				{
					static::$detected_ext = ltrim($ext, '.');

					if (\Config::get('routing.strip_extension', true))
					{
						$uri = $path;
					}
				}
			}
		}

		// Do some final clean up of the uri
		static::$detected_uri = \Security::clean_uri($uri, true);

		return static::$detected_uri;
	}

	/**
	 * Detects and returns the current URI extension
	 *
	 * @return  string
	 */
	public static function extension()
	{
		static::$detected_ext === null and static::uri();

		return static::$detected_ext;
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
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 *
	 * @param   string $default
	 * @return  string
	 */
	public static function method($default = 'GET')
	{
		// get the method from the current active request
		if ($request = \Request::active() and $method = $request->get_method())
		{
			return $method;
		}

		// if called before a request is active, fall back to the global server setting
		if (\Config::get('security.allow_x_headers', false))
		{
			return static::server('HTTP_X_HTTP_METHOD_OVERRIDE', static::server('REQUEST_METHOD', $default));
		}
		else
		{
			return static::server('REQUEST_METHOD', $default);
		}
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
	 * Returns all of the GET, POST, PUT and DELETE variables.
	 *
	 * @return  array
	 */
	public static function all()
	{
		static::$input === null and static::hydrate();
		return static::$input;
	}

	/**
	 * Gets the specified GET variable.
	 *
	 * @param   string  $index    The index to get
	 * @param   string  $default  The default value
	 * @return  string|array
	 */
	public static function get($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_GET : \Arr::get($_GET, $index, $default);
	}

	/**
	 * Fetch an item from the POST array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function post($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_POST : \Arr::get($_POST, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for put arguments
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function put($index = null, $default = null)
	{
		static::$put_patch_delete === null and static::hydrate();
		return (func_num_args() === 0) ? static::$put_patch_delete : \Arr::get(static::$put_patch_delete, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for patch arguments
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function patch($index = null, $default = null)
	{
		static::$put_patch_delete === null and static::hydrate();
		return (func_num_args() === 0) ? static::$put_patch_delete : \Arr::get(static::$put_patch_delete, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for delete arguments
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function delete($index = null, $default = null)
	{
		static::$put_patch_delete === null and static::hydrate();
		return (is_null($index) and func_num_args() === 0) ? static::$put_patch_delete : \Arr::get(static::$put_patch_delete, $index, $default);
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
	 * Fetch an item from either the GET, POST, PUT, PATCH or DELETE array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public static function param($index = null, $default = null)
	{
		static::$input === null and static::hydrate();
		return \Arr::get(static::$input, $index, $default);
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
	 * Hydrates the input array
	 *
	 * @return  void
	 */
	protected static function hydrate()
	{
		static::$input = array_merge($_GET, $_POST);

		if (static::method() == 'PUT' or static::method() == 'PATCH' or static::method() == 'DELETE')
		{
			static::$php_input === null and static::$php_input = file_get_contents('php://input');
			if (strpos(static::headers('Content-Type'), 'www-form-urlencoded') > 0 and \Config::get('security.form-double-urlencoded', false))
			{
				static::$php_input = urldecode(static::$php_input);
			}
			parse_str(static::$php_input, static::$put_patch_delete);
			static::$input = array_merge(static::$input, static::$put_patch_delete);
		}
		else
		{
			static::$put_patch_delete = array();
		}
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
