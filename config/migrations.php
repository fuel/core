<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
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
	 *  Version
	 * -------------------------------------------------------------------------
	 *
	 *  Which version of the schema should be considered current.
	 *
	 *  Default value is 0.
	 *
	 */

	'version' => array(
		'app' => array(
			'default' => 0,
		),

		'module' => array(),

		'package' => array(),
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Folder
	 * -------------------------------------------------------------------------
	 *
	 *  Folder name where migrations are stored relative to App, Module
	 *  and Package paths.
	 *
	 *  Default path directory is 'migrations/'.
	 *
	 */

	'folder' => 'migrations/',

	/**
	 * -------------------------------------------------------------------------
	 *  Table Name
	 * -------------------------------------------------------------------------
	 *
	 *  Table name for migrations.
	 *
	 *  Default table name is 'migration'.
	 *
	 */

	'table' => 'migration',

	/**
	 * -------------------------------------------------------------------------
	 *  Cache
	 * -------------------------------------------------------------------------
	 *
	 *  Whether to flush all cache after running migrations.
	 *
	 *  Default value is false.
	 *
	 */

	 'flush_cache' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Flag
	 * -------------------------------------------------------------------------
	 *
	 *  Name of a file to be used as "in-migration" flag. If configured, it will
	 *  be created as soon as migrations start, and removed when migrations
	 *  are finished. This can be used by the app to block access or show a
	 *  maintenance page.
	 *
	 *  Note, the file must be in a location where both the CLI and the webserver
	 *  is able to write to. /tmp is obvious, but will usually not work due to
	 *  systemd PrivateTmp.
	 *
	 *  Default value is null.
	 *
	 */

	 'flag' => null,
);
