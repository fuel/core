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

namespace Fuel\Core;

/**
 * Migrate Class
 *
 * @package		Fuel
 * @category	Migrations
 * @link		http://docs.fuelphp.com/classes/migrate.html
 */
class Migrate
{
	protected static $migrations = array();

	protected static $prefix = 'Fuel\\Migrations\\';

	protected static $table = 'migration';

	protected static $table_definition = array(
		'type' => array('type' => 'varchar', 'constraint' => 25),
		'name' => array('type' => 'varchar', 'constraint' => 50),
		'migration' => array('type' => 'varchar', 'constraint' => 100, 'null' => false, 'default' => ''),
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
		logger(\Fuel::L_DEBUG, 'Migrate class initialized');

		// load the migrations config
		\Config::load('migrations', true);

		// set the name of the table containing the installed migrations
		static::$table = \Config::get('migrations.table', static::$table);

		// installs or upgrades the migration table to the current release
		static::table_check();

		//get all installed migrations from db
		$migrations = \DB::select()
			->from(static::$table)
			->order_by('type', 'ASC')
			->order_by('name', 'ASC')
			->execute()
			->as_array();

		// convert the db migrations to match the config file structure
		foreach($migrations as $migration)
		{
			isset(static::$migrations[$migration['type']]) or static::$migrations[$migration['type']] = array();
			isset(static::$migrations[$migration['type']][$migration['name']]) or static::$migrations[$migration['type']][$migration['name']] = array();
			static::$migrations[$migration['type']][$migration['name']][] = $migration['migration'];
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
		// get the current version from the config
		$current = \Config::get('migrations.version.'.$type.'.'.$name);

		// any migrations defined?
		if ( ! empty($current))
		{
			// get the timestamp of the last installed migration
			if (preg_match('/^(\w+?)_(\w+)$/', end($current), $match))
			{
				// and run till that timestamp
				return static::version($match[1], $name, $type);
			}
		}

		return false;
	}

	/**
	 * Migrate to a specific schema version.  If the version given is null
	 * it will migrate to the latest.
	 *
	 * @param   int|null  Version to migrate to (null for latest)
	 * @param   string    Name of the package, module or app
	 * @param   string    Type of migration (package, module or app)
	 * @param   string    direction of the migration (up or down)
	 * @return	mixed
	 */
	public static function version($version, $name = 'default', $type = 'app', $method = 'up')
	{
		// make sure the requested migrations entry exists
		isset(static::$migrations[$type][$name]) or static::$migrations[$type][$name] = array();

		// get the current version, if none present, use ' ' (we assume it's always the lowest)
		$start = end(static::$migrations[$type][$name]);
		$start === false and $start = ' ';

		// get all migrations in scope that still need to run
		$result = static::find_migrations($name, $type, $start, $version);

		// get the direction of migration and the actual migration files found
		list($direction, $migrations) = $result;

		// if no migrations were found, or the direction is wrong, bail out
		if (empty($migrations) or $method != $direction)
		{
			return false;
		}

		// we now prepare to actually DO the migrations
		// but first let's make sure that everything is the way it should be
		foreach ($migrations as $ver => $path)
		{
			// get the migration filename
			$file = basename($path);

			// filename validations
			if (preg_match('/^\w+?_(\w+).php$/', $file, $match))
			{
				$class_name = ucfirst(strtolower($match[1]));

				include $path;
				$class = static::$prefix.$class_name;

				if ( ! class_exists($class, false))
				{
					throw new \FuelException(sprintf('Migration "%s" does not contain expected class "%s"', $file, $class));
				}

				if ( ! is_callable(array($class, 'up')) || ! is_callable(array($class, 'down')))
				{
					throw new \FuelException(sprintf('Migration class "%s" must include public methods "up" and "down"', $name));
				}

				$runnable_migrations[$ver] = array('file' => $file, 'class' => $class);
			}
			else
			{
				throw new \FuelException(sprintf('Invalid Migration filename "%s"', $file));
			}
		}

		// to log migrations after run
		$migrations = array();

		// Loop through the runnable migrations and run them
		foreach ($runnable_migrations as $ver => $migration)
		{
			logger(\Fuel::L_INFO, 'Migrating to: '.$ver);
			$class = $migration['class'];
			call_user_func(array(new $class, $method));
			static::_update_schema_version($type, $name, $migration['file'], $direction);
			$migrations[] = $migration['file'];
		}

		// update the config file
		sort(static::$migrations[$type][$name], SORT_NUMERIC);
		\Config::set('migrations.version.'.$type.'.'.$name, static::$migrations[$type][$name]);
		\Config::save('migrations', 'migrations');

		logger(\Fuel::L_INFO, 'Migrated '.$type.':'.$name.' to '.$migration['file'].' successfully.');

		return $migrations;
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
		// load all *_*.php files in the migrations path
		$method = '_find_'.$type;
		$files = static::$method($name);

		if ( ! $files)
		{
			return array();
		}

		// keep the full paths for use in the return array
		$full_paths = $files;

		// get the basename of all migrations
		$files = array_map('basename', $files);

		// strip leading zeroes to make sure the comparison works
		$start_version = ltrim($start_version, '0');

		// make sure we have a start value at all
		empty($start_version) and $start_version = ' ';

		// ensure we are going the right way
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
			preg_match('/^(\w+)_(\w+).php$/', $file, $matches);
			$version = intval($matches[1]);
			if ($version > $start_version)
			{
				if ($end_version === null or $version <= $end_version)
				{
					$migrations[$version] = $full_paths[$index];
				}
			}
		}
		ksort($migrations, SORT_NUMERIC);

		if ($direction === 'down')
		{
			$keys = array_keys($migrations);

			$replacement = $keys;
			array_unshift($replacement, $start_version);

			for ($i=0; $i < count($keys); $i++)
			{
				$keys[$i] = $replacement[$i];
			}

			empty($keys) or $migrations = array_combine($keys, $migrations);
			$migrations = array_reverse($migrations, true);
		}

		return array($direction, $migrations);
	}

	/**
	 * Updates the schema version in the database
	 *
	 * @param   string  Type of migration (package, module or app)
	 * @param   string  Name of the package, module or app
	 * @param   string  Name of the migration file just run
	 * @return	void
	 */
	private static function _update_schema_version($type = '', $name, $file, $direction = 'up')
	{
		if ($direction == 'up')
		{
			// add the migration just run
			\DB::insert(static::$table)->set(array(
				'name' => $name,
				'type' => $type,
				'migration' => $file,
			))->execute();

			// and add the file to the list of run migrations
			static::$migrations[$type][$name][] = $file;
		}
		else
		{
			// remove the migration just run
			\DB::delete(static::$table)
				->where('name', $name)
				->where('type', $type)
				->where('migration', $file)
			->execute();

			// and remove the file from the list of run migrations
			if (($key = array_search($file, static::$migrations[$type][$name])) !== false)
			{
				unset(static::$migrations[$type][$name][$key]);
			}
		}
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
	 * @deprecated	Remove upgrade check in 1.3
	 */
	private static function table_check()
	{
		// if table does not exist
		if ( ! \DBUtil::table_exists(static::$table))
		{
			// create table
			\DBUtil::create_table(static::$table, static::$table_definition);
		}

		// check if a table upgrade is needed
		elseif ( ! \DBUtil::field_exists(static::$table, array('migration')))
		{
			// get the current migration status
			$current = \DB::select()->from(static::$table)->order_by('type', 'ASC')->order_by('name', 'ASC')->execute()->as_array();

			// drop the existing table, and recreate it in the new layout
			\DBUtil::drop_table(static::$table);
			\DBUtil::create_table(static::$table, static::$table_definition);

			// check if we had a current migration status
			if ( ! empty($current))
			{
				// do we need to migrate from a v1.0 migration environment?
				if (isset($current[0]['current']))
				{
					// convert the current result into a v1.1. migration environment structure
					$current = array(0 => array('name' => 'default', 'type' => 'app', 'version' => $current[0]['current']));
				}

				// build a new config structure
				$configs = array();

				// convert the v1.1 structure to the v1.2 structure
				foreach ($current as $migration)
				{
					// find the migrations for this entry
					$migrations = static::find_migrations($migration['name'], $migration['type'], 0, $migration['version']);

					// don't care about the direction returned as well...
					$migrations = isset($migrations[1]) ? $migrations[1] : array();

					// array to keep track of the migrations already run
					$config = array();

					// add the individual migrations found
					foreach ($migrations as $file)
					{
						$file = pathinfo($file);

						// add this migration to the table
						\DB::insert(static::$table)->set(array(
							'name' => $migration['name'],
							'type' => $migration['type'],
							'migration' => $file['filename'],
						))->execute();

						// and to the config
						$config[] = $file['filename'];
					}

					// create a config entry for this name and type if needed
					isset($configs[$migration['type']]) or $configs[$migration['type']] = array();
					$configs[$migration['type']][$migration['name']] = $config;
				}

				// write the updated migrations config back
				\Config::set('migrations.version', $configs);
				\Config::save('migrations', 'migrations');
			}
		}
	}
}
