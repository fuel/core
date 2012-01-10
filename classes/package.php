<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * This exception is thrown when a package cannot be found.
 *
 * @package     Core
 * @subpackage  Packages
 */
class PackageNotFoundException extends \FuelException { }

/**
 * Handles all the loading, unloading and management of packages.
 *
 * @package     Core
 * @subpackage  Packages
 */
class Package
{

	/**
	 * @var  array  $packages  Holds all the loaded package information.
	 */
	protected static $packages = array();

	/**
	 * Loads the given package.  If a path is not given, then PKGPATH is used.
	 * It also accepts an array of packages as the first parameter.
	 *
	 * @param   string|array  $package  The package name or array of packages.
	 * @param   string|null   $path     The path to the package
	 * @return  bool  True on success
	 * @throws  PackageNotFoundException
	 */
	public static function load($package, $path = null)
	{
		if (is_array($package))
		{
			foreach ($package as $pkg)
			{
				$path = null;
				if (is_array($pkg))
				{
					list($pkg, $path) = $pkg;
				}
				static::load($pkg, $path);
			}
			return false;
		}


		if (static::loaded($package))
		{
			return;
		}

		// Load it from PKGPATH if no path was given.
		if ($path === null)
		{
			$path = PKGPATH.$package.DS;
		}

		if ( ! is_dir($path))
		{
			throw new \PackageNotFoundException("Package '$package' could not be found at '".\Fuel::clean_path($path)."'");
		}

		\Finder::instance()->add_path($path, 1);
		\Fuel::load($path.'bootstrap.php');
		static::$packages[$package] = $path;

		return true;
	}

	/**
	 * Unloads a package from the stack.
	 *
	 * @param   string  $pacakge  The package name
	 * @return  void
	 */
	public static function unload($package)
	{
		\Finder::instance()->remove_path(static::$packages[$package]);
		unset(static::$packages[$package]);
	}

	/**
	 * Checks if the given package is loaded, if no package is given then
	 * all loaded packages are returned.
	 *
	 * @param   string|null  $package  The package name or null
	 * @return  bool|array  Whether the package is loaded, or all packages
	 */
	public static function loaded($package = null)
	{
		if ($package === null)
		{
			return static::$packages;
		}

		return array_key_exists($package, static::$packages);
	}

}
