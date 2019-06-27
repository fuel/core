<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class ThemeException extends \FuelException {}

/**
 * Handles loading theme views and assets.
 */
class Theme
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
	 * @param   string  $name    The instance name
	 * @param   array   $config
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
	 * @var  Asset_Instance  $asset  Asset instance for this theme instance
	 */
	public $asset = null;

	/**
	 * @var  array  $paths  Possible locations for themes
	 */
	protected $paths = array();

	/**
	 * @var  View  $template  View instance for this theme instance template
	 */
	public $template = null;

	/**
	 * @var  array  $active  Currently active theme
	 */
	protected $active = array(
		'name' => null,
		'path' => null,
		'asset_base' => false,
		'asset_path' => false,
		'info' => array(),
	);

	/**
	 * @var  array  $fallback  Fallback theme
	 */
	protected $fallback = array(
		'name' => null,
		'path' => null,
		'asset_base' => false,
		'asset_path' => false,
		'info' => array(),
	);

	/**
	 * @var  array  $config  Theme config
	 */
	protected $config = array(
		'active' => 'default',
		'fallback' => 'default',
		'paths' => array(),
		'assets_folder' => 'themes',
		'view_ext' => '.html',
		'require_info_file' => false,
		'info_file_name' => 'themeinfo.php',
		'use_modules' => false,
	);

	/**
	 * @var  array  $partials	Storage for defined template partials
	 */
	protected $partials = array();

	/**
	 * @var  array  $chrome	Storage for defined partial chrome
	 */
	protected $chrome = array();

	/**
	 * @var  array  $order	Order in which partial sections should be rendered
	 */
	protected $order = array();

	/**
	 * Sets up the theme object.  If a config is given, it will not use the config
	 * file.
	 *
	 * @param   array  $config  Optional config override
	 */
	public function __construct(array $config = array())
	{
		if (empty($config))
		{
			\Config::load('theme', true, false, true);
			$config = \Config::get('theme', array());
		}

		// Order of this addition is important, do not change this.
		$this->config = $config + $this->config;

		// define the default theme paths...
		$this->add_paths($this->config['paths']);

		// create a unique asset instance for this theme instance...
		$this->asset = \Asset::forge('theme_'.spl_object_hash($this), array('paths' => array()));

		// and set the active and the fallback theme
		$this->active($this->config['active']);
		$this->fallback($this->config['fallback']);
	}

	/**
	 * Magic method, returns the output of [static::render].
	 *
	 * @return  string
	 * @uses    Theme::render
	 */
	public function __toString()
	{
		try
		{
			return (string) $this->render();
		}
		catch (\Exception $e)
		{
			\Errorhandler::exception_handler($e);

			return '';
		}
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
		return $this->set_theme($theme, 'active');
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
		return $this->set_theme($theme, 'fallback');
	}

	/**
	 * Loads a view from the currently active theme, the fallback theme, or
	 * via the standard FuelPHP cascading file system for views
	 *
	 * @param   string  $view         View name
	 * @param   array   $data         View data
	 * @param   bool    $auto_filter  Auto filter the view data
	 * @return  View    New View object
	 * @throws  \ThemeException
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
	 * Loads a viewmodel, and have it use the view from the currently active theme,
	 * the fallback theme, or the standard FuelPHP cascading file system
	 *
	 * @param   string  $view         ViewModel classname without View_ prefix or full classname
	 * @param   string  $method       Method to execute
	 * @param   bool    $auto_filter  Auto filter the view data
	 * @return  View    New View object
	 *
	 * @deprecated 1.8
	 */
	public function viewmodel($view, $method = 'view', $auto_filter = null)
	{
		return \Viewmodel::forge($view, $method, $auto_filter, $this->find_file($view));
	}

	/**
	 * Loads a presenter, and have it use the view from the currently active theme,
	 * the fallback theme, or the standard FuelPHP cascading file system
	 *
	 * @param   string  $presenter    Presenter classname without View_ prefix or full classname
	 * @param   string  $method       Method to execute
	 * @param   bool    $auto_filter  Auto filter the view data
	 * @param   string  $view         Custom View to associate with this persenter
	 * @return  Presenter
	 */
	public function presenter($presenter, $method = 'view', $auto_filter = null, $view = null)
	{
		// if no custom view is given, make it equal to the presenter name
		if (is_null($view))
		{
			// loading from a specific namespace?
			if (strpos($presenter, '::') !== false)
			{
				$split = explode('::', $presenter, 2);
				if (isset($split[1]))
				{
					// remove the namespace from the view name
					$view = $split[1];
				}
			}
			else
			{
				$view = $presenter;
			}
		}

		return \Presenter::forge($presenter, $method, $auto_filter, $this->find_file($view));
	}

	/**
	 * Loads an asset from the currently loaded theme.
	 *
	 * @param   string  $path  Relative path to the asset
	 * @return  string  Full asset URL or path if outside docroot
	 * @throws  \ThemeException
	 */
	public function asset_path($path)
	{
		if ($this->active['path'] === null)
		{
			throw new \ThemeException('You must set an active theme.');
		}

		if ($this->active['asset_base'])
		{
			return $this->active['asset_base'].$path;
		}
		else
		{
			return $this->active['path'].$path;
		}
	}

	/**
	 * Sets a template for a theme
	 *
	 * @param   string  $template Name of the template view
	 * @return  View
	 */
	public function set_template($template)
	{
		// make sure the template is a View
		if (is_string($template))
		{
			$this->template = $this->view($template);
		}
		else
		{
			$this->template = $template;
		}

		// return the template view for chaining
		return $this->template;
	}

	/**
	 * Get the template view so it can be manipulated
	 *
	 * @return  string|View
	 * @throws  \ThemeException
	 */
	public function get_template()
	{
		// make sure the partial entry exists
		if (empty($this->template))
		{
			throw new \ThemeException('No valid template could be found. Use set_template() to define a page template.');
		}

		// return the template
		return $this->template;
	}

	/**
	 * Define a custom order for a partial section
	 *
	 * @param   string  $section  name of the partial section
	 * @param   mixed   $order
	 * @throws  \ThemeException
	 */
	public function set_order($section, $order)
	{
		$this->order[$section] = $order;
	}

	/**
	 * Render the partials and the theme template
	 *
	 * @return  string|View
	 * @throws  \ThemeException
	 */
	public function render()
	{
		// make sure the template to be rendered is defined
		if (empty($this->template))
		{
			throw new \ThemeException('No valid template could be found. Use set_template() to define a page template.');
		}

		// storage for rendered results
		$rendered = array();

		// make sure we have a render ordering for all defined partials
		foreach ($this->partials as $key => $partials)
		{
			if ( ! isset($this->order[$key]))
			{
				$this->order[$key] = 0;
			}
		}

		// determine the rendering sequence
		asort($this->order, SORT_NUMERIC);

		// pre-process all defined partials in defined order
		foreach ($this->order as $key => $order)
		{
			$output = '';
			if (isset($this->partials[$key]))
			{
				foreach ($this->partials[$key] as $index => $partial)
				{
					// render the partial
					if (is_callable(array($partial, 'render')))
					{
						$output .= $partial->render();
					}
					else
					{
						$output .= $partial;
					}
				}
			}

			// store the rendered output
			if ( ! empty($output) and array_key_exists($key, $this->chrome))
			{
				// encapsulate the partial in the chrome template
				$rendered[$key] = $this->chrome[$key]['view']->set($this->chrome[$key]['var'], $output, false);
			}
			else
			{
				// store the partial output
				$rendered[$key] = $output;
			}
		}

		// assign the partials to the template
		$this->template->set('partials', $rendered, false);

		// return the template
		return $this->template;
	}

	/**
	 * Sets a partial for the current template
	 *
	 * @param   string  						$section   Name of the partial section in the template
	 * @param   string|View|ViewModel|Presenter	$view      View, or name of the view
	 * @param   bool							$overwrite If true overwrite any already defined partials for this section
	 * @return  View
	 */
	public function set_partial($section, $view, $overwrite = false)
	{
		// make sure the partial entry exists
		array_key_exists($section, $this->partials) or $this->partials[$section] = array();

		// make sure the partial is a view
		if (is_string($view))
		{
			$name = $view;
			$view = $this->view($view);
		}
		else
		{
			$name = 'partial_'.count($this->partials[$section]);
		}

		// store the partial
		if ($overwrite)
		{
			$this->partials[$section] = array($name => $view);
		}
		else
		{
			$this->partials[$section][$name] = $view;
		}

		// return the partial view object for chaining
		return $this->partials[$section][$name];
	}

	/**
	 * Get a partial so it can be manipulated
	 *
	 * @param   string	$section   Name of the partial section in the template
	 * @param   string	$view      name of the view
	 * @return  View
	 * @throws  \ThemeException
	 */
	public function get_partial($section, $view)
	{
		// make sure the partial entry exists
		if ( ! array_key_exists($section, $this->partials) or ! array_key_exists($view, $this->partials[$section]))
		{
			throw new \ThemeException(sprintf('No partial named "%s" can be found in the "%s" section.', $view, $section));
		}

		return $this->partials[$section][$view];
	}

	/**
	 * Returns whether or not a section has partials defined
	 *
	 * @param   string  				$section   Name of the partial section in the template
	 * @return  bool
	 */
	public function has_partials($section)
	{
		return $this->partial_count($section) > 0;
	}

	/**
	 * Returns the number of partials defined for a section
	 *
	 * @param   string  				$section   Name of the partial section in the template
	 * @return  int
	 */
	public function partial_count($section)
	{
		// return the defined partial count
		return array_key_exists($section, $this->partials) ? count($this->partials[$section]) : 0;
	}

	/**
	 * Sets a chrome for a partial
	 *
	 * @param   string  						$section	Name of the partial section in the template
	 * @param   string|View|ViewModel|Presenter	$view   	chrome View, or name of the view
	 * @param   string  						$var		Name of the variable in the chrome that will output the partial
	 *
	 * @return  View|ViewModel|Presenter, the view partial
	 */
	public function set_chrome($section, $view, $var = 'content')
	{
		// make sure the chrome is a view
		if (is_string($view))
		{
			$view = $this->view($view);
		}

		$this->chrome[$section] = array('var' => $var, 'view' => $view);

		return $view;
	}

	/**
	 * Get a set chrome view
	 *
	 * @param   string  						$section	Name of the partial section in the template
	 * @return mixed
	 * @throws \ThemeException
	 */
	public function get_chrome($section)
	{
		// make sure the partial entry exists
		if ( ! array_key_exists($section, $this->chrome))
		{
			throw new \ThemeException(sprintf('No chrome for a partial named "%s" can be found.', $section));
		}

		return $this->chrome[$section]['view'];
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
			foreach(new \GlobIterator($path.'*') as $theme)
			{
				$themes[] = $theme->getFilename();
			}
		}
		sort($themes);

		return $themes;
	}

	/**
	 * Get a value from the info array
	 *
	 * @param   mixed  $var
	 * @param   mixed  $default
	 * @param   mixed  $theme
	 * @return  mixed
	 * @throws  \ThemeException
	 */
	public function get_info($var = null, $default = null, $theme = null)
	{
		// if no theme is given
		if ($theme === null)
		{
			// if no var to search is given return the entire active info array
			if ($var === null)
			{
				return $this->active['info'];
			}

			// find the value in the active theme info
			if (($value = \Arr::get($this->active['info'], $var, null)) !== null)
			{
				return $value;
			}

			// and if not found, check the fallback
			elseif (($value = \Arr::get($this->fallback['info'], $var, null)) !== null)
			{
				return $value;
			}
		}

		// or if we have a specific theme
		else
		{
			// fetch the info from that theme
			$info = $this->load_info($theme);

			// and return the requested value
			return $var === null ? $info : \Arr::get($info, $var, $default);
		}

		// not found, return the given default value
		return $default;
	}

	/**
	 * Set a value in the info array
	 *
	 * @return  Theme
	 */
	public function set_info($var, $value = null, $type = 'active')
	{
		if ($type == 'active')
		{
			\Arr::set($this->active['info'], $var, $value);
		}
		elseif ($type == 'fallback')
		{
			\Arr::set($this->fallback['info'], $var, $value);
		}

		// return for chaining
		return $this;
	}

	/**
	 * Load in the theme.info file for the given (or active) theme.
	 *
	 * @param   string  $theme  Name of the theme (null for active)
	 * @return  array   Theme info array
	 * @throws \ThemeException
	 */
	public function load_info($theme = null)
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
				'path' => $path,
			);
		}

		if ( ! $path)
		{
			throw new \ThemeException(sprintf('Could not find theme "%s".', $theme));
		}

		if (($file = $this->find_file($this->config['info_file_name'], array($theme))) == $this->config['info_file_name'])
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

		return \Config::load($file, false, true);
	}

	/**
	 * Save the theme.info file for the active (or fallback) theme.
	 *
	 * @param   string  $type  Name of the theme (null for active)
	 * @return  array   Theme info array
	 * @throws  \ThemeException
	 */
	public function save_info($type = 'active')
	{
		if ($type == 'active')
		{
			$theme = $this->active;
		}
		elseif ($type == 'fallback')
		{
			$theme = $this->fallback;
		}
		else
		{
			throw new \ThemeException('No location found to save the info file to.');
		}

		if ( ! $theme['path'])
		{
			throw new \ThemeException(sprintf('Could not find theme "%s".', $theme['name']));
		}

		if ( ! ($file = $this->find_file($this->config['info_file_name'], array($theme))))
		{
			throw new \ThemeException(sprintf('Theme "%s" is missing "%s".', $theme['name'], $this->config['info_file_name']));
		}

		return \Config::save($file, $theme['info']);
	}

	/**
	 * Enable or disable the use of modules. If enabled, every theme view loaded
	 * will be prefixed with the module name, so you don't have to hardcode the
	 * module name as a view file prefix
	 *
	 * @param	bool|string  $enable  enable if true or string, disable if false
	 * @return	Theme
	 */
	public function use_modules($enable = true)
	{
		$this->config['use_modules'] = $enable;

		// return for chaining
		return $this;
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

		// determine the path prefix and optionally the module path
		$path_prefix = '';
		$module_path = null;
		if ($this->config['use_modules'] and class_exists('Request', false) and $request = \Request::active() and $module = $request->module)
		{
			// we're using module name prefixing
			$path_prefix = $module.DS;

			// and modules are in a separate path
			is_string($this->config['use_modules']) and $path_prefix = trim($this->config['use_modules'], '\\/').DS.$path_prefix;

			// do we need to check the module too?
			$this->config['use_modules'] === true and $module_path = \Module::exists($module).'themes'.DS;
		}

		foreach ($themes as $theme)
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION)
				? ('.'.pathinfo($view, PATHINFO_EXTENSION))
				: $this->config['view_ext'];

			$file = pathinfo($view, PATHINFO_DIRNAME)
				? (str_replace(array('/', DS), DS, pathinfo($view, PATHINFO_DIRNAME)).DS)
				: '';
			$file .= pathinfo($view, PATHINFO_FILENAME);

			if (empty($theme['find_file']))
			{
				if ($module_path and ! empty($theme['name']) and is_file($path = $module_path.$theme['name'].DS.$file.$ext))
				{
					return $path;
				}
				elseif (is_file($path = $theme['path'].$path_prefix.$file.$ext))
				{
					return $path;
				}
				elseif (is_file($path = $theme['path'].$file.$ext))
				{
					return $path;
				}
			}
			else
			{
				if ($path = \Finder::search($theme['path'].$path_prefix, $file, $ext))
				{
					return $path;
				}
			}
		}

		// not found, return the viewname to fall back to the standard View processing
		return $view;
	}

	/**
	 * Sets a  theme.
	 *
	 * @param   string  $theme  Theme name to set active
	 * @param   string  $type   name of the internal theme array to set
	 * @return  array   The theme array
	 * @throws  \ThemeException
	 */
	protected function set_theme($theme = null, $type = 'active')
	{
		// set the theme if given
		if ($theme !== null)
		{
			// remove the defined theme asset paths from the asset instance
			empty($this->active['asset_path']) or $this->asset->remove_path($this->active['asset_path']);
			empty($this->fallback['asset_path']) or $this->asset->remove_path($this->fallback['asset_path']);

			$this->{$type} = $this->create_theme_array($theme);

			// add the asset paths to the asset instance
			empty($this->fallback['asset_path']) or $this->asset->add_path($this->fallback['asset_path']);
			empty($this->active['asset_path']) or $this->asset->add_path($this->active['asset_path']);
		}

		// and return the theme config
		return $this->{$type};
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
			);
		}
		else
		{
			if ( ! isset($theme['name']) or ! isset($theme['path']))
			{
				throw new \ThemeException('Theme name and path must be given in array config.');
			}
		}

		// load the theme info file
		if ( ! isset($theme['info']))
		{
			$theme['info'] = $this->load_info($theme);
		}

		if ( ! isset($theme['asset_base']))
		{
			// determine the asset location and base URL
			$assets_folder = rtrim($this->config['assets_folder'], DS).'/';

			// all theme files are inside the docroot
			if (strpos($path, DOCROOT) === 0 and is_dir($path.$assets_folder))
			{
				$theme['asset_path'] = $path.$assets_folder;
				$theme['asset_base'] = str_replace(DOCROOT, '', $theme['asset_path']);
			}

			// theme views and templates are outside the docroot
			else
			{
				$theme['asset_base'] = $assets_folder.$theme['name'].'/';
			}
		}

		if ( ! isset($theme['asset_path']) and strpos($theme['asset_base'], '://') === false)
		{
			$theme['asset_path'] = DOCROOT.$theme['asset_base'];
		}

		// always uses forward slashes for the asset base and path
		$theme['asset_base'] = str_replace(DS, '/', $theme['asset_base']);
		$theme['asset_path'] = str_replace(DS, '/', $theme['asset_path']);

		// but if on windows, file paths require a backslash
		if (strpos($theme['asset_base'], '://') === false and DS !== '/')
		{
			$theme['asset_path'] = str_replace('/', DS, $theme['asset_path']);
		}

		return $theme;
	}
}
