<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

// load PHP 5.6+ specific code
if (PHP_VERSION_ID >= 50600)
{
	include "base56.php";
}

/**
 * Check if we're running on a Windows platform
 */
if ( ! function_exists('is_windows'))
{
 	function is_windows()
 	{
 		return DIRECTORY_SEPARATOR === '/';
 	}
}

/**
 * Loads in a core class and optionally an app class override if it exists.
 *
 * @param   string  $path
 * @param   string  $folder
 * @return  bool
 */
if ( ! function_exists('import'))
{
	function import($path, $folder = 'classes')
	{
		// unify the path
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);

		foreach (array('.php', '.phar', '') as $ext)
		{
			foreach (array(COREPATH, APPPATH) as $loc)
			{
				// check if the file exist
				if (is_file($file = $loc.$folder.DIRECTORY_SEPARATOR.$path.$ext))
				{
					require_once $file;
					return true;
				}
			}
		}

		return false;
	}
}

/**
 * Shortcut for writing to the Log
 *
 * @param   int|string    the error level
 * @param   string        the error message
 * @param   string|array  message context information
 * @return  bool
 */
if ( ! function_exists('logger'))
{
	function logger($level, $msg, $context = null)
	{
		return \Log::write($level, $msg, $context);
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

		foreach ((array) $attr as $property => $value)
		{
			// Ignore null/false
			if ($value === null or $value === false)
			{
				continue;
			}

			// If the key is numeric then it must be something like selected="selected"
			if (is_numeric($property))
			{
				$property = $value;
			}

			$attr_str .= $property.'="'.str_replace('"', '&quot;', $value).'" ';
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
		// list of void elements (tags that can not have content)
		static $void_elements = array(
			// html4
			"area","base","br","col","hr","img","input","link","meta","param",
			// html5
			"command","embed","keygen","source","track","wbr",
			// html5.1
			"menuitem",
		);

		// construct the HTML
		$html = '<'.$tag;
		$html .= ( ! empty($attr)) ? ' '.(is_array($attr) ? array_to_attr($attr) : $attr) : '';

		// a void element?
		if (in_array(strtolower($tag), $void_elements))
		{
			// these can not have content
			$html .= ' />';
		}
		else
		{
			// add the content and close the tag
			$html .= '>'.$content.'</'.$tag.'>';
		}

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
	function __($string, $params = array(), $default = null, $language = null)
	{
		return \Lang::get($string, $params, $default, $language);
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
		return \Security::htmlentities($string);
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
 * Takes an associative array in the layout of parse_url, and constructs a URL from it
 *
 * see http://www.php.net/manual/en/function.http-build-url.php#96335
 *
 * @param   mixed   (Part(s) of) an URL in form of a string or associative array like parse_url() returns
 * @param   mixed   Same as the first argument
 * @param   int     A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
 * @param   array   If set, it will be filled with the parts of the composed url like parse_url() would return
 *
 * @return  string  constructed URL
 */
if (!function_exists('http_build_url'))
{
	define('HTTP_URL_REPLACE', 1);				// Replace every part of the first URL when there's one of the second URL
	define('HTTP_URL_JOIN_PATH', 2);			// Join relative paths
	define('HTTP_URL_JOIN_QUERY', 4);			// Join query strings
	define('HTTP_URL_STRIP_USER', 8);			// Strip any user authentication information
	define('HTTP_URL_STRIP_PASS', 16);			// Strip any password authentication information
	define('HTTP_URL_STRIP_AUTH', 32);			// Strip any authentication information
	define('HTTP_URL_STRIP_PORT', 64);			// Strip explicit port numbers
	define('HTTP_URL_STRIP_PATH', 128);			// Strip complete path
	define('HTTP_URL_STRIP_QUERY', 256);		// Strip query string
	define('HTTP_URL_STRIP_FRAGMENT', 512);		// Strip any fragments (#identifier)
	define('HTTP_URL_STRIP_ALL', 1024);			// Strip anything but scheme and host

	function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false)
	{
		$keys = array('user','pass','port','path','query','fragment');

		// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
		if ($flags & HTTP_URL_STRIP_ALL)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
			$flags |= HTTP_URL_STRIP_PORT;
			$flags |= HTTP_URL_STRIP_PATH;
			$flags |= HTTP_URL_STRIP_QUERY;
			$flags |= HTTP_URL_STRIP_FRAGMENT;
		}
		// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
		elseif ($flags & HTTP_URL_STRIP_AUTH)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
		}

		// parse the original URL
		$parse_url = is_array($url) ? $url : parse_url($url);

		// make sure we always have a scheme, host and path
		empty($parse_url['scheme']) and $parse_url['scheme'] = 'http';
		empty($parse_url['host']) and $parse_url['host'] = \Input::server('http_host');
		isset($parse_url['path']) or $parse_url['path'] = '';

		// make the path absolute if needed
		if ( ! empty($parse_url['path']) and substr($parse_url['path'], 0, 1) != '/')
		{
			$parse_url['path'] = '/'.$parse_url['path'];
		}

		// scheme and host are always replaced
		isset($parts['scheme']) and $parse_url['scheme'] = $parts['scheme'];
		isset($parts['host']) and $parse_url['host'] = $parts['host'];

		// replace the original URL with it's new parts (if applicable)
		if ($flags & HTTP_URL_REPLACE)
		{
			foreach ($keys as $key)
			{
				if (isset($parts[$key]))
					$parse_url[$key] = $parts[$key];
			}
		}
		else
		{
			// join the original URL path with the new path
			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
			{
				if (isset($parse_url['path']))
					$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
				else
					$parse_url['path'] = $parts['path'];
			}

			// join the original query string with the new query string
			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
			{
				if (isset($parse_url['query']))
					$parse_url['query'] .= '&' . $parts['query'];
				else
					$parse_url['query'] = $parts['query'];
			}
		}

		// strips all the applicable sections of the URL
		// note: scheme and host are never stripped
		foreach ($keys as $key)
		{
			if ($flags & (int) constant('HTTP_URL_STRIP_' . strtoupper($key)))
				unset($parse_url[$key]);
		}

		$new_url = $parse_url;

		return
			 ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
			.((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
			.((isset($parse_url['host'])) ? $parse_url['host'] : '')
			.((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
			.((isset($parse_url['path'])) ? $parse_url['path'] : '')
			.((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
			.((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
		;
	}
}

/**
 * Find the common "root" path of two given paths or FQFN's
 *
 * @param   array   array with the paths to compare
 *
 * @return  string  the determined common path section
 */
if ( ! function_exists('get_common_path'))
{
	function get_common_path($paths)
	{
		$lastOffset = 1;
		$common = '/';
		if ( ! empty($paths[0]))
		{
			while (($index = strpos($paths[0], '/', $lastOffset)) !== false)
			{
				$dirLen = $index - $lastOffset + 1;	// include /
				$dir = substr($paths[0], $lastOffset, $dirLen);
				foreach ($paths as $path)
				{
					if (substr($path, $lastOffset, $dirLen) != $dir)
					{
						return $common;
					}
				}
				$common .= $dir;
				$lastOffset = $index + 1;
			}
		}
		return $common;
	}
}

/**
 * Faster equivalent of call_user_func_array
 */
if ( ! function_exists('call_fuel_func_array'))
{
	function call_fuel_func_array($callback, array $args)
	{
		// deal with "class::method" syntax
		if (is_string($callback) and strpos($callback, '::') !== false)
		{
			$callback = explode('::', $callback);
		}

		// if an array is passed, extract the object and method to call
		if (is_array($callback) and isset($callback[1]) and is_object($callback[0]))
		{
			// make sure our arguments array is indexed
			if ($count = count($args))
			{
				$args = array_values($args);
			}

			list($instance, $method) = $callback;

			// calling the method directly is faster then call_user_func_array() !
			switch ($count)
			{
				case 0:
					return $instance->$method();

				case 1:
					return $instance->$method($args[0]);

				case 2:
					return $instance->$method($args[0], $args[1]);

				case 3:
					return $instance->$method($args[0], $args[1], $args[2]);

				case 4:
					return $instance->$method($args[0], $args[1], $args[2], $args[3]);
			}
		}

		elseif (is_array($callback) and isset($callback[1]) and is_string($callback[0]))
		{
			list($class, $method) = $callback;
			$class = '\\'.ltrim($class, '\\');

			// calling the method directly is faster then call_user_func_array() !
			switch (count($args))
			{
				case 0:
					return $class::$method();

				case 1:
					return $class::$method($args[0]);

				case 2:
					return $class::$method($args[0], $args[1]);

				case 3:
					return $class::$method($args[0], $args[1], $args[2]);

				case 4:
					return $class::$method($args[0], $args[1], $args[2], $args[3]);
			}
		}

		// if it's a string, it's a native function or a static method call
		elseif (is_string($callback) or $callback instanceOf \Closure)
		{
			is_string($callback) and $callback = ltrim($callback, '\\');

			// calling the method directly is faster then call_user_func_array() !
			switch (count($args))
			{
				case 0:
					return $callback();

				case 1:
					return $callback($args[0]);

				case 2:
					return $callback($args[0], $args[1]);

				case 3:
					return $callback($args[0], $args[1], $args[2]);

				case 4:
					return $callback($args[0], $args[1], $args[2], $args[3]);
			}
		}

		// fallback, handle the old way
		return call_user_func_array($callback, $args);
	}
}

/**
 * hash_pbkdf2() implementation for PHP < 5.5.0
 */
if ( ! function_exists('hash_pbkdf2'))
{
    /* PBKDF2 Implementation (described in RFC 2898)
     *
     *  @param string a   hash algorithm to use
     *  @param string p   password
     *  @param string s   salt
     *  @param int    c   iteration count (use 1000 or higher)
     *  @param int    kl  derived key length
     *  @param bool   r   when set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
     *
     *  @return string derived key
     */
    function hash_pbkdf2($a, $p, $s, $c, $kl = 0, $r = false)
    {
        $hl = strlen(hash($a, null, true)); # Hash length
        $kb = ceil($kl / $hl);              # Key blocks to compute
        $dk = '';                           # Derived key

        # Create key
        for ( $block = 1; $block <= $kb; $block ++ )
        {
            # Initial hash for this block
            $ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);

            # Perform block iterations
            for ( $i = 1; $i < $c; $i ++ )
            {
                # XOR each iterate
                $ib ^= ($b = hash_hmac($a, $b, $p, true));
            }
            $dk .= $ib; # Append iterated block
        }

        # Return derived key of correct length
		return substr($r ? $dk : bin2hex($dk), 0, $kl);
	}
}
