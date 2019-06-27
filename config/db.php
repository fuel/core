<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
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
	 *  Active Configurations
	 * -------------------------------------------------------------------------
	 *
	 *  If you don't specify a DB configuration name when you create
	 *  a database connection, the configuration to be used will be determined
	 *  by the 'active' value.
	 *
	 */

	'active' => 'default',

	/**
	 * -------------------------------------------------------------------------
	 *  PDO
	 * -------------------------------------------------------------------------
	 *
	 *  Base PDO configurations.
	 *
	 */

	'default' => array(
		'type' => 'pdo',

		'connection' => array(
			'dsn'        => '',
			'hostname'   => '',
			'username'   => null,
			'password'   => null,
			'database'   => '',
			'persistent' => false,
			'compress'   => false,
		),

		'identifier'   => '`',
		'table_prefix' => '',
		'charset'      => 'utf8',
		'collation'    => false,
		'enable_cache' => true,
		'profiling'    => false,
		'readonly'     => false,
	),

	/**
	 * -------------------------------------------------------------------------
	 *  MySQLi
	 * -------------------------------------------------------------------------
	 *
	 *  Base MySQLi configurations.
	 *
	 */

	'mysqli' => array(
		'type' => 'mysqli',

		'connection' => array(
			'dsn'        => '',
			'hostname'   => '',
			'username'   => null,
			'password'   => null,
			'database'   => '',
			'persistent' => false,
			'compress'   => false,
		),

		'identifier'   => '`',
		'table_prefix' => '',
		'charset'      => 'utf8',
		'collation'    => false,
		'enable_cache' => false,
		'profiling'    => false,
		'readonly'     => false,
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Redis
	 * -------------------------------------------------------------------------
	 *
	 *  Base Redis configurations.
	 *
	 */

	'redis' => array(
		'default' => array(
			'hostname' => '127.0.0.1',
			'port'     => 6379,
			'timeout'  => null,
			'database' => 0,
		),
	),
);
