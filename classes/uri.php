<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2015 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Uri Class
 *
 * @package   Fuel
 * @category  Core
 * @author    Dan Horrigan
 * @link      http://docs.fuelphp.com/classes/uri.html
 */
class Uri
{
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
	 * Replace all * wildcards in a URI by the current segment in that location
	 *
	 * @param  string  $url     The url containing the wildcards
	 * @param  bool    $secure  To force a particular HTTP scheme
	 * @return  string
	 */
	public static function segment_replace($url, $secure = null)
	{
		// get the path from the url
		$parts = parse_url($url);

		// explode it in it's segments
		$segments = explode('/', trim($parts['path'], '/'));

		// fetch any segments needed
		$wildcards = 0;
		foreach ($segments as $index => &$segment)
		{
			if (strpos($segment, '*') !== false)
			{
				$wildcards++;
				if (($new = static::segment($index+1)) === null)
				{
					throw new \OutofBoundsException('Segment replace on "'.$url.'" failed. No segment exists for wildcard '.$wildcards.'.');
				}
				$segment = str_replace('*', $new, $segment);
			}
		}

		// re-assemble the path
		$parts['path'] = '/'.implode('/', $segments);

		// do we need to force a scheme?
		if (is_bool($secure))
		{
			$parts['scheme'] = $secure ? 'https' : 'http';
		}

		// and rebuild the url with the new path
		if (empty($parts['host']))
		{
			// if a relative url was given, fake a host so we can remove it after building
			$url = substr(http_build_url('http://__removethis__/', $parts), 22);
		}
		else
		{
			// a hostname was present, just rebuild it
			$url = http_build_url('', $parts);
		}

		// return the newly constructed url
		return $url;
	}

	/**
	 * Converts the current URI segments to an associative array.  If
	 * the URI has an odd number of segments, an empty value will be added.
	 *
	 * @param  int  segment number to start from. default value is the first segment
	 * @return  array  the assoc array
	 */
	public static function to_assoc($start = 1)
	{
		$segments = array_slice(static::segments(), ($start - 1));
		count($segments) % 2 and $segments[] = null;

		return \Arr::to_assoc($segments);
	}

	/**
	 * Returns the full uri as a string
	 *
	 * @return  string
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
	 * @param   bool    $secure         If false, force http. If true, force https
	 * @return  string
	 */
	public static function create($uri = null, $variables = array(), $get_variables = array(), $secure = null)
	{
		$url = '';
		is_null($uri) and $uri = static::string();

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

		// stick a url suffix onto it if defined and needed
		if ($url_suffix = \Config::get('url_suffix', false) and substr($url, -1) != '/')
		{
			$current_suffix = strrchr($url, '.');
			if ( ! $current_suffix or strpos($current_suffix, '/') !== false)
			{
				$url .= $url_suffix;
			}
		}

		if ( ! empty($get_variables))
		{
			$char = strpos($url, '?') === false ? '?' : '&';
			if (is_string($get_variables))
			{
				$url .= $char.str_replace('%3A', ':', $get_variables);
			}
			else
			{
				$url .= $char.str_replace('%3A', ':', http_build_query($get_variables));
			}
		}

		array_walk(
			$variables,
			function ($val, $key) use (&$url)
			{
				$url = str_replace(':'.$key, $val, $url);
			}
		);

		is_bool($secure) and $url = http_build_url($url, array('scheme' => $secure ? 'https' : 'http'));

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
	 * Builds a query string by merging all array and string values passed. If
	 * a string is passed, it will be assumed to be a switch, and converted
	 * to "string=1".
	 *
	 * @param array|string Array or string to merge
	 * @param array|string ...
	 *
	 * @return string
	 */
	public static function build_query_string()
	{
		$params = array();

		foreach (func_get_args() as $arg)
		{
			$arg = is_array($arg) ? $arg : array($arg => '1');

			$params = array_merge($params, $arg);
		}

		return http_build_query($params);
	}

	/**
	 * Updates the query string of the current or passed URL with the data passed
	 *
	 * @param  array|string  $vars    Assoc array of GET variables, or a get variable name
	 * @param  string|mixed  $uri     Optional URI to use if $vars is an array, otherwise the get variable name
	 * @param  bool          $secure  If false, force http. If true, force https
	 *
	 * @return string
	 */
	public static function update_query_string($vars = array(), $uri = null, $secure = null)
	{
		// unify the input data
		if ( ! is_array($vars))
		{
			$vars = array($vars => $uri);
			$uri = null;
		}

		// if we have a custom URI, use that
		if ($uri === null)
		{
			// use the current URI if not is passed
			$uri = static::current();

			// merge them with the existing query string data
			$vars = array_merge(\Input::get(), $vars);
		}

		// return the updated uri
		return static::create($uri, array(), $vars, $secure);
	}

	/**
	 * @var  string  The URI string
	 */
	protected $uri = '';

	/**
	 * @var  array  The URI segments
	 */
	protected $segments = '';

	/**
	 * Construct takes a URI or detects it if none is given and generates
	 * the segments.
	 *
	 * @param   string  The URI
	 * @return  void
	 */
	public function __construct($uri = null)
	{
		if (\Fuel::$profiling)
		{
			\Profiler::mark(__METHOD__.' Start');
		}

		// if the route is a closure, an object will be passed here
		is_object($uri) and $uri = null;

		$this->uri = trim($uri ?: \Input::uri(), '/');

		if (empty($this->uri))
		{
			$this->segments = array();
		}
		else
		{
			$this->segments = explode('/', $this->uri);
		}

		if (\Fuel::$profiling)
		{
			\Profiler::mark(__METHOD__.' End');
		}
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

		return \Fuel::value($default);
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
