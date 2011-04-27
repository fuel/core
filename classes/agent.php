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
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
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
		'Browser'             => "Unknown",
		'Version'             => 0,
		'MajorVer'            => 0,
		'MinorVer'            => 0,
		'Platform'            => 'Unknown',
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
	 * array of global config defaults
	 */
	protected static $defaults = array(
		'browscap' => array(
			'enabled' => true,
			'interval' => 10080,
		),
		'path' => '',		// will be set to default in _init()
		'expiry' => 604800,
	);

	/**
	 * array of global config items
	 */
	protected static $config = array(
	);

	/**
	 * browscap ini download url
	 */
	protected static $browscap_url = 'http://browsers.garykeith.com/stream.asp?BrowsCapINI';

	/**
	 * detected user agent string
	 *
	 * @var string
	 */
	protected static $user_agent = '';

	// ---------------------------------------------------------------------

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

		if (empty(static::$config['path']) or ! is_dir(static::$config['path']))
		{
			static::$config['path'] = APPPATH.'cache'.DS;
		}

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
			if ( ! array_key_exists('interval', static::$config['browscap']) or ! is_numeric(static::$config['browscap']['interval']))
			{
				static::$config['browscap']['interval'] = static::$defaults['browscap']['interval'];
			}
		}

		if (empty(static::$config['expiry']) or ! is_numeric(static::$config['expiry']))
		{
			static::$config['expiry'] = static::$defaults['expiry'];
		}

		// check if we have the browser info in cache
		if (false === $browser = static::_get_from_cache())
		{
			// if not, try the build in get_browser() method
			if (false === $browser = @get_browser())
			{
				// else emulate get_browser()
				$browser = static::_get_from_browscap();
			}
		}

		$browser and static::$properties = $browser;

		// store the result in local cache
		static::_add_to_cache($browser !== false);
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
	 * @param	mixed $robot optional, check (one of) if given robotname(s) is true
	 * @return	bool
	 */
	public static function is_robot($robot = null)
	{
		return static::$properties['Crawler'];
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser is mobile device
	 *
	 * @param	mixed $mobile optional, check (one of) if given mobile name(s) is true
	 * @return	bool
	 */
	public static function is_mobile()
	{
		return static::$properties['isMobile'];
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser is mobile device
	 *
	 * @param	mixed $referer optional, check if the referer matches the regex
	 * @return	bool
	 */
	public static function is_referer($referer = null)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser accepts a specific language
	 *
	 * @param	string $language optional, ISO language code, defaults to 'en'
	 * @return	bool
	 */
	public static function accepts_language($language = 'en')
	{
	}

	// --------------------------------------------------------------------

	/**
	 * check if the current browser accepts a specific character set
	 *
	 * @param	string $language optional, character set, defaults to 'utf-8'
	 * @return	bool
	 */
	public static function accept_charset($charset = 'utf-8')
	{
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

	/**
	 * add the detected browser info to the cache for this user agent string
	 *
	 * @param	bool	indicates if we were able to get the browser information
	 * @return	void
	 */
	protected static function _add_to_cache($found)
	{
		// save the cached user agent strings
		try
		{
			$cache = Cache::get('fuel.agent.cache');
		}
		catch (\Exception $e)
		{
			$cache = array();
		}

		$cache[static::$user_agent] = static::$properties;

		// save the updated cache file
		$browscap = Cache::set('fuel.agent.cache', $cache, $found ? static::$config['expiry'] : 86400);
	}

	// --------------------------------------------------------------------

	/**
	 * load the cached user agent strings, and look for a match
	 *
	 * @return	mixed	array if a match is found, of false if not cached yet
	 */
	protected static function _get_from_cache()
	{
		// save the cached user agent strings
		try
		{
			$cache = Cache::get('fuel.agent.cache');
		}
		catch (\Exception $e)
		{
			return false;
		}

		return array_key_exists(static::$user_agent, $cache) ? $cache[static::$user_agent] : false;
	}

	// --------------------------------------------------------------------

	/**
	 * use the parsed php_browscap.ini file to find a user agent match
	 *
	 * @return	mixed	array if a match is found, of false if not cached yet
	 */
	protected static function _get_from_browscap()
	{
		// load the cached browscap data
		try
		{
			$browscap = Cache::get('fuel.agent.browscap');
		}
		// browscap not cached
		catch (\Exception $e)
		{
			$browscap = static::_parse_browscap();
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
	protected static function _parse_browscap()
	{
		// temp filename for the download
		$file = tempnam(sys_get_temp_dir(), 'fuel');

		// download the file
$file = '/tmp/php_browscap.ini';

		// parse the downloaded file
		$browsers = @parse_ini_file($file, true, INI_SCANNER_RAW) or $browsers = array();

		// remove the version and default entries
		array_shift($browsers);
		array_shift($browsers);

		$index = array();
		$result = array();

		// reduce the array keys
		foreach($browsers as $browser => $properties)
		{
			$index[$browser] = count($result);

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
						throw new \Exception('Agent class: parse error in browsecap.ini file. Unknown parent reference detected for: '.$browser);
					}
				}
			}
		}

		// save the result to the cache
		$browscap = Cache::set('fuel.agent.browscap', $result, static::$config['expiry']);

		return $result;
	}
}

/* End of file agent.php */
