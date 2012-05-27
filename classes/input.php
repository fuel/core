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
	 * @var  $input  All of the input (GET, POST, PUT, DELETE)
	 */
	protected static $input = null;

	/**
	 * @var  $put_delete  All of the put or delete vars
	 */
	protected static $put_delete = null;

	/**
	 * @var  $content  parsed request body (xml/json)
	 */
	protected static $content = null;

	/**
	 * Get the request body interpreted as JSON.
	 *
	 * @return  array  parsed request body content.
	 */
	public static function json($index = null, $default = null)
	{
		if (static::$content === null)
		{
			static::hydrate_raw_input('json');
		}

		$json = ($request = \Request::current()) ? $request->get('json') : false;

		$request === false and $json =& static::$content;

		return (func_num_args() === 0) ? $json : \Arr::get($json, $index, $default);
	}

	/**
	 * Get the request body interpreted as XML.
	 *
	 * @return  array  parsed request body content.
	 */
	public static function xml($index = null, $default = null)
	{
		if (static::$content === null)
		{
			static::hydrate_raw_input('xml');
		}

		$xml = ($request = \Request::current()) ? $request->get('xml') : false;

		$request === false and $xml =& static::$content;

		return (func_num_args() === 0) ? $xml : \Arr::get($xml, $index, $default);
	}

	/**
	 * Hydration from raw request (xml/json requests)
	 *
	 * @param  string  $type  input type
	 */
	protected static function hydrate_raw_input($type)
	{
		$content = \Format::forge(file_get_contents('php://input'), $type)->to_array();
		is_array($content) and static::$content = \Security::clean($content);
	}

	/**
	 * Detects and returns the current URI based on a number of different server
	 * variables.
	 *
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
				$uri = $_SERVER['REQUEST_URI'];
			}
			else
			{
				throw new \FuelException('Unable to detect the URI.');
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

			// When index.php? is used and the config is set wrong, lets just
			// be nice and help them out.
			if ($index_file and strncmp($uri, '?/', 2) === 0)
			{
				$uri = substr($uri, 1);
			}

			// Lets split the URI up in case it contains a ?.  This would
			// indicate the server requires 'index.php?' and that mod_rewrite
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
		$uri_info = pathinfo($uri);
		if ( ! empty($uri_info['extension']))
		{
			static::$detected_ext = $uri_info['extension'];
			$uri = $uri_info['dirname'].'/'.$uri_info['filename'];
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
	 * @return  string
	 */
	public static function ip($default = '0.0.0.0')
	{
		return static::server('REMOTE_ADDR', $default);
	}

	/**
	 * Get the real ip address of the user.  Even if they are using a proxy.
	 *
	 * @param	string	the default to return on failure
	 * @param	bool	exclude private and reserved IPs
	 * @return  string  the real ip address of the user
	 */
	public static function real_ip($default = '0.0.0.0', $exclude_reserved = false)
	{
		$server_keys = array('HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

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

			if ($exclude_reserved)
			{
				$ips = array_filter($ips, function($ip) {
					return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
				});
			}

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
		if ((static::server('HTTPS') !== null and static::server('HTTPS') != 'off')
			or (static::server('HTTPS') === null and static::server('SERVER_PORT') == 443))
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
	 * @return  string
	 */
	public static function referrer($default = '')
	{
		return static::server('HTTP_REFERER', $default);
	}

	/**
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 *
	 * @return  string
	 */
	public static function method($default = 'GET')
	{
		// get the method from the current active request
		if ($request = \Request::active())
		{
			return $request->get_method();
		}

		// if called before a request is active, fall back to the global server setting
		return static::server('HTTP_X_HTTP_METHOD_OVERRIDE', static::server('REQUEST_METHOD', $default));
	}

	/**
	 * Return's the user agent
	 *
	 * @return  string
	 */
	public static function user_agent($default = '')
	{
		return static::server('HTTP_USER_AGENT', $default);
	}

	/**
	 * Returns all of the GET, POST, PUT and DELETE variables from the main request
	 *
	 * @return  array
	 */
	public static function all()
	{
		if (static::$input === null)
		{
			static::hydrate();
		}

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
		$get = ($request = \Request::active()) ? $request->get('get') : false;
		
		$get === false and $get =& $_GET;

		return (func_num_args() === 0) ? $get : \Arr::get($get, $index, $default);
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
		$post = ($request = \Request::active()) ? $request->get('post') : false;
		
		$post === false and $post =& $_POST;

		return (func_num_args() === 0) ? $post : \Arr::get($post, $index, $default);
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
		if (static::$put_delete === null)
		{
			static::hydrate();
		}

		$put = ($request = \Request::active()) ? $request->get('put') : false;
		
		$put === false and $put =& static::$put_delete;

		return (func_num_args() === 0) ? $put : \Arr::get($put, $index, $default);
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
		if (static::$put_delete === null)
		{
			static::hydrate();
		}

		$delete = ($request = \Request::active()) ? $request->get('delete') : false;
		
		$delete === false and $delete =& static::$put_delete;

		return (is_null($index) and func_num_args() === 0) ? $delete : \Arr::get($delete, $index, $default);
	}

	/**
	 * Fetch an item from the FILE array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function file($index = null, $default = null)
	{
		$files = ($request = \Request::active()) ? $request->get('files') : false;
		
		$files === false and $files =& $_FILES;

		return (func_num_args() === 0) ? $files : \Arr::get($files, $index, $default);
	}

	/**
	 * Fetch an item from either the main request's GET, POST, PUT or DELETE array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function param($index = null, $default = null)
	{
		if (static::$input === null)
		{
			static::hydrate();
		}

		return \Arr::get(static::$input, $index, $default);
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param    string  The index key
	 * @param    mixed   The default value
	 * @return   string|array
	 */
	public static function cookie($index = null, $default = null)
	{
		$cookie = ($request = \Request::active()) ? $request->get('cookie') : false;
		
		$cookie === false and $cookie =& $_COOKIE;

		return (func_num_args() === 0) ? $cookie : \Arr::get($cookie, $index, $default);
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
		$server = ($request = \Request::active()) ? $request->get('server') : false;
		
		$server === false and $server =& $_SERVER;

		return (func_num_args() === 0) ? $server : \Arr::get($server, strtoupper($index), $default);
	}

	/**
	 * Hydrates the input array
	 *
	 * @return  void
	 */
	protected static function hydrate()
	{
		static::$input = array_merge($_GET, $_POST);

		if (\Input::method() == 'PUT' or \Input::method() == 'DELETE')
		{
			parse_str(file_get_contents('php://input'), static::$put_delete);
			static::$input = array_merge(static::$input, static::$put_delete);
		}
	}
}
