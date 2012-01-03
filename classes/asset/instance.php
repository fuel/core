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
 * The Asset class allows you to easily work with your apps assets.
 * It allows you to specify multiple paths to be searched for the
 * assets.
 *
 * You can configure the paths by copying the core/config/asset.php
 * config file into your app/config folder and changing the settings.
 *
 * @package     Fuel
 * @subpackage  Core
 */
class Asset_Instance
{

	/**
	 * @var  array  the asset paths to be searched
	 */
	protected $_asset_paths = array(
		'css' => array(),
		'js' => array(),
		'img' => array(),
	);

	/**
	 * @var  array  the sub-folders to be searched
	 */
	protected $_path_folders = array(
		'css' => 'css/',
		'js' => 'js/',
		'img' => 'img/',
	);

	/**
	 * @var  string  the URL to be prepended to all assets
	 */
	protected $_asset_url = '/';

	/**
	 * @var  bool  whether to append the file mtime to the url
	 */
	protected $_add_mtime = true;

	/**
	 * @var  array  holds the groups of assets
	 */
	protected $_groups = array();

	/**
	 * @var  string  prefix for generated output to provide proper indentation
	 */
	protected $_ident = '';

	/**
	 * @var  bool  if true, directly renders the output of no group name is given
	 */
	protected $_auto_render = true;

	/**
	 * Parse the config and initialize the object instance
	 *
	 * @return  void
	 */
	public function __construct($config)
	{
		//global search path folders
		$this->_path_folders['css'] = $config['css_dir'];
		$this->_path_folders['js'] = $config['js_dir'];
		$this->_path_folders['img'] = $config['img_dir'];

		// global search paths
		foreach ($config['paths'] as $path)
		{
			$this->add_path($path);
		}

		// per-type search paths
		foreach ($config['folders'] as $type => $folders)
		{
			is_array($folders) or $folders = array($folders);

			foreach ($folders as $path)
			{
				$this->add_path($path, $type);
			}
		}

		$this->_add_mtime = $config['add_mtime'];
		$this->_asset_url = $config['url'];
		$this->_indent = str_repeat($config['indent_with'], $config['indent_level']);
		$this->_auto_render = $config['auto_render'];
	}

	/**
	 * Adds the given path to the front of the asset paths array.  It adds paths
	 * in a way so that asset paths are used First in Last Out.
	 *
	 * @param   string  the path to add
	 * @param   string  optional path type (js, css or img)
	 * @return  object  current instance
	 */
	public function add_path($path, $type = null)
	{
		is_null($type) and $type = $this->_path_folders;

		if( is_array($type))
		{
			foreach ($type as $key => $folder)
			{
				is_numeric($key) and $key = $folder;
				array_unshift($this->_asset_paths[$key], str_replace('../', '', rtrim($path, '/')).'/'.rtrim($folder, '/').'/');
			}
		}
		else
		{
			array_unshift($this->_asset_paths[$type], str_replace('../', '', rtrim($path, '/')).'/');
		}

		return $this;
	}

	/**
	 * Removes the given path from the asset paths array
	 *
	 * @param   string  the path to remove
	 * @param   string  optional path type (js, css or img)
	 * @return  object  current instance
	 */
	public function remove_path($path, $type = null)
	{
		is_null($type) and $type = $this->_path_folders;

		if( is_array($type))
		{
			foreach ($type as $key => $folder)
			{
				is_numeric($key) and $key = $folder;
				if (($found = array_search(str_replace('../', '', rtrim($path,'/').$folder.'/'), $this->_asset_paths[$key])) !== false)
				{
					unset($this->_asset_paths[$key][$found]);
				}
			}
		}
		else
		{
			if (($key = array_search(str_replace('../', '', rtrim($path,'/')), $this->_asset_paths[$type])) !== false)
			{
				unset($this->_asset_paths[$type][$key]);
			}
		}

		return $this;
	}

	/**
	 * Renders the given group.  Each tag will be separated by a line break.
	 * You can optionally tell it to render the files raw.  This means that
	 * all CSS and JS files in the group will be read and the contents included
	 * in the returning value.
	 *
	 * @param   mixed   the group to render
	 * @param   bool    whether to return the raw file or not
	 * @return  string  the group's output
	 */
	public function render($group = null, $raw = false)
	{
		is_null($group) and $group = '_default_';

		if (is_string($group))
		{
			isset($this->_groups[$group]) and $group = $this->_groups[$group];
		}

		is_array($group) or $group = array();

		$css = '';
		$js = '';
		$img = '';
		foreach ($group as $key => $item)
		{
			$type = $item['type'];
			$filename = $item['file'];
			$attr = $item['attr'];

			if ( ! preg_match('|^(\w+:)?//|', $filename) and ($file = $this->find_file($filename, $type)))
			{
				$raw or $file = $this->_asset_url.$file.($this->_add_mtime ? '?'.filemtime($file) : '');
			}
			else
			{
				$file = $filename;
			}

			switch($type)
			{
				case 'css':
					if ($raw)
					{
						return '<style type="text/css">'.PHP_EOL.file_get_contents($file).PHP_EOL.'</style>';
					}
					$attr['rel'] = 'stylesheet';
					$attr['type'] = 'text/css';
					$attr['href'] = $file;

					$css .= $this->_indent.html_tag('link', $attr).PHP_EOL;
				break;
				case 'js':
					if ($raw)
					{
						return html_tag('script', array('type' => 'text/javascript'), PHP_EOL.file_get_contents($file).PHP_EOL).PHP_EOL;
					}
					$attr['type'] = 'text/javascript';
					$attr['src'] = $file;

					$js .= $this->_indent.html_tag('script', $attr, '').PHP_EOL;
				break;
				case 'img':
					$attr['src'] = $file;
					$attr['alt'] = isset($attr['alt']) ? $attr['alt'] : '';

					$img .= $this->_indent.html_tag('img', $attr );
				break;
			}

		}

		// return them in the correct order
		return $css.$js.$img;
	}

	// --------------------------------------------------------------------

	/**
	 * CSS
	 *
	 * Either adds the stylesheet to the group, or returns the CSS tag.
	 *
	 * @access	public
	 * @param	mixed	       The file name, or an array files.
	 * @param	array	       An array of extra attributes
	 * @param	string	       The asset group name
	 * @return	string|object  Rendered asset or current instance when adding to group
	 */
	public function css($stylesheets = array(), $attr = array(), $group = null, $raw = false)
	{
		static $temp_group = 1000000;

		if ($group === null)
		{
			$render = $this->_auto_render;
			$group = $render ? (string) (++$temp_group) : '_default_';
		}
		else
		{
			$render = false;
		}

		$this->_parse_assets('css', $stylesheets, $attr, $group);

		if ($render)
		{
			return $this->render($group, $raw);
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * JS
	 *
	 * Either adds the javascript to the group, or returns the script tag.
	 *
	 * @access	public
	 * @param	mixed	       The file name, or an array files.
	 * @param	array	       An array of extra attributes
	 * @param	string	       The asset group name
	 * @return	string|object  Rendered asset or current instance when adding to group
	 */
	public function js($scripts = array(), $attr = array(), $group = null, $raw = false)
	{
		static $temp_group = 2000000;

		if ( ! isset($group))
		{
			$render = $this->_auto_render;
			$group = $render ? (string) (++$temp_group) : '_default_';
		}
		else
		{
			$render = false;
		}

		$this->_parse_assets('js', $scripts, $attr, $group);

		if ($render)
		{
			return $this->render($group, $raw);
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Img
	 *
	 * Either adds the image to the group, or returns the image tag.
	 *
	 * @access	public
	 * @param	mixed	       The file name, or an array files.
	 * @param	array	       An array of extra attributes
	 * @param	string	       The asset group name
	 * @return	string|object  Rendered asset or current instance when adding to group
	 */
	public function img($images = array(), $attr = array(), $group = null)
	{
		static $temp_group = 3000000;

		if ( ! isset($group))
		{
			$render = $this->_auto_render;
			$group = $render ? (string) (++$temp_group) : '_default_';
		}
		else
		{
			$render = false;
		}

		$this->_parse_assets('img', $images, $attr, $group);

		if ($render)
		{
			return $this->render($group);
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Find File
	 *
	 * Locates a file in all the asset paths.
	 *
	 * @access	public
	 * @param	string	The filename to locate
	 * @param	string	The sub-folder to look in (optional)
	 * @return	mixed	Either the path to the file or false if not found
	 */
	public function find_file($file, $type, $folder = '')
	{
		foreach ($this->_asset_paths[$type] as $path)
		{
			empty($folder) or $folder = trim($folder, '/').'/';

			if (is_file($path.$folder.ltrim($file, '/')))
			{
				$file = $path.$folder.ltrim($file, '/');
				strpos($file, DOCROOT) === 0 and $file = substr($file, strlen(DOCROOT));

				return $file;
			}
		}

		return false;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Assets
	 *
	 * Pareses the assets and adds them to the group
	 *
	 * @access	private
	 * @param	string	The asset type
	 * @param	mixed	The file name, or an array files.
	 * @param	array	An array of extra attributes
	 * @param	string	The asset group name
	 * @return	string
	 */
	protected function _parse_assets($type, $assets, $attr, $group)
	{
		if ( ! is_array($assets))
		{
			$assets = array($assets);
		}

		foreach ($assets as $key => $asset)
		{
			$this->_groups[$group][] = array(
				'type'	=>	$type,
				'file'	=>	$asset,
				'attr'	=>	(array) $attr
			);
		}
	}

}
