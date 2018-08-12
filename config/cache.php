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
 *  copy this file to your app/config folder, and make them in there.
 *
 *  This will allow you to upgrade FuelPHP without losing your custom config.
 *
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Active Driver
	 * -------------------------------------------------------------------------
	 */

	'driver' => 'file',

	/**
	 * -------------------------------------------------------------------------
	 *  Expiration
	 * -------------------------------------------------------------------------
	 */

	'expiration' => null,

	/**
	 * Default content handlers: convert values to strings to be stored
	 * You can set them per primitive type or object class like this:
	 *   - 'string_handler' 		=> 'string'
	 *   - 'array_handler'			=> 'json'
	 *   - 'Some_Object_handler'	=> 'serialize'
	 */

	/**
	 * -------------------------------------------------------------------------
	 *  File Driver Settings
	 * -------------------------------------------------------------------------
	 *
	 *  If empty, the default path will be 'application/cache/'
	 *
	 */

	'file' => array(
		'path' =>	'',
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Memcached Driver Settings
	 * -------------------------------------------------------------------------
	 */

	'memcached' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Cache ID
		 * ---------------------------------------------------------------------
		 *
		 *  Unique ID to distinguish fuel cache items from other cache
		 *  stored on the same server(s).
		 *
		 */

		'cache_id'  => 'fuel',

		/**
		 * ---------------------------------------------------------------------
		 *  Servers
		 * ---------------------------------------------------------------------
		 *
		 *  Servers and port numbers that run the memcached service.
		 *
		 */

		'servers' => array(
			'default' => array(
				'host'   => '127.0.0.1',
				'port'   => 11211,
				'weight' => 100
			),
		),
	),

	/**
	 * -------------------------------------------------------------------------
	 *  APC Driver Settings
	 * -------------------------------------------------------------------------
	 */

	'apc' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Cache ID
		 * ---------------------------------------------------------------------
		 *
		 *  Unique ID to distinguish fuel cache items from other cache
		 *  stored on the same server(s).
		 *
		 */

		'cache_id' => 'fuel',
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Redis Driver Settings
	 * -------------------------------------------------------------------------
	 */

	'redis' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Database Name
		 * ---------------------------------------------------------------------
		 *
		 *  Name of the redis database to use (as configured in 'config/db.php')
		 *
		 */

		'database' => 'default',
	),

	/**
	 * -------------------------------------------------------------------------
	 *  XCache Driver Settings
	 * -------------------------------------------------------------------------
	 */

	'xcache' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Cache ID
		 * ---------------------------------------------------------------------
		 *
		 *  Unique ID to distinguish fuel cache items from other cache
		 *  stored on the same server(s).
		 *
		 */

		'cache_id'  => 'fuel',
	),
);
