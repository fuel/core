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
class Asset
{

	/**
	 * All the Asset instances
	 *
	 * @var  array
	 */
	protected static $instances = array();


	/**
	 * Default configuration values
	 *
	 * @var  array
	 */
	protected static $default_config = array(
		'paths' => array('assets/'),
		'img_dir' => 'img/',
		'js_dir' => 'js/',
		'css_dir' => 'css/',
		'folders' => array(
			'css' => array(),
			'js'  => array(),
			'img' => array(),
		),
		'url' => '/',
		'add_mtime' => true,
		'indent_level' => 1,
		'indent_with' => "\t",
		'auto_render' => true,
	);

	/**
	 * This is called automatically by the Autoloader.  It loads in the config
	 *
	 * @return  void
	 */
	public static function _init()
	{
		\Config::load('asset', true, false, true);
	}

	/**
	 * Acts as a Multiton.  Will return the requested instance, or will create
	 * a new named one if it does not exist.
	 *
	 * @param   string    $name    The instance name
	 * @param   array     $config  default config overrides
	 *
	 * @return  Theme
	 */
	public static function instance($name = null, array $config = array())
	{
		is_null($name) and $name = '_default_';
		array_key_exists($name, static::$instances) or static::$instances[$name] = static::forge($config);
		return static::$instances[$name];
	}

	/**
	 * Gets a new instance of the Asset class.
	 *
	 * @param   array  $config  default config overrides
	 * @return  Theme
	 */
	public static function forge(array $config = array())
	{
		return new \Asset_Instance(array_merge(static::$default_config, \Config::get('asset'), $config));
	}

	/**
	 * Adds the given path to the front of the asset paths array.  It adds paths
	 * in a way so that asset paths are used First in Last Out.
	 *
	 * @param   string  the path to add
	 * @return  void
	 */
	public static function add_path($path, $type = null)
	{
		static::instance()->add_path($path, $type);
	}

	/**
	 * Removes the given path from the asset paths array
	 *
	 * @param   string  the path to remove
	 * @return  void
	 */
	public static function remove_path($path, $type = null)
	{
		static::instance()->remove_path($path, $type);
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
	public static function render($group, $raw = false)
	{
		return static::instance()->render($group, $raw);
	}

	// --------------------------------------------------------------------

	/**
	 * CSS
	 *
	 * Either adds the stylesheet to the group, or returns the CSS tag.
	 *
	 * @access	public
	 * @param	mixed	The file name, or an array files.
	 * @param	array	An array of extra attributes
	 * @param	string	The asset group name
	 * @return	string
	 */
	public static function css($stylesheets = array(), $attr = array(), $group = NULL, $raw = false)
	{
		return static::instance()->css($stylesheets, $attr, $group, $raw);
	}

	// --------------------------------------------------------------------

	/**
	 * JS
	 *
	 * Either adds the javascript to the group, or returns the script tag.
	 *
	 * @access	public
	 * @param	mixed	The file name, or an array files.
	 * @param	array	An array of extra attributes
	 * @param	string	The asset group name
	 * @return	string
	 */
	public static function js($scripts = array(), $attr = array(), $group = NULL, $raw = false)
	{
		return static::instance()->js($scripts, $attr, $group, $raw);
	}

	// --------------------------------------------------------------------

	/**
	 * Img
	 *
	 * Either adds the image to the group, or returns the image tag.
	 *
	 * @access	public
	 * @param	mixed	The file name, or an array files.
	 * @param	array	An array of extra attributes
	 * @param	string	The asset group name
	 * @return	string
	 */
	public static function img($images = array(), $attr = array(), $group = NULL)
	{
		return static::instance()->img($images, $attr, $group);
	}
}
