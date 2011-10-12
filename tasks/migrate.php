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

	protected static $default = true;
	protected static $modules = array();
	protected static $packages = array();
	protected static $module_count = 0;
	protected static $package_count = 0;

	public function __construct()
	{
		//load config
		\Config::load('migrations', true);

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

	public static function run()
	{
		// run default migrations if default is true
		if (static::$default)
		{
			static::_run('default');
		}

		// run modules if passed
		if (count(static::$modules))
		{
			foreach (static::$modules as $module)
			{
				static::_run($module, 'module');
			}
		}

		//run packages if passed
		if (count(static::$packages))
		{
			foreach (static::$packages as $package)
			{
				static::_run($package, 'package');
			}
		}

	}

	private static function _run($name, $type = null)
	{
		if ($type)
		{
			$current_version = \Config::get('migrations.version.'.$type.'.'.$name);
		}
		else
		{
			$current_version = \Config::get('migrations.version.'.$name);
		}

		if ( ! $current_version)
		{
			$current_version = 0;
		}

		// -v or --version
		$version = \Cli::option('v', \Cli::option('version'));

		// version is used as a flag, so show it
		if ($version === true)
		{
			\Cli::write('Currently on migration: ' . $current_version .'.', 'green');
			return;
		}

		// If version has a value, make sure only 1 item was passed
		else if ( ! is_null($version) and static::$default + static::$module_count + static::$package_count > 1)
		{
			throw new \Oil\Exception('Migration: version only excepts 1 item.');
			return;
		}

		// Not a lot of point in this
		else if ( ! is_null($version) and $version == $current_version)
		{
			throw new \Oil\Exception('Migration: ' . $version .' already in use.');
			return;
		}

		$run = false;

		// Specific version
		if (is_numeric($version) and $version >= 0)
		{
			if (\Migrate::version($version, $name, $type) === false)
			{
				throw new \Oil\Exception('Migration ' . $version .' could not be found.');
			}

			else
			{
				static::_update_version($version, 'default');
				\Cli::write('Migrated to version: ' . $version .'.', 'green');
			}
		}

		// Just go to the latest
		else
		{
			if (($result = \Migrate::latest($name, $type)) === false)
			{
				throw new \Oil\Exception("Could not migrate to latest version, still using {$current_version}.");
			}

			else
			{
				static::_update_version($result, 'default');
				\Cli::write('Migrated to latest version: ' . $result .'.', 'green');
			}
		}

	}

	public static function up()
	{
		$version = \Config::get('migrations.version.default') + 1;

		if ($foo = \Migrate::version($version))
		{
			static::_update_version($version, 'default');
			\Cli::write('Migrated to version: ' . $version .'.', 'green');
		}
		else
		{
			throw new \Oil\Exception('Already on latest migration.');
		}
	}

	public static function down()
	{
		if (($version = \Config::get('migrations.version.default') - 1) < 0)
		{
			throw new \Oil\Exception('You are already on the first migration.');
		}

		if (\Migrate::version($version) !== false)
		{
			static::_update_version($version, 'default');
			\Cli::write("Migrated to version: {$version}.", 'green');
		}
		else
		{
			throw new \Oil\Exception("Migration {$version} does not exist. How did you get here?");
		}
	}

	private static function _update_version($version, $name, $type = null)
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
				throw new Exception('Config file core/config/migrations.php is missing.');
				exit;
			}

			file_put_contents($path, $contents);
		}

		// set config version
		if ($type)
		{
			\Config::set('migrations.version.'.$type.'.'.$name, $version);
		}
		else
		{
			\Config::set('migrations.version.'.$name, $version);
		}

		\Config::save('migrations', 'migrations');

	}

/*
	private static function _update_version($version)
	{
		if (file_exists($path = APPPATH.'config'.DS.'migrations.php'))
		{
			$contents = file_get_contents($path);
		}
		elseif (file_exists($core_path = COREPATH.'config'.DS.'migrations.php'))
		{
			$contents = file_get_contents($core_path);
		}
		else
		{
			throw new Exception('Config file core/config/migrations.php is missing.');
			exit;
		}

		$contents = preg_replace("#('default'[ \t]+=>)[ \t]+([0-9]+),#i", "$1 $version,", $contents);

		file_put_contents($path, $contents);
	}
*/
	public static function current()
	{
		\Migrate::current();
	}

	public static function help()
	{
		echo <<<HELP
Usage:
    php oil refine migrate [--version=X]

Fuel options:
    -v, [--version]  # Migrate to a specific version

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

HELP;

	}

}

