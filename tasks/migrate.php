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

namespace Fuel\Tasks;

/**
 * Migrate task
 *
 * Use this command line task to deploy and rollback changes.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Phil Sturgeon
 * @link		http://fuelphp.com/docs/general/migrations.html
 */

class Migrate
{

	/* set vars*/
	protected static $default = true;
	protected static $modules = array();
	protected static $packages = array();
	protected static $module_count = 0;
	protected static $package_count = 0;

	/**
	 * Sets vars by grabbing Cli options
	 */
	public function __construct()
	{
		//load config
		\Config::load('migrations', true);

		// get Cli options
		$modules = \Cli::option('modules');
		$packages = \Cli::option('packages');
		$default = \Cli::option('default');

		// if modules option set
		if (!empty($modules))
		{
			// if true - get all modules
			if ($modules === true)
			{
				// loop through module paths
				foreach (\Config::get('module_paths') as $path)
				{
					// get all modules that have files in the migration folder
					foreach (glob($path . '*/') as $m)
					{
						if (count(glob($m.\Config::get('migrations.folder').'/*.php')))
						{
							static::$modules[] = basename($m);
						}
					}
				}
			}
			// else do selected modules
			else
			{
				static::$modules = explode(',', $modules);
			}
		}

		// if packages option set
		if (!empty($packages))
		{
			// if true - get all packages
			if ($packages === true)
			{
				// get all packages that have files in the migration folder
				foreach (glob(PKGPATH . '*/') as $p)
				{
					if (count(glob($p.\Config::get('migrations.folder').'/*.php')))
					{
						static::$packages[] = basename($p);
					}
				}
			}
			// else do selected packages
			else
			{
				static::$packages = explode(',', $packages);
			}
		}

		if ( (!empty($packages) or !empty($modules)) and empty($default))
		{
			static::$default = false;
		}

		// set count
		static::$module_count = count(static::$modules);
		static::$package_count = count(static::$packages);
	}

	/**
	 * Catches requested method call and runs as needed
	 */
	public function __call($name, $args)
	{
		// set method name
		$name = '_'.$name;

		// run app (default) migrations if default is true
		if (static::$default)
		{
			static::$name('default', 'app');
		}

		// run modules if passed
		foreach (static::$modules as $module)
		{
			static::$name($module, 'module');
		}

		//run packages if passed
		foreach (static::$packages as $package)
		{
			static::$name($package, 'package');
		}
	}

	/**
	 * Migrates to the latest version unless -version is specified
	 *
	 * @param string
	 * @param string
	 */
	private static function _run($name, $type)
	{
		$current_version = \Config::get('migrations.version.'.$type.'.'.$name);

		if ( ! $current_version)
		{
			$current_version = 0;
		}

		// -v or --version
		$version = \Cli::option('v', \Cli::option('version'));

		// version is used as a flag, so show it
		if ($version === true)
		{
			\Cli::write('Currently on migration: ' . $current_version .' for '.$type.':'.$name.'.', 'green');
			return;
		}

		// If version has a value, make sure only 1 item was passed
		else if ( ! is_null($version) and static::$default + static::$module_count + static::$package_count > 1)
		{
			\Cli::write('Migration: version only excepts 1 item.');
			return;
		}

		// Not a lot of point in this
		else if ( ! is_null($version) and $version == $current_version)
		{
			\Cli::write('Migration: ' . $version .' already in use for '.$type.':'.$name.'.');
			return;
		}

		$run = false;

		// Specific version
		if (is_numeric($version) and $version >= 0)
		{
			if (\Migrate::version($version, $name, $type) === false)
			{
				\Cli::write('Migration ' . $version .' could not be found for '.$type.':'.$name.'.');
			}

			else
			{
				static::_update_version($version, $name, $type);
				\Cli::write('Migrated '.$type.':'.$name.' version: ' . $version .'.', 'green');
			}
		}

		// Just go to the latest
		else
		{
			if (($result = \Migrate::latest($name, $type)) === false)
			{
				\Cli::write('Already on latest migration for '.$type.':'.$name.'.');
			}

			else
			{
				static::_update_version($result, $name, $type);
				\Cli::write('Migrated '.$type.':'.$name.' to latest version: ' . $result .'.', 'green');
			}
		}

	}

	/**
	 * Migrates item up 1 version
	 *
	 * @param string
	 * @param string
	 */
	private static function _up($name, $type)
	{
		// add 1 to the version #
		$version = \Config::get('migrations.version.'.$type.'.'.$name) + 1;

		// if migration successful
		if ($foo = \Migrate::version($version, $name, $type))
		{
			// update config and output a notice
			static::_update_version($version, $name, $type);
			\Cli::write('Migrated to version: ' . $version .' for '.$type.':'.$name.'.', 'green');
		}
		else
		{
			// already on last/highest migration
			\Cli::write('Already on latest migration for '.$type.':'.$name.'.');
		}
	}

	/**
	 * Migrates item down 1 version
	 *
	 * @param string
	 * @param string
	 */
	private static function _down($name, $type)
	{
		// if version - 1 is less than 0
		if (($version = \Config::get('migrations.version.'.$type.'.'.$name) - 1) < 0)
		{
			// already on first/lowest migration
			\Cli::write('You are already on the first migration for '.$type.':'.$name.'.');
			return;
		}

		if (\Migrate::version($version, $name, $type) !== false)
		{
			// update config and output a notice to console
			static::_update_version($version, $name, $type);
			\Cli::write('Migrated to version: ' . $version .' for '.$type.':'.$name.'.', 'green');
		}
		else
		{
			// migration doesn't exist
			\Cli::write('Migration '.$version.' does not exist for '.$type.':'.$name.'. How did you get here?');
		}
	}

	/**
	 * Migrates item to current config verision
	 *
	 * @param string
	 * @param string
	 */
	private static function _current($name, $type)
	{
		$version = \Migrate::current($name, $type);

		// if version is a number
		if(is_numeric($version))
		{
			// show what version the item migrated to
			\Cli::write('Migrated to version: '.$version.' for '.$type.':'.$name.'.');
		}
		else
		{
			// migration is already on current version
			\Cli::write('Already on current migration version for '.$type.':'.$name.'.');
		}
	}

	/**
	 * Updates version in migrations config
	 *
	 * @param int
	 * @param string
	 * @param string
	 */
	private static function _update_version($version, $name, $type)
	{
		// if migrations config doesn't exist in app/config
		if ( ! file_exists($path = APPPATH.'config'.DS.'migrations.php'))
		{
			// make sure it exists in core/config and copy to app/config
			if (file_exists($core_path = COREPATH.'config'.DS.'migrations.php'))
			{
				$contents = file_get_contents($core_path);
			}
			else
			{
				\Cli::write('Config file core/config/migrations.php is missing.');
				exit;
			}

			// create the file in app/config folder
			file_put_contents($path, $contents);
		}

		// set config version
		\Config::set('migrations.version.'.$type.'.'.$name, (int) $version);

		// save the config;
		\Config::save('migrations', 'migrations');
	}

	/**
	 * Shows basic help instructions for using migrate in oil
	 */
	public static function help()
	{
		echo <<<HELP
Usage:
    php oil refine migrate [--version=X]

Fuel options:
    -v, [--version]  # Migrate to a specific version ( only 1 item at a time)

    # The following disable default migrations unless you add --default to the command
    --default # re-enables default migration
    --modules # Migrates all modules
    --modules=item1,item2 # Migrates specific modules
    --packages # Migrates all packages
    --packages=item1,item2 # Migrates specific modules

Description:
    The migrate task can run migrations. You can go up, down or by default go to the current migration marked in the config file.

Examples:
    php oil r migrate
    php oil r migrate:current
    php oil r migrate:up
    php oil r migrate:down
    php oil r migrate --version=10
    php oil r migrate --modules --packages --default
    php oil r migrate:up --modules=module1,module2 --packages=package1
    php oil r migrate --module=module1 -v=3

HELP;

	}

}

