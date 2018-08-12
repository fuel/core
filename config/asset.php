<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  [!] NOTICE
 * -----------------------------------------------------------------------------
 *
 *  If you need to make modifications to the default configuration,
 *  copy this file to your 'app/config' folder, and make them in there.
 *
 *  This will allow you to upgrade FuelPHP without losing your custom config.
 *
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Paths
	 * -------------------------------------------------------------------------
	 *
	 *  An array of paths that will be searched for assets.
	 *
	 *  Each path is a RELATIVE path from the speficied url:
	 *
	 *      array('assets/')
	 *
	 *  These MUST include the trailing slash ('/')
	 *
	 *  Paths specified here are suffixed with sub-folder paths defined below.
	 *
	 */

	'paths' => array('assets/'),

	/**
	 * -------------------------------------------------------------------------
	 *  Sub-folders
	 * -------------------------------------------------------------------------
	 *
	 *  Names for the 'img', 'js' and 'css' folders (inside the 'assets' path).
	 *
	 *  Examples:
	 *
	 *      img/
	 *      js/
	 *      css/
	 *
	 *  This MUST include the trailing slash ('/')
	 *
	 */

	'img_dir' => 'img/',
	'js_dir'  => 'js/',
	'css_dir' => 'css/',

	/**
	 * -------------------------------------------------------------------------
	 *  Folders
	 * -------------------------------------------------------------------------
	 *
	 *  You can also specify one or more per asset-type folders. You don't have
	 *  to specify all of them.
	 *
	 *  Each folder is a RELATIVE path from the URL speficied below:
	 *
	 *      array('css' => 'assets/css/')
	 *
	 *  These MUST include the trailing slash ('/')
	 *
	 *  Paths specified here are expected to contain the assets they point to
	 *
	 */

	'folders' => array(
		'css' => array(),
		'js'  => array(),
		'img' => array(),
	),

	/**
	 * -------------------------------------------------------------------------
	 *  URL
	 * -------------------------------------------------------------------------
	 *
	 *  URL to your Fuel root. Typically this will be your base URL:
	 *
	 *  Example:
	 *
	 *      'http://example.com/'
	 *
	 *  These MUST include the trailing slash ('/')
	 *
	 */

	'url' => \Config::get('base_url'),

	/**
	 * -------------------------------------------------------------------------
	 *  Timestamp
	 * -------------------------------------------------------------------------
	 *
	 *  Whether to append last modified timestamp to the URL.
	 *
	 *  This will aid in asset caching, and is recommended. The URL will looks
	 *  like this:
	 *
	 *      <link ... src="/assets/css/styles.css?1303443763" />
	 *
	 */

	'add_mtime' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Indent Level
	 * -------------------------------------------------------------------------
	 *
	 *  The amount of indents to prefix to the generated asset tag(s).
	 *
	 */

	'indent_level' => 1,

	/**
	 * -------------------------------------------------------------------------
	 *  Indent Character
	 * -------------------------------------------------------------------------
	 *
	 *  What to use for indenting.
	 *
	 */

	'indent_with' => "\t",

	/**
	 * -------------------------------------------------------------------------
	 *  Automatic Render
	 * -------------------------------------------------------------------------
	 *
	 *  What to do when an asset method is called without a group name.
	 *
	 *  If true, it will return the generated asset tag. If false, it will
	 *  add it to the default group.
	 *
	 */

	'auto_render' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Error Reports
	 * -------------------------------------------------------------------------
	 *
	 *  Set to true to prevent an exception from being thrown
	 *  when a file is not found. The asset will then be skipped.
	 */

	'fail_silently' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Always Resolve
	 * -------------------------------------------------------------------------
	 *
	 *  When set to true, the Asset class will always true to resolve
	 *  an asset URI to a local asset, even if the asset URL is an absolute URL,
	 *  for example one that points to another hostname.
	 *
	 */

	'always_resolve' => false,
);
