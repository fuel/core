<?php
/**
 * Part of the Fuel framework.
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
 * General Fuel Exception class
 */
class FuelException extends \Exception {}

/**
 * @deprecated  Keep until v1.2
 */
class Fuel_Exception extends \FuelException {}

/**
 * The core of the framework.
 *
 * @package		Fuel
 * @subpackage	Core
 */
class Fuel
{

	/**
	 * @var  string  The version of Fuel
	 */
	const VERSION = '1.1-rc1';

	/**
	 * @var  string  constant used for when in testing mode
	 */
	const TEST = 'test';

	/**
	 * @var  string  constant used for when in development
	 */
	const DEVELOPMENT = 'development';

	/**
	 * @var  string  constant used for when in production
	 */
	const PRODUCTION = 'production';

	/**
	 * @var  string  constant used for when testing the app in a staging env.
	 */
	const STAGE = 'stage';

	/**
	 * @var  int  No logging
	 */
	const L_NONE = 0;

	/**
	 * @var  int  Log errors only
	 */
	const L_ERROR = 1;

	/**
	 * @var  int  Log warning massages and below
	 */
	const L_WARNING = 2;

	/**
	 * @var  int  Log debug massages and below
	 */
	const L_DEBUG = 3;

	/**
	 * @var  int  Log info massages and below
	 */
	const L_INFO = 4;

	/**
	 * @var  int  Log everything
	 */
	const L_ALL = 5;

	/**
	 * @var  bool  Whether Fuel has been initialized
	 */
	public static $initialized = false;

	/**
	 * @var  string  The Fuel environment
	 */
	public static $env = \Fuel::DEVELOPMENT;

	/**
	 * @var  bool  Whether to display the profiling information
	 */
	public static $profiling = false;

	public static $locale = 'en_US';

	public static $timezone = 'UTC';

	public static $encoding = 'UTF-8';

	public static $path_cache = array();

	public static $caching = false;

	/**
	 * The amount of time to cache in seconds.
	 * @var	int	$cache_lifetime
	 */
	public static $cache_lifetime = 3600;

	protected static $cache_dir = '';

	public static $paths_changed = false;

	public static $is_cli = false;

	public static $is_test = false;

	public static $volatile_paths = array();

	protected static $_paths = array();

	protected static $packages = array();

	final private function __construct() { }

	/**
	 * Initializes the framework.  This can only be called once.
	 *
	 * @access	public
	 * @return	void
	 */
	public static function init($config)
	{
		if (static::$initialized)
		{
			throw new \FuelException("You can't initialize Fuel more than once.");
		}

		static::$_paths = array(APPPATH, COREPATH);

		// Is Fuel running on the command line?
		static::$is_cli = (bool) defined('STDIN');

		\Config::load($config);

		// Start up output buffering
		ob_start(\Config::get('ob_callback', null));

		static::$profiling = \Config::get('profiling', false);

		if (static::$profiling)
		{
			\Profiler::init();
			\Profiler::mark(__METHOD__.' Start');
		}

		static::$cache_dir = \Config::get('cache_dir', APPPATH.'cache/');
		static::$caching = \Config::get('caching', false);
		static::$cache_lifetime = \Config::get('cache_lifetime', 3600);

		if (static::$caching)
		{
			\Finder::instance()->read_cache('Fuel::paths');
		}

		// set a default timezone if one is defined
		static::$timezone = \Config::get('default_timezone') ?: date_default_timezone_get();
		date_default_timezone_set(static::$timezone);

		static::$encoding = \Config::get('encoding', static::$encoding);
		MBSTRING and mb_internal_encoding(static::$encoding);

		static::$locale = \Config::get('locale', static::$locale);

		if ( ! static::$is_cli)
		{
			if (\Config::get('base_url') === null)
			{
				\Config::set('base_url', static::generate_base_url());
			}
		}

		// Run Input Filtering
		\Security::clean_input();

		\Event::register('shutdown', 'Fuel::finish');

		//Load in the packages
		foreach (\Config::get('always_load.packages', array()) as $package => $path)
		{
			is_string($package) and $path = array($package => $path);
			\Package::load($path);
		}

		// Always load classes, config & language set in always_load.php config
		static::always_load();

		// Load in the routes
		\Config::load('routes', true);
		\Router::add(\Config::get('routes'));

		// Set  locale
		if (!empty(static::$locale))
		{
			setlocale(LC_ALL, static::$locale, static::$locale.'.'.strtolower(str_replace('-', '', static::$encoding)))
			or logger(\Fuel::L_WARNING, 'The configured locale '.static::$locale.' is not installed on your system.', __METHOD__);
		}

		static::$initialized = true;

		if (static::$profiling)
		{
			\Profiler::mark(__METHOD__.' End');
		}
	}

	/**
	 * Cleans up Fuel execution, ends the output buffering, and outputs the
	 * buffer contents.
	 *
	 * @access	public
	 * @return	void
	 */
	public static function finish()
	{
		if (static::$caching)
		{
			\Finder::instance()->write_cache('Fuel::paths');
		}

		if (static::$profiling)
		{
			// Grab the output buffer and flush it, we will rebuffer later
			$output = ob_get_clean();

			\Profiler::mark('End of Fuel Execution');
			if (preg_match("|</body>.*?</html>|is", $output))
			{
				$output  = preg_replace("|</body>.*?</html>|is", '', $output);
				$output .= \Profiler::output();
				$output .= '</body></html>';
			}
			else
			{
				$output .= \Profiler::output();
			}
			// Restart the output buffer and send the new output
			ob_start();
			echo $output;
		}
	}

	/**
	 * Finds a file in the given directory.  It allows for a cascading filesystem.
	 *
	 * @param   string   The directory to look in.
	 * @param   string   The name of the file
	 * @param   string   The file extension
	 * @param   boolean  if true return an array of all files found
	 * @param   boolean  if false do not cache the result
	 * @return  string   the path to the file
	 * @deprecated  Replaced by Finder::search()
	 */
	public static function find_file($directory, $file, $ext = '.php', $multiple = false, $cache = true)
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Finder::search() instead.', __METHOD__);

		return \Finder::search($directory, $file, $ext, $multiple, $cache);
	}

	/**
	 * Gets a list of all the files in a given directory inside all of the
	 * loaded search paths (e.g. the cascading file system).  This is useful
	 * for things like finding all the config files in all the search paths.
	 *
	 * @param   string  The directory to look in
	 * @param   string  The file filter
	 * @return  array   the array of files
	 * @deprecated  Replaced by Finder::instance()->list_files()
	 */
	public static function list_files($directory = null, $filter = '*.php')
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Finder::instance()->list_files() instead.', __METHOD__);

		return Finder::instance()->list_files($directory, $filter);
	}

	/**
	 * Generates a base url.
	 *
	 * @return  string  the base url
	 */
	protected static function generate_base_url()
	{
		$base_url = '';
		if(\Input::server('http_host'))
		{
			$base_url .= \Input::protocol().'://'.\Input::server('http_host');
		}
		if (\Input::server('script_name'))
		{
			$base_url .= str_replace('\\', '/', dirname(\Input::server('script_name')));

			// Add a slash if it is missing
			$base_url = rtrim($base_url, '/').'/';
		}
		return $base_url;
	}

	/**
	 * Add to paths which are used by Fuel::find_file()
	 *
	 * @param  string  the new path
	 * @param  bool    whether to add just behind the APPPATH or to prefix
	 */
	public static function add_path($path, $prefix = false)
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Finder::instance()->add_path() instead.', __METHOD__);

		return \Finder::instance()->add_path($path, ($prefix ? 1 : null));
	}

	/**
	 * Returns the array of currently loaded search paths.
	 *
	 * @return  array  the array of paths
	 */
	public static function get_paths()
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Finder::instance()->paths() instead.', __METHOD__);

		return \Finder::instance()->paths();
	}

	/**
	 * Includes the given file and returns the results.
	 *
	 * @param   string  the path to the file
	 * @return  mixed   the results of the include
	 */
	public static function load($file)
	{
		return include $file;
	}

	/**
	 * Adds a package or multiple packages to the stack.
	 *
	 * Examples:
	 *
	 * static::add_package('foo');
	 * static::add_package(array('foo' => PKGPATH.'bar/foo/'));
	 *
	 * @param   array|string  the package name or array of packages
	 * @return  void
	 */
	public static function add_package($package)
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Package::load() instead.', __METHOD__);
		\Package::load($package);
	}

	/**
	 * Removes a package from the stack.
	 *
	 * @param   string  the package name
	 * @return  void
	 */
	public static function remove_package($name)
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a Package::unload() instead.', __METHOD__);
		\Package::unload($name);
	}

	/**
	 * Add module
	 *
	 * Registers a given module as a class prefix and returns the path to the
	 * module. Won't register twice, will just return the path on a second call.
	 *
	 * @param   string  module name (lowercase prefix without underscore)
	 * @return  string  the path that was loaded
	 */
	public static function add_module($name)
	{
		if ( ! $path = \Autoloader::namespace_path('\\'.ucfirst($name)))
		{
			$paths = \Config::get('module_paths', array());

			if ( ! empty($paths))
			{
				foreach ($paths as $modpath)
				{
					if (is_dir($mod_check_path = $modpath.strtolower($name).DS))
					{
						$path = $mod_check_path;
						$ns = '\\'.ucfirst($name);
						\Autoloader::add_namespaces(array(
							$ns  => $path.'classes'.DS,
						), true);
						break;
					}
				}
			}

			// throw an exception if a non-existent module has been added
			if ( ! isset($ns))
			{
				throw new \FuelException('Trying to add a non-existent module "'.$name.'"');
			}
		}
		else
		{
			// strip the classes directory, we need the module root
			$path = substr($path, 0, -8);
		}

		return $path;
	}

	/**
	 * Checks to see if a module exists or not.
	 *
	 * @param   string  the module name
	 * @return  bool    whether it exists or not
	 */
	public static function module_exists($module)
	{
		$paths = \Config::get('module_paths', array());

		foreach ($paths as $path)
		{
			if (is_dir($path.$module))
			{
				return $path.$module.DS;
			}
		}
		return false;
	}

	/**
	 * This method does basic filesystem caching.  It is used for things like path caching.
	 *
	 * This method is from KohanaPHP's Kohana class.
	 *
	 * @param  string  the cache name
	 * @param  array   the data to cache (if non given it returns)
	 * @param  int     the number of seconds for the cache too live
	 */
	public static function cache($name, $data = null, $lifetime = null)
	{
		// Cache file is a hash of the name
		$file = sha1($name).'.txt';

		// Cache directories are split by keys to prevent filesystem overload
		$dir = rtrim(static::$cache_dir, DS).DS.$file[0].$file[1].DS;

		if ($lifetime === NULL)
		{
			// Use the default lifetime
			$lifetime = static::$cache_lifetime;
		}

		if ($data === null)
		{
			if (is_file($dir.$file))
			{
				if ((time() - filemtime($dir.$file)) < $lifetime)
				{
					// Return the cache
					return unserialize(file_get_contents($dir.$file));
				}
				else
				{
					try
					{
						// Cache has expired
						unlink($dir.$file);
					}
					catch (Exception $e)
					{
						// Cache has mostly likely already been deleted,
						// let return happen normally.
					}
				}
			}

			// Cache not found
			return NULL;
		}

		if ( ! is_dir($dir))
		{
			// Create the cache directory
			mkdir($dir, octdec(\Config::get('file.chmod.folders', 0777)), true);

			// Set permissions (must be manually set to fix umask issues)
			chmod($dir, octdec(\Config::get('file.chmod.folders', 0777)));
		}

		// Force the data to be a string
		$data = serialize($data);

		try
		{
			// Write the cache
			return (bool) file_put_contents($dir.$file, $data, LOCK_EX);
		}
		catch (Exception $e)
		{
			// Failed to write cache
			return false;
		}
	}

	/**
	 * Always load packages, modules, classes, config & language files set in always_load.php config
	 *
	 * @param  array  what to autoload
	 */
	public static function always_load($array = null)
	{
		if (is_null($array))
		{
			$array = \Config::get('always_load', array());
			// packages were loaded by Fuel's init already
			$array['packages'] = array();
		}

		if (isset($array['packages']))
		{
			foreach ($array['packages'] as $packages)
			{
				static::add_packages($packages);
			}
		}

		if (isset($array['modules']))
		{
			foreach ($array['modules'] as $module)
			{
				static::add_module($module, true);
			}
		}

		if (isset($array['classes']))
		{
			foreach ($array['classes'] as $class)
			{
				if ( ! class_exists($class = ucfirst($class)))
				{
					throw new \FuelException('Always load class does not exist. Unable to load: '.$class);
				}
			}
		}

		/**
		 * Config and Lang must be either just the filename, example: array(filename)
		 * or the filename as key and the group as value, example: array(filename => some_group)
		 */

		if (isset($array['config']))
		{
			foreach ($array['config'] as $config => $config_group)
			{
				\Config::load((is_int($config) ? $config_group : $config), (is_int($config) ? true : $config_group));
			}
		}

		if (isset($array['language']))
		{
			foreach ($array['language'] as $lang => $lang_group)
			{
				\Lang::load((is_int($lang) ? $lang_group : $lang), (is_int($lang) ? true : $lang_group));
			}
		}
	}

	/**
	 * Takes a value and checks if it is a Closure or not, if it is it
	 * will return the result of the closure, if not, it will simply return the
	 * value.
	 *
	 * @param   mixed  $var  The value to get
	 * @return  mixed
	 */
	public static function value($var)
	{
		return ($var instanceof \Closure) ? $var() : $var;
	}

	/**
	 * Cleans a file path so that it does not contain absolute file paths.
	 *
	 * @param   string  the filepath
	 * @return  string  the clean path
	 */
	public static function clean_path($path)
	{
		static $search = array(APPPATH, COREPATH, PKGPATH, DOCROOT, '\\');
		static $replace = array('APPPATH/', 'COREPATH/', 'PKGPATH/', 'DOCROOT/', '/');
		return str_ireplace($search, $replace, $path);
	}
}
