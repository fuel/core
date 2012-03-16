<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Tasks;

/**
 * migrate task
 *
 * use this command line task to deploy and rollback changes
 */
class Migrate
{
	/**
	 * @var	boolean	if true, migrate the app
	 */
	protected static $default = true;

	/**
	 * @var	array	list of modules to migrate
	 */
	protected static $modules = array();

	/**
	 * @var	array	list of packages to migrate
	 */
	protected static $packages = array();

	/**
	 * @var	int	number of modules migrated
	 */
	protected static $module_count = 0;

	/**
	 * @var	int	number of packages migrated
	 */
	protected static $package_count = 0;

	/**
	 * sets the properties by grabbing Cli options
	 */
	public function __construct()
	{
		// load config
		\Config::load('migrations', true);

		// get Cli options
		$modules = \Cli::option('modules', \Cli::option('m'));
		$packages = \Cli::option('packages', \Cli::option('p'));
		$default = \Cli::option('default');
		$all = \Cli::option('all');

		if ($all)
		{
			$modules = true;
			$packages = true;
			$default = true;
		}

		// if modules option set
		if ( ! empty($modules))
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
		if ( ! empty($packages))
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

		// if packages or modules are specified, and the app isn't, disable app migrations
		if ( ( ! empty($packages) or ! empty($modules)) and empty($default))
		{
			static::$default = false;
		}

		// set the module and package count
		static::$module_count = count(static::$modules);
		static::$package_count = count(static::$packages);
	}

	/**
	 * catches requested method call and runs as needed
	 *
	 * @param string	name of the method to run
	 * @param string	any additional method arguments (not used here!)
	 */
	public function __call($name, $args)
	{
		// set method name
		$name = '_'.$name;

		// make sure the called name exists
		if ( ! method_exists(get_called_class(), $name))
		{
			throw new \FuelException('Called method Migrate::'.$name.'() does not exist.');
		}

		// run app (default) migrations if default is true
		if (static::$default)
		{
			static::$name('default', 'app');
		}

		// run migrations on all specified modules
		foreach (static::$modules as $module)
		{
			static::$name($module, 'module');
		}

		// run migrations on all specified packages
		foreach (static::$packages as $package)
		{
			static::$name($package, 'package');
		}
	}

	/**
	 * migrates to the latest version unless -version is specified
	 *
	 * @param string	name of the type (in case of app, it's 'default')
	 * @param string	type (app, module or package)
	 * @param string	direction of migration (up or down)
	 */
	private static function _run($name, $type, $direction = 'up')
	{
		$current = \Config::get('migrations.version.'.$type.'.'.$name, array());

		// -v or --version
		$version = \Cli::option('v', \Cli::option('version'));

		// version is used as a flag, so show it
		if ($version === true)
		{
			\Cli::write('Currently installed migrations for '.$type.':'.$name.':', 'green');
			foreach ($current as $version)
			{
				\Cli::write('- '.$version);
			}
			return;
		}

		if (is_numeric($version) and $version >= 0)
		{
			// specific timestamp number
			$migrations = \Migrate::version($version, $name, $type, $direction);
		}
		else
		{
			// just go to the latest
			$migrations = \Migrate::latest($name, $type);
		}

		if ($migrations)
		{
			if ($direction == 'up')
			{
				\Cli::write('Newly installed migrations for '.$type.':'.$name.':', 'green');
			}
			else
			{
				\Cli::write('Migrations reverted for '.$type.':'.$name.':', 'green');
			}
			foreach ($migrations as $migration)
			{
				\Cli::write('- '.$migration, 'green');
			}
		}
		else
		{
			if (is_numeric($version) and $version >= 0)
			{
				if ($direction == 'up')
				{
					\Cli::write('No new migrations were found for '.$type.':'.$name.' before version '.$version.'.');
				}
				else
				{
					\Cli::write('No migrations were reverted for '.$type.':'.$name.' after version '.$version.'.');
				}
			}
			else
			{
				\Cli::write('Already on the latest migration for '.$type.':'.$name.'.');
			}
		}
	}

	/**
	 * migrates item to current config version
	 *
	 * @param string	name of the type (in case of app, it's 'default')
	 * @param string	type (app, module or package)
	 */
	private static function _current($name, $type)
	{
		$migrations = \Migrate::current($name, $type);

		if ($migrations)
		{
			\Cli::write('Newly installed migrations for '.$type.':'.$name.':', 'green');
			foreach ($migrations as $migration)
			{
				\Cli::write('- '.$migration, 'green');
			}
		}
		else
		{
			// migration is already on current version
			\Cli::write('Already on current migration version for '.$type.':'.$name.'.');
		}
	}

	/**
	 * migrates item up to the given version
	 *
	 * @param string
	 * @param string
	 */
	private static function _up($name, $type)
	{
		// -v or --version
		$version = \Cli::option('v', \Cli::option('version'));

		if (is_null($version))
		{
			\Cli::write('As of version 1.2, "up" requires a version to migrate up to.', 'red');
		}

		return static::_run($name, $type);
	}

	/**
	 * migrates item down to the given version
	 *
	 * @param string
	 * @param string
	 */
	private static function _down($name, $type)
	{
		// -v or --version
		$version = \Cli::option('v', \Cli::option('version'));

		if (is_null($version))
		{
			\Cli::write('As of version 1.2, "down" requires a version to migrate down to.', 'red');
		}

		return static::_run($name, $type, 'down');
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
    -v, [--version]  # Migrate to a specific version timestamp

    # The following disable default migrations unless you add --default to the command
    --default # re-enables default migration
    --modules -m # Migrates all modules
    --modules=item1,item2 -m=item1,item2 # Migrates specific modules
    --packages -p # Migrates all packages
    --packages=item1,item2 -p=item1,item2 # Migrates specific modules
    --all # shortcut for --modules --packages --default

Description:
    The migrate task can run migrations. You can migrate up or down to a specific timestamp,
    and select exactly what part of the application your want to have migrated.

Examples:
    php oil r migrate
    php oil r migrate:current
    php oil r migrate --version=1331553600
    php oil r migrate --modules --packages --default
    php oil r migrate --modules=module1,module2 --packages=package1,package2
    php oil r migrate --module=module1 -v=1331553600
    php oil r migrate --all

HELP;

	}

}
