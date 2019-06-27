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
	 *  Initialization
	 * -------------------------------------------------------------------------
	 *
	 *  Set to false to prevent the default session from being automatically
	 *  created and started when accessing the Session class.
	 *
	 *  [!] WARNING:
	 *
	 *  If you set this to false, your session may expire prematurely as it is
	 *  no longer automatically updated on every page load when you load
	 *  or autoload the Session class.
	 *
	 */

	'auto_initialize' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Start Options
	 * -------------------------------------------------------------------------
	 *
	 *  Set to false to prevent manually created session instances from being
	 *  autostarted when they are created.
	 *
	 */

	'auto_start' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Driver
	 * -------------------------------------------------------------------------
	 *
	 *  If no session type is requested, use 'cookie' as the default value.
	 *
	 */

	'driver' => 'cookie',

	/**
	 * -------------------------------------------------------------------------
	 *  IP Address Checking
	 * -------------------------------------------------------------------------
	 *
	 *  Check for an IP address match after loading the cookie.
	 *
	 */

	'match_ip' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  User Agent Checking
	 * -------------------------------------------------------------------------
	 *
	 *  Check for an User Agent match after loading the cookie.
	 *
	 */

	'match_ua' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Cookie
	 * -------------------------------------------------------------------------
	 *
	 */

	'cookie_domain'    => '',
	'cookie_path'      => '/',
	'cookie_http_only' => null,

	/**
	 * -------------------------------------------------------------------------
	 *  Cookie - Security
	 * -------------------------------------------------------------------------
	 *
	 *  Whether securing the cookie with encryption or not.
	 *
	 */

	'encrypt_cookie' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Cookie - Expiration Options
	 * -------------------------------------------------------------------------
	 *
	 *  Whether the session expires when the browser is closed.
	 *
	 */

	'expire_on_close' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Cookie - Expiration Time
	 * -------------------------------------------------------------------------
	 *
	 *  Cookie expiration in seconds.
	 *
	 *  If 'expiration_time' less than or equal 0, it means the cookie will be
	 *  expired in 2 years.
	 *
	 */

	'expiration_time' => 7200,

	/**
	 * -------------------------------------------------------------------------
	 *  Rotation
	 * -------------------------------------------------------------------------
	 *
	 *  Session ID rotation time.
	 *
	 *  Set to false to disable rotation.
	 *
	 */

	'rotation_time' => 300,

	/**
	 * -------------------------------------------------------------------------
	 *  Flash Data - ID
	 * -------------------------------------------------------------------------
	 *
	 *  Default ID for flash variables.
	 *
	 */

	'flash_id' => 'flash',

	/**
	 * -------------------------------------------------------------------------
	 *  Flash Data - Expiration
	 * -------------------------------------------------------------------------
	 *
	 *  If false, expire flash values only after it's used.
	 *
	 */

	'flash_auto_expire' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Flash Data - Behavior
	 * -------------------------------------------------------------------------
	 *
	 *  If true, a 'get_flash()' automatically expires the flash data.
	 *
	 */

	'flash_expire_after_get' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Compatibility - Cookies Support
	 * -------------------------------------------------------------------------
	 *
	 *  For requests that don't support cookies (i.e. flash), use this
	 *  POST variable to pass the cookie to the session driver.
	 *
	 */

	'post_cookie_name' => '',

	/**
	 * -------------------------------------------------------------------------
	 *  Compatibility - Cookies Options
	 * -------------------------------------------------------------------------
	 *
	 *  For requests in which you don't want to use cookies, use an HTTP header
	 *  by this name to pass the cookie to the session driver.
	 *
	 */

	'http_header_name' => 'Session-Id',

	/**
	 * -------------------------------------------------------------------------
	 *  Compatibility - Response
	 * -------------------------------------------------------------------------
	 *
	 *  If false, no cookie will be added to the response to the client.
	 *
	 */

	'enable_cookie' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Compatibility - Integration
	 * -------------------------------------------------------------------------
	 *
	 *  If true, session data will be synced with PHP's native '$_SESSION',
	 *  to allow easier integration of third-party components.
	 *
	 */

	'native_emulation' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  [!] NOTICE
	 * -------------------------------------------------------------------------
	 *
	 *  Below are specific driver configurations.
	 *
	 *  To override a global setting, just add it to the driver config
	 *  with a different value.
	 *
	 */

	/**
	 * -------------------------------------------------------------------------
	 *  Configurations - Cookie Based
	 * -------------------------------------------------------------------------
	 *
	 *  Special configuration settings for cookie based sessions.
	 *
	 */

	'cookie' => array(
		'cookie_name' => 'fuelcid',
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Configurations - File Based
	 * -------------------------------------------------------------------------
	 *
	 *  Special configuration settings for file based sessions.
	 *
	 */

	'file' => array(
		'cookie_name' => 'fuelfid',

		/**
		 * ---------------------------------------------------------------------
		 *  Storage Path
		 * ---------------------------------------------------------------------
		 *
		 *  Path where the session files should be stored.
		 *
		 */

		'path' => '/tmp',

		/**
		 * ---------------------------------------------------------------------
		 *  Garbage Collection
		 * ---------------------------------------------------------------------
		 *
		 *  Probability % (between 0 and 100) for garbage collection.
		 *
		 */

		'gc_probability' => 5,
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Configurations - Memcached Based
	 * -------------------------------------------------------------------------
	 *
	 *  Special configuration settings for memcached based sessions.
	 *
	 */

	'memcached' => array(
		'cookie_name' => 'fuelmid',

		/**
		 * ---------------------------------------------------------------------
		 *  Server
		 * ---------------------------------------------------------------------
		 *
		 *  Servers and portnumbers that run the memcached service.
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
	 *  Configurations - Database Based
	 * -------------------------------------------------------------------------
	 *
	 *  Special configuration settings for database based sessions.
	 *
	 */

	'db' => array(
		'cookie_name' => 'fueldid',

		/**
		 * ---------------------------------------------------------------------
		 *  Database Name
		 * ---------------------------------------------------------------------
		 *
		 *  Name of the database (as configured in config/db.php).
		 *
		 */

		'database' => null,

		/**
		 * ---------------------------------------------------------------------
		 *  Table Name
		 * ---------------------------------------------------------------------
		 *
		 *  Name of the session table.
		 *
		 */

		'table' => 'sessions',

		/**
		 * ---------------------------------------------------------------------
		 *  Garbage Collection
		 * ---------------------------------------------------------------------
		 *
		 *  Probability % (between 0 and 100) for garbage collection.
		 *
		 */

		'gc_probability' => 5,
	),

	// specific configuration settings for redis based sessions
	/**
	 * -------------------------------------------------------------------------
	 *  Configurations - Redis Based
	 * -------------------------------------------------------------------------
	 *
	 *  Special configuration settings for Redis based sessions.
	 *
	 */

	'redis' => array(
		'cookie_name' => 'fuelrid',

		/**
		 * ---------------------------------------------------------------------
		 *  Database Name
		 * ---------------------------------------------------------------------
		 *
		 *  Name of the Redis database to use (as configured in config/db.php).
		 *
		 */

		'database' => 'default',
	),
);
