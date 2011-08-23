<?php
/**
 * Fuel
 *
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
 * Identifies the platform, browser, robot, or mobile device from the user agent string
 *
 * This class uses PHP's get_browser() to get details from the browsers user agent
 * string. If not available, it can use a coded alternative using the php_browscap.ini
 * file from http://browsers.garykeith.com.
 *
 * @package	    Fuel
 * @subpackage  Core
 * @category    Core
 * @author      Harro Verton
 */

class Agent {

	/**
	 * @var  array  information about the current browser
	 */
	protected static $properties = array(
		'Browser'             => 'unknown',
		'Version'             => 0,
		'MajorVer'            => 0,
		'MinorVer'            => 0,
		'Platform'            => 'unknown',
		'Alpha'               => false,
		'Beta'                => false,
		'Win16'               => false,
		'Win32'               => false,
		'Win64'               => false,
		'Frames'              => false,
		'IFrames'             => false,
		'Tables'              => false,
		'Cookies'             => false,
		'BackgroundSounds'    => false,
		'JavaScript'          => false,
		'VBScript'            => false,
		'JavaApplets'         => false,
		'ActiveXControls'     => false,
		'isBanned'            => false,
		'isMobile'            => false,
		'isSyndicationReader' => false,
		'Crawler'             => false,
		'CssVersion'          => 0,
		'AolVersion'          => 0,
	);

	/**
	 * @var  array  property to cache key mapping
	 */
	protected static $keys = array(
		'Browser'             => 'A',
		'Version'             => 'B',
		'MajorVer'            => 'C',
		'MinorVer'            => 'D',
		'Platform'            => 'E',
		'Alpha'               => 'F',
		'Beta'                => 'G',
		'Win16'               => 'H',
		'Win32'               => 'I',
		'Win64'               => 'J',
		'Frames'              => 'K',
		'IFrames'             => 'L',
		'Tables'              => 'M',
		'Cookies'             => 'N',
		'BackgroundSounds'    => 'O',
		'JavaScript'          => 'P',
		'VBScript'            => 'Q',
		'JavaApplets'         => 'R',
		'ActiveXControls'     => 'S',
		'isBanned'            => 'T',
		'isMobile'            => 'U',
		'isSyndicationReader' => 'V',
		'Crawler'             => 'W',
		'CssVersion'          => 'X',
		'AolVersion'          => 'Y',
	);

	/**
	 * @var	array	global config defaults
	 */
	protected static $defaults = array(
		'browscap' => array(
			'enabled' => true,
			'url' => 'http://browsers.garykeith.com/stream.asp?BrowsCapINI',
			'method' => 'wrapper',
			'file' => '',
		),
		'cache' => array(
			'driver' => '',
			'expiry' => 604800,
			'identifier' => 'fuel.agent',
		),
	);

	/**
	 * @var	array	global config items
	 */
	protected static $config = array(
	);

	/**
	 * @var	string	detected user agent string
	 */
	protected static $user_agent = '';

	// --------------------------------------------------------------------
	// public static methods
	// --------------------------------------------------------------------

	/**
	 * map the user agent string to browser specifications
	 *
	 * @return void
	 */
	public static function _init()
	{
		// fetch and store the user agent
		static::$user_agent = \Input::server('http_user_agent', '');

		// fetch and process the configuration
		\Config::load('agent', true);

		static::$config = array_merge(static::$defaults, \Config::get('agent', array()));

		// validate the browscap configuration
		if ( ! is_array(static::$config['browscap']))
		{
			static::$config['browscap'] = static::$defaults['browscap'];
		}
		else
		{
			if ( ! array_key_exists('enabled', static::$config['browscap']) or ! is_bool(static::$config['browscap']['enabled']))
			{
				static::$config['browscap']['enabled'] = true;
			}

			if ( ! array_key_exists('url', static::$config['browscap']) or ! is_string(static::$config['browscap']['url']))
			{
				static::$config['browscap']['url'] = static::$defaults['browscap']['url'];
			}

			if ( ! array_key_exists('file', static::$config['browscap']) or ! is_string(static::$config['browscap']['file']))
			{
				static::$config['browscap']['file'] = static::$defaults['browscap']['file'];
			}

			if ( ! array_key_exists('method', static::$config['browscap']) or ! is_string(static::$config['browscap']['method']))
			{
				static::$config['browscap']['method'] = static::$defaults['browscap']['method'];
			}
			static::$config['browscap']['method'] = strtolower(static::$config['browscap']['method']);
		}

		// validate the cache configuration
		if ( ! is_array(static::$config['cache']))
		{
			static::$config['cache'] = static::$defaults['cache'];
		}
		else
		{
			if ( ! array_key_exists('driver', static::$config['cache']) or ! is_string(static::$config['cache']['driver']))
			{
				static::$config['cache']['driver'] = static::$defaults['cache']['driver'];
			}

			if ( ! array_key_exists('expiry', static::$config['cache']) or ! is_numeric(static::$config['cache']['expiry']) or static::$config['cache']['expiry'] < 7200)
			{
				static::$config['cache']['expiry'] = static::$defaults['cache']['expiry'];
			}

			if ( ! array_key_exists('identifier', static::$config['cache']) or ! is_string(static::$config['cache']['identifier']))
			{
				static::$config['cache']['identifier'] = static::$defaults['cache']['identifier'];
			}
		}

		// check if we have the browser info in cache
		if (false === $browser = static::get_from_cache())
		{
			// if not, try the build in get_browser() method
			if (ini_get('browscap') == '' or false === $browser = get_browser())
			{
				// if all else fails, emulate get_browser()
				$browser = static::get_from_browscap();
			}
		}

		if ($browser)
		{
			// save it for future reference
			static::$properties = $browser;

			// store the result in local cache
			static::add_to_cache();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * get the normalized browser name
	 *
	 * @return	string
	 */
	public static function browser()
	{
		return static::$properties['Browser'];
	}

	// --------------------------------------------------------------------

	/**
	 * Get the browser platform
	 *
	 * @return	string
	 */
	public static function platform()
	{
		return static::$properties['Platform'];
	}

	// --------------------------------------------------------------------

	/**
	 * Get the Browser Version
	 *
	 * @return	string
	 */
	public static function version()
	{
		return static::$properties['Version'];
	}

	// --------------------------------------------------------------------

	/**
	 * Get any browser property
	 *
	 * @return	string
	 */
	public static function property($property = null)
	{
		return array_key_exists($property, static::$properties) ? static::$properties[$property] : null;
	}

	// --------------------------------------------------------------------

	/**
	 * Get all browser properties
	 *
	 * @return	string
	 */
	public static function properties()
	{
		return static::$properties;
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser is a robot or crawler
	 *
	 * @return	bool
	 */
	public static function is_robot()
	{
		return static::$properties['Crawler'];
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser is mobile device
	 *
	 * @return	bool
	 */
	public static function is_mobile()
	{
		return static::$properties['isMobile'];
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser accepts a specific language
	 *
	 * @param	string $language	optional ISO language code, defaults to 'en'
	 * @return	bool
	 */
	public static function accepts_language($language = 'en')
	{
		return (in_array(strtolower($language), static::languages(), true)) ? true : false;
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser accepts a specific character set
	 *
	 * @param	string $charset	optional character set, defaults to 'utf-8'
	 * @return	bool
	 */
	public static function accepts_charset($charset = 'utf-8')
	{
		return (in_array(strtolower($charset), static::charsets(), true)) ? true : false;
	}

	// --------------------------------------------------------------------

	/**
	 * get the list of browser accepted languages
	 *
	 * @return	array
	 */
	public static function languages()
	{
		return explode(',', preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim(\Input::server('http_accept_language')))));
	}

	// --------------------------------------------------------------------

	/**
	 * get the list of browser accepted charactersets
	 *
	 * @return	array
	 */
	public static function charsets()
	{
		return explode(',', preg_replace('/(;q=.+)/i', '', strtolower(trim(\Input::server('http_accept_charset')))));
	}

	// --------------------------------------------------------------------
	// internal static methods
	// --------------------------------------------------------------------

	/**
	 * add the detected browser info to the cache for this user agent string
	 *
	 * @param	bool	indicates if we were able to get the browser information
	 * @return	void
	 */
	protected static function add_to_cache()
	{
		$cache = \Cache::forge(static::$config['cache']['identifier'].'.cache');

		// save the cached user agent strings
		try
		{
			$content = $cache->get();
		}
		catch (\Exception $e)
		{
			$content = array();
		}

		$content[static::$user_agent] = static::$properties;

		// save the updated cache file
		$cache->set($content, static::$config['cache']['expiry']);
	}

	// --------------------------------------------------------------------

	/**
	 * load the cached user agent strings, and look for a match
	 *
	 * @return	mixed	array if a match is found, of false if not cached yet
	 */
	protected static function get_from_cache()
	{
		$cache = \Cache::forge(static::$config['cache']['identifier'].'.cache');

		// save the cached user agent strings
		try
		{
			$content = $cache->get();
		}
		catch (\Exception $e)
		{
			return false;
		}

		return array_key_exists(static::$user_agent, $cache) ? $content[static::$user_agent] : false;
	}

	// --------------------------------------------------------------------

	/**
	 * use the parsed php_browscap.ini file to find a user agent match
	 *
	 * @return	mixed	array if a match is found, of false if not cached yet
	 */
	protected static function get_from_browscap()
	{
		$cache = \Cache::forge(static::$config['cache']['identifier'].'.browscap');

		// load the cached browscap data
		try
		{
			$browscap = $cache->get();
		}
		// browscap not cached
		catch (\Exception $e)
		{
			$browscap = static::$config['browscap']['enabled'] ? static::parse_browscap() : array();
		}

		$search = array('\*', '\?');
		$replace = array('.*', '.');

		$result = false;

		// find a match for the user agent string
		foreach($browscap as $browser => $properties)
		{
			$pattern = '@^'.str_replace($search, $replace, preg_quote($browser, '@')).'$@i';
			if (preg_match($pattern, static::$user_agent))
			{
				// store the browser name
				$properties['Browser'] = $browser;

				// fetch possible parent info
				if (array_key_exists('Parent', $properties))
				{
					if ($properties['Parent'] > 0)
					{
						$parent = array_slice($browscap, $properties['Parent'], 1);
						unset($properties['Parent']);
						$properties = array_merge(current($parent), $properties);

						// store the browser name
						$properties['Browser'] = key($parent);
					}
				}

				// normalize keys
				$properties = \Arr::replace_keys($properties, array_flip(static::$keys));

				// merge it with the defaults to add missing values
				$result = array_merge(static::$properties, $properties);

				break;
			}
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * download and parse the browscap file
	 *
	 * @return	array	array with parsed download info, or empty if the download is disabled of failed
	 */
	protected static function parse_browscap()
	{
		// get the browscap.ini file
		switch (static::$config['browscap']['method'])
		{
			case 'local':
				if ( ! file_exists(static::$config['browscap']['file']) or filesize(static::$config['browscap']['file']) == 0)
				{
					throw new \Exception('Agent class: could not open the local browscap.ini file.');
				}
				$data = @file_get_contents(static::$config['browscap']['file']);
			break;

			// socket connections are not implemented yet!
			case 'sockets':
				$data = false;
			break;

			case 'curl':
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Fuel PHP framework - Agent class (http://fuelphp.com)');
				curl_setopt($curl, CURLOPT_URL, static::$config['browscap']['url']);
				$data = curl_exec($curl);
				curl_close($curl);
			break;

			case 'wrapper':
				ini_set('user_agent', 'Fuel PHP framework - Agent class (http://fuelphp.com)');
				$data = file_get_contents(static::$config['browscap']['url']);
			default:

			break;
		}

		if ($data === false)
		{
			logger(\Fuel::L_ERROR, 'Failed to download browscap.ini file.', 'Agent::parse_browscap');
		}

		// parse the downloaded data
		$browsers = @parse_ini_string($data, true, INI_SCANNER_RAW) or $browsers = array();

		// remove the version and timestamp entry
		array_shift($browsers);

		$result = array();

		// reverse sort on key string length
		uksort($browsers, function($a, $b) { return strlen($a) < strlen($b) ? 1 : -1; } );

		$index = array();
		$count = 0;

		// reduce the array keys
		foreach($browsers as $browser => $properties)
		{
			$index[$browser] = $count++;

			// fix any type issues
			foreach ($properties as $var => $value)
			{
				if (is_numeric($value))
				{
					$properties[$var] = $value + 0;
				}
				elseif ($value == 'true')
				{
					$properties[$var] = true;
				}
				elseif ($value == 'false')
				{
					$properties[$var] = false;
				}
			}

			$result[$browser] = \Arr::replace_keys($properties, static::$keys);

		}

		// reduce parent links to
		foreach($result as $browser => &$properties)
		{
			if (array_key_exists('Parent', $properties))
			{
				if ($properties['Parent'] == 'DefaultProperties')
				{
					unset($properties['Parent']);
				}
				else
				{
					if (array_key_exists($properties['Parent'], $index))
					{
						$properties['Parent'] = $index[$properties['Parent']];
					}
					else
					{
						unset($properties['Parent']);
					}
				}
			}
		}

		// save the result to the cache
		if ( ! empty($result))
		{
			$cache = \Cache::forge(static::$config['cache']['identifier'].'.browscap');
			$cache->set($result, static::$config['cache']['expiry']);
		}

		return $result;
	}
}


