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
 * The core of the framework.
 *
 * @package		Fuel
 * @subpackage	Core
 * @category	Core
 */
class Fuel {

	/**
	 * @var  string  constant used for when in testing mode
	 */
	const TEST = 'test';

	/**
	 * @var  string  constant used for when in development
	 */
	const DEVELOPMENT = 'development';

	/**
	 * @var         string  constant used for when testing the code in a staging env.
	 * @deprecated  This will be removed no earlier than v1.1.  Use STAGE instead.
	 */
	const QA = 'qa';

	/**
	 * @var  string  constant used for when in production
	 */
	const PRODUCTION = 'production';

	/**
	 * @var  string  constant used for when testing the app in a staging env.
	 */
	const STAGE = 'stage';

	const L_NONE = 0;
	const L_ERROR = 1;
	const L_DEBUG = 2;
	const L_INFO = 3;
	const L_ALL = 4;

	const VERSION = '1.0-rc3';

	public static $initialized = false;

	public static $env = \Fuel::DEVELOPMENT;

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
		\Config::load($config);
	
		if (static::$initialized)
		{
			throw new \Fuel_Exception("You can't initialize Fuel more than once.");
		}

		register_shutdown_function('fuel_shutdown_handler');
		set_exception_handler('fuel_exception_handler');
		set_error_handler('fuel_error_handler');

		// Start up output buffering
		ob_start();

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
			static::$path_cache = static::cache('Fuel::path_cache');
		}

		// set a default timezone if one is defined
		static::$timezone = \Config::get('default_timezone') ?: date_default_timezone_get();
		date_default_timezone_set(static::$timezone);

		// set the encoding and locale to use
		static::$encoding = \Config::get('encoding', static::$encoding);
		static::$locale = \Config::get('locale', static::$locale);
		
		static::$_paths = array(APPPATH, COREPATH);

		if ( ! static::$is_cli)
		{
			if (\Config::get('base_url') === null)
			{
				\Config::set('base_url', static::generate_base_url());
			}

			\Uri::detect();
		}

		// Run Input Filtering
		\Security::clean_input();

		static::$env = \Config::get('environment');
		
		\Event::register('shutdown', 'Fuel::finish');

		//Load in the packages
		foreach (\Config::get('always_load.packages', array()) as $package)
		{
			static::add_package($package);
		}

		// Load in the routes
		\Config::load('routes', true);
		\Router::add(\Config::get('routes'));

		// Set  locale
		static::$locale and setlocale(LC_ALL, static::$locale);

		// Always load classes, config & language set in always_load.php config
		static::always_load();

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
		if (static::$caching and static::$paths_changed === true)
		{
			static::cache('Fuel::path_cache', static::$path_cache);
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
	 */
	public static function find_file($directory, $file, $ext = '.php', $multiple = false, $cache = true)
	{
		$cache_id = '';
		$paths = array();

		$found = $multiple ? array() : false;

		// absolute path requested?
		if (strpos($file, '/') === 0 or strpos($file, ':') === 1)
		{
			$cache_id = $file;
			$found = file_exists($file);
		}

		// the file requested namespaced?
		elseif($pos = strripos($file, '::'))
		{
			// get the namespace path
			if ($path = \Autoloader::namespace_path('\\'.ucfirst(substr($file, 0, $pos))))
			{
				$cache_id .= substr($file, 0, $pos);

				// and strip the classes directory as we need the module root
				$paths = array(substr($path,0, -8));

				// strip the namespace from the filename
				$file = substr($file, $pos+2);
			}
		}

		// if not found, use the cascading filesystem to find the file
		if (empty($cache_id))
		{
			$paths = static::$_paths;

			// get extra information of the active request
			if (class_exists('Request', false) and $active = \Request::active())
			{
				$cache_id = $active->uri->uri;
				$paths = array_merge($active->paths, $paths);
			}
		}

		$path = $directory.DS.strtolower($file).$ext;

		$cache_id = md5(($multiple ? 'M.' : 'S.').$cache_id);

		if (static::$path_cache !== null and array_key_exists($cache_id.$path, static::$path_cache))
		{
			return static::$path_cache[$cache_id.$path];
		}

		foreach ($paths as $dir)
		{
			$file_path = $dir.$path;

			if (is_file($file_path))
			{
				if ( ! $multiple)
				{
					$found = $file_path;
					break;
				}

				$found[] = $file_path;
			}
		}

		if ( ! empty($found))
		{
			$cache and static::$path_cache[$cache_id.$path] = $found;
			static::$paths_changed = true;
		}

		return $found;
	}

	/**
	 * Gets a list of all the files in a given directory inside all of the
	 * loaded search paths (e.g. the cascading file system).  This is useful
	 * for things like finding all the config files in all the search paths.
	 *
	 * @param   string  The directory to look in
	 * @param   string  The file filter
	 * @return  array   the array of files
	 */
	public static function list_files($directory = null, $filter = '*.php')
	{
		$paths = static::$_paths;
		// get the paths of the active request, and search them first
		if (class_exists('Request', false) and $active = \Request::active())
		{
			$paths = array_merge($active->paths, $paths);
		}

		$found = array();
		foreach ($paths as $path)
		{
			$found = array_merge(glob($path.$directory.'/'.$filter), $found);
		}

		return $found;
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
		if ($prefix)
		{
			// prefix the path to the paths array
			array_unshift(static::$_paths, $path);
		}
		else
		{
			// find APPPATH index
			$insert_at = array_search(APPPATH, static::$_paths) + 1;
			// insert new path just behind the APPPATH
			array_splice(static::$_paths, $insert_at, 0, $path);
		}
	}

	/**
	 * Returns the array of currently loaded search paths.
	 *
	 * @return  array  the array of paths
	 */
	public static function get_paths()
	{
		return static::$_paths;
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
		if ( ! is_array($package))
		{
			$package = array($package => PKGPATH.$package.DS);
		}
		foreach ($package as $name => $path)
		{
			if (array_key_exists($name, static::$packages))
			{
				continue;
			}
			static::add_path($path);
			static::load($path.'bootstrap.php');
			static::$packages[$name] = true;
		}
	}

	/**
	 * Removes a package from the stack.
	 *
	 * @param   string  the package name
	 * @return  void
	 */
	public static function remove_package($name)
	{
		unset(static::$packages[$name]);
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

			if (empty($paths))
			{
				return false;
			}

			foreach ($paths as $modpath)
			{
				if (is_dir($mod_check_path = $modpath.strtolower($name).DS))
				{
					$path = $mod_check_path;
					$ns = '\\'.ucfirst($name);
					\Autoloader::add_namespaces(array(
						$ns					=> $path.'classes'.DS,
					), true);
					break;
				}
			}
		}
		else
		{
			// strip the classes directory, we need the module root
			$path = substr($path,0, -8);
		}

		return $path;
	}

	/**
	 * Checks to see if a module exists or not.
	 *
	 * @param	string	the module name
	 * @return	bool	whether it exists or not
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
		$dir = static::$cache_dir.DS.$file[0].$file[1].DS;

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
			mkdir($dir, 0777, TRUE);

			// Set permissions (must be manually set to fix umask issues)
			chmod($dir, 0777);
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
				if ( ! class_exists(ucfirst($class)))
				{
					throw new \Fuel_Exception('Always load class does not exist.');
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

/* End of file fuel.php */