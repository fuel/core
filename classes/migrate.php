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
 * Migrate Class
 *
 * @package		Fuel
 * @category	Migrations
 * @link		http://fuelphp.com/docs/classes/migrate.html
 */
class Migrate
{
	public static $version = array();

	protected static $prefix = 'Fuel\\Migrations\\';

	protected static $table = 'migration';

	protected static $table_definition = array(
		'name' => array('type' => 'varchar', 'constraint' => 50),
		'type' => array('type' => 'varchar', 'constraint' => 25),
		'version' => array('type' => 'int', 'constraint' => 11, 'null' => false, 'default' => 0),
	);

	/**
	 * Loads in the migrations config file, checks to see if the migrations
	 * table is set in the database (if not, create it), and reads in all of
	 * the versions from the DB.
	 *
	 * @return  void
	 */
	public static function _init()
	{
		logger(Fuel::L_DEBUG, 'Migrate class initialized');

		\Config::load('migrations', true);

		static::$table = \Config::get('migrations.table', static::$table);

		// installs or upgrades table
		static::table_check();

		//get all versions from db
		$migrations = \DB::select()
			->from(static::$table)
			->execute()
			->as_array();

		foreach ($migrations as $migration)
		{
			static::$version[$migration['type']][$migration['name']] = (int) $migration['version'];
		}
	}

	/**
	 * Migrates to the latest schema version.
	 *
	 * @param   string  Name of the package, module or app
	 * @param   string  Type of migration (package, module or app)
	 * @return	mixed
	 */
	public static function latest($name = 'default', $type = 'app')
	{
		return static::version(null, $name, $type);
	}

	/**
	 * Migrates to the current schema version stored in the migrations config.
	 *
	 * @param   string  Name of the package, module or app
	 * @param   string  Type of migration (package, module or app)
	 * @return	mixed
	 */
	public static function current($name = 'default', $type = 'app')
	{
		return static::version(\Config::get('migrations.version.'.$type.'.'.$name), $name, $type);
	}

	/**
	 * Migrate to a specific schema version.  If the version given is null
	 * it will migrate to the latest.
	 *
	 * @param   int|null  Version to migrate to (null for latest)
	 * @param   string    Name of the package, module or app
	 * @param   string    Type of migration (package, module or app)
	 * @return	mixed
	 */
	public static function version($version, $name = 'default', $type = 'app')
	{
		// if version isn't set
		if ( ! isset(static::$version[$type][$name]))
		{
			// insert into db
			\DB::insert(static::$table)
			->set(array(
				'name' => $name,
				'type' => $type,
				'version' => 0,
			))
			->execute();

			// set verstion to 0
			static::$version[$type][$name] = 0;
		}

		$migrations = static::find_migrations($name, $type, static::$version[$type][$name], $version);

		if ($version === null and ! empty($migrations))
		{
			$keys = array_keys($migrations);
			$version = end($keys);
		}

		// return false if current version equals requested version
		if (empty($migrations) or static::$version[$type][$name] === $version)
		{
			return false;
		}

		// set vars for loop
		$start = static::$version[$type][$name];
		$stop = $version;

		// modify loop vars and add step
		$method = $version > static::$version[$type][$name] ? 'up' : 'down';

		$runnable = array();

		// We now prepare to actually DO the migrations
		// But first let's make sure that everything is the way it should be
		foreach ($migrations as $ver => $path)
		{
			$file = basename($path);

			// Filename validations
			if (preg_match('/^\d+_(\w+).php$/', $file, $match))
			{
				$class_name = ucfirst(strtolower($match[1]));

				include $path;
				$class = static::$prefix.$class_name;

				if ( ! class_exists($class, false))
				{
					throw new FuelException(sprintf('Migration "%s" does not contain expected class "%s"', $file, $class));
				}

				if ( ! is_callable(array($class, 'up')) || ! is_callable(array($class, 'down')))
				{
					throw new FuelException(sprintf('Migration class "%s" must include public methods "up" and "down"', $name));
				}

				$runnable_migrations[$ver] = $class;
			}
			else
			{
				throw new FuelException(sprintf('Invalid Migration filename "%s"', $file));
			}
		}

		// Loop through the runnable migrations and run them
		foreach ($runnable_migrations as $ver => $class)
		{
			logger(Fuel::L_INFO, 'Migrating to: '.$ver);
			call_user_func(array(new $class, $method));
			static::_update_schema_version(static::$version[$type][$name], $ver, $name, $type);
			static::$version[$type][$name] = $ver;
		}

		logger(Fuel::L_INFO, 'Migrated to '.$ver.' successfully.');

		return static::$version[$type][$name];
	}

	/**
	 * Gets all of the migrations from the start version to the end version.
	 *
	 * @param   string    Name of the package, module or app
	 * @param   string    Type of migration (package, module or app)
	 * @param   int       Starting version
	 * @param   int|null  Ending version (null for latest)
	 * @return	array
	 */
	protected static function find_migrations($name, $type, $start_version, $end_version = null)
	{
		// Load all *_*.php files in the migrations path
		$method = '_find_'.$type;
		$files = static::$method($name);

		// Keep the full paths for use in the return array
		$full_paths = $files;

		// Get the basename of all migrations
		$files = array_map('basename', $files);

		// Ensure we are going the right way
		if ($end_version === null)
		{
			$direction = 'up';
		}
		else
		{
			$direction = $start_version > $end_version ? 'down' : 'up';
		}

		if ($direction === 'down')
		{
			$temp_version = $start_version;
			$start_version = $end_version;
			$end_version = $temp_version;
		}

		$migrations = array();
		foreach ($files as $index => $file)
		{
			preg_match('/^(\d+)_(\w+).php$/', $file, $matches);
			$version = intval($matches[1]);
			if ($version > $start_version)
			{
				if ($end_version === null or $version <= $end_version)
				{
					$direction === 'down' and --$version;
					$migrations[$version] = $full_paths[$index];
				}
			}
		}
		ksort($migrations, SORT_NUMERIC);

		if ($direction === 'down')
		{
			$migrations = array_reverse($migrations, true);
		}

		return $migrations;
	}

	/**
	 * Updates the schema version in the database
	 *
	 * @param   int     Old schema version
	 * @param   int     New schema version
	 * @param   string  Name of the package, module or app
	 * @param   string  Type of migration (package, module or app)
	 * @return	void
	 */
	private static function _update_schema_version($old_version, $version, $name, $type = '')
	{
		\DB::update(static::$table)
			->set(array(
				'version' => (int) $version
			))
			->where('version', (int) $old_version)
			->where('name', $name)
			->where('type', $type)
			->execute();
	}

	/**
	 * Finds migrations for the given app
	 *
	 * @param   string    Name of the app (not used at the moment)
	 * @return  array
	 */
	private static function _find_app($name = null)
	{
		return glob(APPPATH.\Config::get('migrations.folder').'*_*.php');
	}

	/**
	 * Finds migrations for the given module (or all if name is not given)
	 *
	 * @param   string    Name of the module
	 * @return  array
	 */
	private static function _find_module($name = null)
	{
		if ($name)
		{
			// find a module
			foreach (\Config::get('module_paths') as $m)
			{
				$files = glob($m .$name.'/'.\Config::get('migrations.folder').'*_*.php');
			}
		}
		else
		{
			// find all modules
			foreach (\Config::get('module_paths') as $m)
			{
				$files = glob($m.'*/'.\Config::get('migrations.folder').'*_*.php');
			}
		}

		return $files;
	}

	/**
	 * Finds migrations for the given package (or all if name is not given)
	 *
	 * @param   string    Name of the package
	 * @return  array
	 */
	private static function _find_package($name = null)
	{
		if ($name)
		{
			// find a package
			$files = glob(PKGPATH.$name.'/'.\Config::get('migrations.folder').'*_*.php');
		}
		else
		{
			// find all modules
			$files = glob(PKGPATH.'*/'.\Config::get('migrations.folder').'*_*.php');
		}

		return $files;
	}

	/**
	 * Installs or upgrades migration table
	 *
	 * @return  void
	 * @deprecated	Remove upgrade check in 1.2
	 */
	private static function table_check()
	{
		// if table does not exist
		if ( ! \DBUtil::table_exists(static::$table))
		{
			// create table
			\DBUtil::create_table(static::$table, static::$table_definition);
		}
		elseif ( ! \DBUtil::field_exists(static::$table, array('name', 'type')))
		{
			$current = \DB::select('current')->from(static::$table)->limit(1)->execute()->get('current');

			\DBUtil::drop_table(static::$table);
			\DBUtil::create_table(static::$table, static::$table_definition);

			\DB::insert(static::$table)->set(array(
				'name' => 'default',
				'type' => 'app',
				'version' => (int) $current
			))->execute();
		}
	}
}

