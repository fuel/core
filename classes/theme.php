<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.1
 * @author     Cartalyst LLC
 * @license    MIT License
 * @copyright  2011 Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Fuel\Core;

class ThemeException extends \FuelException { }

/**
 * Handles loading theme views and assets.
 *
 * @author  Dan Horrigan
 */
class Theme implements \ArrayAccess, \Iterator
{

	/**
	 * All the Theme instances
	 *
	 * @var  array
	 */
	protected static $instances = array();

	/**
	 * Acts as a Multiton.  Will return the requested instance, or will create
	 * a new named one if it does not exist.
	 *
	 * @param   string    $name  The instance name
	 *
	 * @return  Theme
	 */
	public static function instance($name = '_default_', array $config = array())
	{
		if ( ! \array_key_exists($name, static::$instances))
		{
			static::$instances[$name] = static::forge($config);
		}

		return static::$instances[$name];
	}

	/**
	 * Gets a new instance of the Theme class.
	 *
	 * @param   array  $config  Optional config override
	 * @return  Theme
	 */
	public static function forge(array $config = array())
	{
		return new static($config);
	}

	/**
	 * @var  array  $paths  Possible locations for themes
	 */
	protected $paths = array();

	/**
	 * @var  array  $active  Currently active theme
	 */
	protected $active = array(
		'name' => null,
		'path' => null,
		'asset_base' => false,
		'info' => array(),
	);

	/**
	 * @var  array  $fallback  Fallback theme
	 */
	protected $fallback = array(
		'name' => null,
		'path' => null,
		'asset_base' => false,
		'info' => array(),
	);

	/**
	 * @var  array  $config  Theme config
	 */
	protected $config = array(
		'active' => 'default',
		'fallback' => 'default',
		'paths' => array(),
		'assets_folder' => 'assets',
		'view_ext' => '.html',
		'require_info_file' => false,
		'info_file_name' => 'theme.info',
		'info_file_type' => 'php',
	);

	/**
	 * Sets up the theme object.  If a config is given, it will not use the config
	 * file.
	 *
	 * @param   array  $config  Optional config override
	 * @return  void
	 */
	public function __construct(array $config = array())
	{
		if (empty($config))
		{
			\Config::load('theme', true, false, true);
			$config = \Config::get('theme', false);
		}
		// Order of this addition is important, do not change this.
		$this->config = $config + $this->config;

		$this->add_paths($this->config['paths']);
		$this->active($this->config['active']);
		$this->fallback($this->config['fallback']);
	}

	/**
	 * Loads a view from the currently loaded theme.
	 *
	 * @param   string  $view         View name
	 * @param   array   $data         View data
	 * @param   bool    $auto_filter  Auto filter the view data
	 * @return  View    New View object
	 */
	public function view($view, $data = array(), $auto_filter = null)
	{
		if ($this->active['path'] === null)
		{
			throw new \ThemeException('You must set an active theme.');
		}

		return \View::forge($this->find_file($view), $data, $auto_filter);
	}

	/**
	 * Find the absolute path to a file in a set of Themes.  You can optionally
	 * send an array of themes to search.  If you do not, it will search active
	 * then fallback (in that order).
	 *
	 * @param   string  $view    name of the view to find
	 * @param   array   $themes  optional array of themes to search
	 * @return  string  absolute path to the view
	 * @throws  \ThemeException  when not found
	 */
	protected function find_file($view, $themes = null)
	{
		if ($themes === null)
		{
			$themes = array($this->active, $this->fallback);
		}

		foreach ($themes as $theme)
		{
			$ext   = pathinfo($view, PATHINFO_EXTENSION) ?
				'.'.pathinfo($view, PATHINFO_EXTENSION) : $this->config['view_ext'];
			$file  = (pathinfo($view, PATHINFO_DIRNAME) ?
					str_replace(array('/', DS), DS, pathinfo($view, PATHINFO_DIRNAME)).DS : '').
				pathinfo($view, PATHINFO_FILENAME);
			if (empty($theme['find_file']))
			{
				if (is_file($path = $theme['path'].$file.$ext))
				{
					return $path;
				}
			}
			else
			{
				if ($path = \Finder::search($theme['path'], $file, $ext))
				{
					return $path;
				}
			}
		}

		throw new \ThemeException(sprintf('Could not locate view "%s" in the theme.', $view));
	}

	/**
	 * Loads an asset from the currently loaded theme.
	 *
	 * @param   string  $path  Relative path to the asset
	 * @return  string  Full asset URL or path if outside docroot
	 */
	public function asset($path)
	{
		if ($this->active['path'] === null)
		{
			throw new \ThemeException('You must set an active theme.');
		}

		if ($this->active['asset_base'])
		{
			return $this->active['asset_base'].$path;
		}

		return $this->active['path'].$path;
	}

	/**
	 * Gets an option for the active theme.
	 *
	 * @param   string  $option   Option to get
	 * @param   mixed   $default  Default value
	 * @return  mixed
	 */
	public function option($option, $default = null)
	{
		if ( ! isset($this->active['info']['options'][$option]))
		{
			return $default;
		}

		return $this->active['info']['options'][$option];
	}

	/**
	 * Sets an option for the active theme.
	 *
	 * NOTE: This does NOT update the theme.info file.
	 *
	 * @param   string  $option   Option to get
	 * @param   mixed   $value    Value
	 * @return  $this
	 */
	public function set_option($option, $value)
	{
		$this->active['info']['options'][$option] = $value;

		return $this;
	}

	/**
	 * Adds the given path to the theme search path.
	 *
	 * @param   string  $path  Path to add
	 * @return  void
	 */
	public function add_path($path)
	{
		$this->paths[] = rtrim($path, DS).DS;
	}

	/**
	 * Adds the given paths to the theme search path.
	 *
	 * @param   array  $paths  Paths to add
	 * @return  void
	 */
	public function add_paths(array $paths)
	{
		array_walk($paths, array($this, 'add_path'));
	}

	/**
	 * Sets the currently active theme.  Will return the currently active
	 * theme.  It will throw a \ThemeException if it cannot locate the theme.
	 *
	 * @param   string  $theme  Theme name to set active
	 * @return  array   The theme array
	 * @throws  \ThemeException
	 */
	public function active($theme = null)
	{
		if ($theme !== null)
		{
			$this->active = $this->create_theme_array($theme);
		}

		return $this->active;
	}

	/**
	 * Sets the fallback theme.  This theme will be used if a view or asset
	 * cannot be found in the active theme.  Will return the fallback
	 * theme.  It will throw a \ThemeException if it cannot locate the theme.
	 *
	 * @param   string  $theme  Theme name to set active
	 * @return  array   The theme array
	 * @throws  \ThemeException
	 */
	public function fallback($theme = null)
	{
		if ($theme !== null)
		{
			$this->fallback = $this->create_theme_array($theme);
		}

		return $this->fallback;
	}

	/**
	 * Finds the given theme by searching through all of the theme paths.  If
	 * found it will return the path, else it will return `false`.
	 *
	 * @param   string  $theme  Theme to find
	 * @return  string|false  Path or false if not found
	 */
	public function find($theme)
	{
		foreach ($this->paths as $path)
		{
			if (is_dir($path.$theme))
			{
				return $path.$theme.DS;
			}
		}

		return false;
	}

	/**
	 * Gets an array of all themes in all theme paths, sorted alphabetically.
	 *
	 * @return  array
	 */
	public function all()
	{
		$themes = array();
		foreach ($this->paths as $path)
		{
			foreach(glob($path.'*', GLOB_ONLYDIR) as $theme)
			{
				$themes[] = basename($theme);
			}
		}
		sort($themes);

		return $themes;
	}

	public function info($var, $default = null, $theme = null)
	{
		if ($theme === null)
		{
			if (isset($this->active['info'][$var]))
			{
				return $this->active['info'][$var];
			}
			elseif (isset($this->fallback['info'][$var]))
			{
				return $this->fallback['info'][$var];
			}
		}

		if ($theme !== null)
		{
			$info = $this->all_info($theme);
			if (isset($info[$var]))
			{
				return $info[$var];
			}
		}

		return $default;
	}

	/**
	 * Reads in the theme.info file for the given (or active) theme.
	 *
	 * @param   string  $theme  Name of the theme (null for active)
	 * @return  array   Theme info array
	 */
	public function all_info($theme = null)
	{
		if ($theme === null)
		{
			$theme = $this->active;
		}

		if (is_array($theme))
		{
			$path = $theme['path'];
			$name = $theme['name'];
		}
		else
		{
			$path = $this->find($theme);
			$name = $theme;
			$theme = array(
				'name' => $name,
				'path' => $path
			);
		}

		if ( ! $path)
		{
			throw new \ThemeException(sprintf('Could not find theme "%s".', $theme));
		}

		try
		{
			$file = $this->find_file($this->config['info_file_name'], array($theme));
		}
		catch (ThemeException $e)
		{
			if ($this->config['require_info_file'])
			{
				throw new \ThemeException(sprintf('Theme "%s" is missing "%s".', $name, $this->config['info_file_name']));
			}
			else
			{
				return array();
			}
		}

		$type = strtolower($this->config['info_file_type']);
		switch ($type)
		{
			case 'ini':
				$info = parse_ini_file($file, true);
			break;

			case 'json':
				$info = json_decode(file_get_contents($file), true);
			break;

			case 'yaml':
				$info = \Format::forge(file_get_contents($file), 'yaml')->to_array();
			break;

			case 'php':
				$info = include($file);
			break;

			default:
				throw new \ThemeException(sprintf('Invalid info file type "%s".', $type));
		}

		return $info;
	}


	/**
	 * Implementation of the Iterator interface
	 */

	/**
	 * Iterator - Rewind the info array to the first element
	 *
	 * @return  void
	 */
	public function rewind()
	{
		reset($this->active['info']);
	}

	/**
	 * Iterator - Return the current element of the info array
	 *
	 * @return  mixed
	 */
	public function current()
	{
		return current($this->active['info']);
	}

	/**
	 * Iterator - Return the key of the current element of the info array
	 *
	 * @return  mixed
	 */
	public function key()
	{
		return key($this->active['info']);
	}

	/**
	 * Iterator - Move forward to next element of the info array
	 *
	 * @return  mixed
	 */
	public function next()
	{
		return next($this->active['info']);
	}

	/**
	 * Iterator - Checks if current position is valid
	 *
	 * @return  bool
	 */
	public function valid()
	{
		return key($this->active['info']) !== null;
	}

	/**
	 * ArrayAccess - Sets the given varaible for the active theme.
	 *
	 * @param   string  $offset  Offset to set
	 * @param   mixed   $value   Value to set
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		$this->active['info'][$offset] = $value;
	}

	/**
	 * ArrayAccess - Checks if the given varaible for the active theme.
	 *
	 * @param   string  $offset  Offset to check
	 * @return  bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->active['info'][$offset]);
	}

	/**
	 * ArrayAccess - Unsets the given varaible for the active theme.
	 *
	 * @param   string  $offset  Offset to set
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		unset($this->active['info'][$offset]);
	}

	/**
	 * ArrayAccess - Gets the given offest for the active theme info.
	 *
	 * @param   string  $offset  Key
	 * @return  mixed
	 */
	public function offsetGet($offset)
	{
		return isset($this->active['info'][$offset]) ? $this->active['info'][$offset] : null;
	}

	/**
	 * Creates a theme array by locating the given theme and setting all of the
	 * option.  It will throw a \ThemeException if it cannot locate the theme.
	 *
	 * @param   string  $theme  Theme name to set active
	 * @return  array   The theme array
	 * @throws  \ThemeException
	 */
	protected function create_theme_array($theme)
	{
		if ( ! is_array($theme))
		{
			if ( ! $path = $this->find($theme))
			{
				throw new \ThemeException(sprintf('Theme "%s" could not be found.', $theme));
			}

			$theme = array(
				'name' => $theme,
				'path' => $path,
				'asset_base' => null,
			);
		}
		else
		{
			if ( ! isset($theme['name']) or ! isset($theme['path']))
			{
				throw new \ThemeException('Theme name and path must be given in array config.');
			}
		}

		if ( ! isset($theme['info']))
		{
			$theme['info'] = $this->all_info($theme);
		}

		if ( ! isset($theme['asset_base']))
		{
			$assets_folder = rtrim($this->config['assets_folder'], DS).DS;
			if (strpos($path, DOCROOT) === 0 and is_dir($path.$assets_folder))
			{
				$path = str_replace(DOCROOT, '', $path).$assets_folder;
				$theme['asset_base'] = Config::get('base_url').$path;
			}
		}

		return $theme;
	}
}
