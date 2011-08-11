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
 * Uri Class
 *
 * @package   Fuel
 * @category  Core
 * @author    Dan Horrigan
 * @link      http://fuelphp.com/docs/classes/uri.html
 */
class Uri {

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
	public static function detect()
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
		static::$detected_uri = str_replace(array('//', '../'), '/', $uri);

		return static::$detected_uri;
	}

	/**
	 * Returns the desired segment, or $default if it does not exist.
	 *
	 * @param   int     $segment  The segment number (1-based index)
	 * @param   mixed   $default  Default value to return
	 * @return  string
	 */
	public static function segment($segment, $default = null)
	{
		if ($request = \Request::active())
		{
			return $request->uri->get_segment($segment, $default);
		}
		
		return null;
	}

	/**
	 * Returns all segments in an array
	 *
	 * @return  array
	 */
	public static function segments()
	{
		if ($request = \Request::active())
		{
			return $request->uri->get_segments();
		}
		
		return null;
	}

	/**
	 * Converts the current URI segments to an associative array.  If
	 * the URI has an odd number of segments, null will be returned.
	 *
	 * @return  array|null  the array or null
	 */
	public static function to_assoc()
	{
		return \Arr::to_assoc(static::segments());
	}

	/**
	 * Returns the full uri as a string
	 *
	 * @return	string
	 */
	public static function string()
	{
		if ($request = \Request::active())
		{
			return $request->uri->get();
		}
		
		return null;
	}

	/**
	 * Creates a url with the given uri, including the base url
	 *
	 * @param   string  $uri            The uri to create the URL for
	 * @param   array   $variables      Some variables for the URL
	 * @param   array   $get_variables  Any GET urls to append via a query string
	 * @return  string
	 */
	public static function create($uri = null, $variables = array(), $get_variables = array())
	{
		$url = '';
		$uri = $uri ?: static::string();

		// If the given uri is not a full URL
		if( ! preg_match("#^(http|https|ftp)://#i", $uri))
		{
			$url .= \Config::get('base_url');

			if ($index_file = \Config::get('index_file'))
			{
				$url .= $index_file.'/';
			}
		}
		$url .= ltrim($uri, '/');

		substr($url, -1) != '/' and $url .= \Config::get('url_suffix');

		if ( ! empty($get_variables))
		{
			$char = strpos($url, '?') === false ? '?' : '&';
			$url .= $char.http_build_query($get_variables);
		}

		array_walk($variables, function ($val, $key) use (&$url) {
			$url = str_replace(':'.$key, $val, $url);
		});

		return $url;
	}

	/**
	 * Gets the main request's URI
	 *
	 * @return  string
	 */
	public static function main()
	{
		return static::create(\Request::main()->uri->get());
	}

	/**
	 * Gets the current URL, including the BASE_URL
	 *
	 * @return  string
	 */
	public static function current()
	{
		return static::create();
	}

	/**
	 * Gets the base URL, including the index_file if wanted.
	 *
	 * @param   bool    $include_index  Whether to include index.php in the URL
	 * @return  string
	 */
	public static function base($include_index = true)
	{
		$url = \Config::get('base_url');

		if ($include_index and \Config::get('index_file'))
		{
			$url .= \Config::get('index_file').'/';
		}

		return $url;
	}


	/**
	 * @deprecated  Make protected in 1.2
	 * @var  string  The URI string
	 */
	public $uri = '';

	/**
	 * @deprecated  Make protected in 1.2
	 * @var  array  The URI segments
	 */
	public $segments = '';

	/**
	 * Construct takes a URI or detects it if none is given and generates
	 * the segments.
	 *
	 * @param   string  The URI
	 * @return  void
	 */
	public function __construct($uri = NULL)
	{
		$uri = $uri ?: $uri = static::detect();
		$this->uri = \Security::clean_uri(trim($uri, '/'));
		$this->segments = explode('/', $this->uri);
	}

	/**
	 * Returns the full URI string
	 *
	 * @return  string  The URI string
	 */
	public function get()
	{
		return $this->uri;
	}

	/**
	 * Returns all of the URI segments
	 *
	 * @return  array  The URI segments
	 */
	public function get_segments()
	{
		return $this->segments;
	}

	/**
	 * Get the specified URI segment, return default if it doesn't exist.
	 * 
	 * Segment index is 1 based, not 0 based
	 *
	 * @param   string  $segment  The 1-based segment index
	 * @param   mixed   $default  The default value
	 * @return  mixed
	 */
	public function get_segment($segment, $default = null)
	{
		if (isset($this->segments[$segment - 1]))
		{
			return $this->segments[$segment - 1];
		}

		return ($default instanceof \Closure) ? $default() : $default;
	}

	/**
	 * Returns the URI string
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->get();
	}
}
