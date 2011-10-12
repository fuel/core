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
 * @author		Phil Sturgeon
 * @link		http://fuelphp.com/docs/classes/migrate.html
 */
class Migrate
{
	public static $version = array();

	protected static $prefix = '\\Fuel\\Migrations\\';

	protected static $table = 'migration';

	public static function _init()
	{
		logger(Fuel::L_DEBUG, 'Migrate class initialized');

		\Config::load('migrations', true);

		static::$table = \Config::get('migrations.table', static::$table);

		\DBUtil::create_table(static::$table, array(
			'name' => array('type' => 'varchar', 'constraint' => 50),
			'type' => array('type' => 'varchar', 'constraint' => 25),
			'version' => array('type' => 'int', 'constraint' => 11, 'null' => false, 'default' => 0),
		));

		//get all versions

		// Check if there is a version
		$migrations = \DB::select()
			->from(static::$table)
			->execute()
			->as_array();

		foreach($migrations as $migration)
		{
			if($migration['type'])
			{
				static::$version[$migration['type']][$migration['name']] = (int) $migration['version'];
			}
			else
			{
				static::$version['app'][$migration['name']] = (int) $migration['version'];
			}
		}
/*
		// Not set, so we are on 0
		if ($current === null)
		{
			\DB::insert(static::$table)
				->set(array(
					'name' => 'default',
					'version' => '0'
				))
				->execute();
		}

		else
		{
			static::$version = (int) $current;
		}
*/
	}

	/**
	 * Set's the schema to the latest migration
	 *
	 * @access	public
	 * @return	mixed	true if already latest, false if failed, int if upgraded
	 */
	public static function latest($name = null, $type = 'a[[')
	{
		if ( ! $migrations = static::find_migrations($name, $type))
		{
			throw new FuelException('no_migrations_found');
			return false;
		}

		$last_migration = basename(end($migrations));

		// Calculate the last migration step from existing migration
		// filenames and procceed to the standard version migration
		$last_version = intval(substr($last_migration, 0, 3));

		return static::version($last_version, $name, $type);
	}

	// --------------------------------------------------------------------

	/**
	 * Set's the schema to the migration version set in config
	 *
	 * @access	public
	 * @return	mixed	true if already current, false if failed, int if upgraded
	 */
	public static function current()
	{
		return static::version(\Config::get('migrations.version'));
	}

	// --------------------------------------------------------------------

	/**
	 * Migrate to a schema version
	 *
	 * Calls each migration step required to get to the schema version of
	 * choice
	 *
	 * @access	public
	 * @param $version integer	Target schema version
	 * @return	mixed	true if already latest, false if failed, int if upgraded
	 */
	public static function version($version, $name, $type = 'app')
	{
		if ( ! isset(static::$version[$type][$name]))
		{
			\DB::insert(static::$table)
			->set(array(
				'name' => $name,
				'type' => $type,
				'version' => '0'
			))
			->execute();
			static::$version[$type][$name] = 0;
		}

		if (static::$version[$type][$name] === $version)
		{
			return false;
		}

		$start = static::$version[$type][$name];
		$stop = $version;

		if ($version > static::$version[$type][$name])
		{
			// Moving Up
			++$start;
			++$stop;
			$step = 1;
		}

		else
		{
			// Moving Down
			$step = -1;
		}

		$method = $step === 1 ? 'up' : 'down';
		$migrations = array();

		// We now prepare to actually DO the migrations
		// But first let's make sure that everything is the way it should be
		for ($i = $start; $i != $stop; $i += $step)
		{
			if ($type)
			{
				$get_method = '_find_'.$type;
				$f = static::$get_method($name, $i);
			}
			else
			{
				$f = static::_get_default($i);
			}
			//$f = glob(APPPATH . \Config::get('migrations.folder') . str_pad($i, 3, '0', STR_PAD_LEFT) . "_*.php");

			// Only one migration per step is permitted
			if (count($f) > 1)
			{
				throw new FuelException('multiple_migrations_version');
				return false;
			}

			// Migration step not found
			if (count($f) == 0)
			{
				// If trying to migrate up to a version greater than the last
				// existing one, migrate to the last one.
				if ($step == 1) break;

				// If trying to migrate down but we're missing a step,
				// something must definitely be wrong.
				throw new FuelException('migration_not_found');
				return false;
			}

			$file = basename($f[0]);
			$file_name = basename($f[0], '.php');

			// Filename validations
			if (preg_match('/^\d{3}_(\w+)$/', $file_name, $match))
			{
				$match[1] = strtolower($match[1]);

				// Cannot repeat a migration at different steps
				if (in_array($match[1], $migrations))
				{
					throw new FuelException('multiple_migrations_name');
					return false;
				}

				include $f[0];
				$class = static::$prefix . ucfirst($match[1]);

				if ( ! class_exists($class, false))
				{
					throw new FuelException('migration_class_doesnt_exist');
					return false;
				}

				if ( ! is_callable(array($class, 'up')) || !is_callable(array($class, 'down')))
				{
					throw new FuelException('wrong_migration_interface');
					return false;
				}

				$migrations[] = $match[1];
			}
			else
			{
				throw new FuelException('invalid_migration_filename');
				return false;
			}
		}

		$version = $i + ($step == 1 ? -1 : 0);

		// If there is nothing to do, bitch and quit
		if ($migrations === array())
		{
			return false;
		}

		// Loop through the migrations
		foreach ($migrations as $migration)
		{
			logger(Fuel::L_INFO, 'Migrating to: '.static::$version + $step);

			$class = static::$prefix . ucfirst($migration);
			call_user_func(array(new $class, $method));

			static::$version[$type][$name] += $step;

			static::_update_schema_version(static::$version[$type][$name] - $step, static::$version[$type][$name], $name, $type);
		}

		logger(Fuel::L_INFO, 'Migrated to ' . static::$version.' successfully.');

		return static::$version[$type][$name];
	}

	// --------------------------------------------------------------------

	/**
	 * Set's the schema to the latest migration
	 *
	 * @access	public
	 * @return	mixed	true if already latest, false if failed, int if upgraded
	 */

	protected static function find_migrations($name, $type = 'app')
	{
		// Load all *_*.php files in the migrations path
		$method = '_find_'.$type;
		$files = static::$method($name);

		$file_count = count($files);

		for ($i = 0; $i < $file_count; $i++)
		{
			// Mark wrongly formatted files as false for later filtering
			$name = basename($files[$i], '.php');
			if ( ! preg_match('/^\d{3}_(\w+)$/', $name))
			{
				$files[$i] = false;
			}
		}

		return $files;
	}

	// --------------------------------------------------------------------

	/**
	 * Stores the current schema version
	 *
	 * @access	private
	 * @param $schema_version integer	Schema version reached
	 * @return	void					Outputs a report of the migration
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

	private static function _find_app($name = null, $file = null)
	{
		if ($file)
		{
			if ( ! isset($name))
			{
				throw new FuelException('Name must be set to find a specific file');
				return false;
			}

			return glob(APPPATH . \Config::get('migrations.folder') . str_pad($file, 3, '0', STR_PAD_LEFT) . "_*.php");;
		}

		return glob(APPPATH . \Config::get('migrations.folder') . '*_*.php');
	}

	private static function _find_module($name = null, $file = null)
	{
		if ($file)
		{
			if ( ! isset($name))
			{
				throw new FuelException('Name must be set to find a specific file');
				return false;
			}

			foreach (\Config::get('module_paths') as $m)
			{
				return glob($m .$name.'/'. \Config::get('migrations.folder') . str_pad($file, 3, '0', STR_PAD_LEFT) . "_*.php");
			}
		}

		if ($name)
		{
			// find a module
			foreach (\Config::get('module_paths') as $m)
			{
				$files = glob($m .$name.'/'. \Config::get('migrations.folder') . '*_*.php');
			}
		}
		else
		{
			// find all modules
			foreach (\Config::get('module_paths') as $m)
			{
				$files = glob($m .'*/'. \Config::get('migrations.folder') . '*_*.php');
			}
		}

		return $files;
	}

	private static function _find_package($name = null, $file = null)
	{
		if ($file)
		{
			if ( ! isset($name))
			{
				throw new FuelException('Name must be set to find a specific file');
				return false;
			}

			return glob(PKGPATH .$name.'/'. \Config::get('migrations.folder') . str_pad($file, 3, '0', STR_PAD_LEFT) . "_*.php");;
		}

		if ($name)
		{
			// find a package
			$files = glob(PKGPATH .$name.'/'. \Config::get('migrations.folder') . '*_*.php');
		}
		else
		{
			// find all modules
			$files = glob(PKGPATH .'*/'. \Config::get('migrations.folder') . '*_*.php');
		}

		return $files;
	}
}

