<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */

return array(
	/**
	 * global configuration
	*/

	// set it to false to prevent the default session from being automatically created and started when accessing the
	// Session class. Note that if you no, your session may expire prematurely as it is no longer automatically updated
	// on every page load when you (auto) load the Session class!
	'auto_initialize'	=> true,

	// set it to false to prevent manually created session instances from being autostarted when they are created
	'auto_start'		=> true,

	// if no session type is requested, use the default
	'driver'			=> 'cookie',

	// check for an IP address match after loading the cookie (optional, default = false)
	'match_ip'			=> false,

	// check for a user agent match after loading the cookie (optional, default = true)
	'match_ua'			=> true,

	// cookie domain  (optional, default = '')
	'cookie_domain' 	=> '',

	// cookie path  (optional, default = '/')
	'cookie_path'		=> '/',

	// cookie http_only flag  (optional, default = use the cookie class default)
	'cookie_http_only'	=> null,

	// whether or not to encrypt the session cookie (optional, default is true)
	'encrypt_cookie'	=> true,

	// if true, the session expires when the browser is closed (optional, default = false)
	'expire_on_close'	=> false,

	// session expiration time, <= 0 means 2 years! (optional, default = 2 hours)
	'expiration_time'	=> 7200,

	// session ID rotation time  (optional, default = 300) Set to false to disable rotation
	'rotation_time'		=> 300,

	// default ID for flash variables  (optional, default = 'flash')
	'flash_id'			=> 'flash',

	// if false, expire flash values only after it's used  (optional, default = true)
	'flash_auto_expire'	=> true,

	// if true, a get_flash() automatically expires the flash data
	'flash_expire_after_get' => true,

	// for requests that don't support cookies (i.e. flash), use this POST variable to pass the cookie to the session driver
	'post_cookie_name'	=> '',

	// for requests in which you don't want to use cookies, use an HTTP header by this name to pass the cookie to the session driver
	'http_header_name' => 'Session-Id',

	// if false, no cookie will be added to the response send back to the client
	'enable_cookie'	=> true,

	// if true, session data will be synced with PHP's native $_SESSION, to allow easier integration of third-party components
	'native_emulation'	=> false,

	/**
	 * specific driver configurations. to override a global setting, just add it to the driver config with a different value
	*/

	// special configuration settings for cookie based sessions
	'cookie'			=> array(
		'cookie_name'		=> 'fuelcid',				// name of the session cookie for cookie based sessions
						),

	// specific configuration settings for file based sessions
	'file'				=> array(
		'cookie_name'		=> 'fuelfid',				// name of the session cookie for file based sessions
		'path'				=>	'/tmp',					// path where the session files should be stored
		'gc_probability'	=>	5,						// probability % (between 0 and 100) for garbage collection
						),

	// specific configuration settings for memcached based sessions
	'memcached'			=> array(
		'cookie_name'		=> 'fuelmid',				// name of the session cookie for memcached based sessions
		'servers'			=> array(					// array of servers and portnumbers that run the memcached service
								'default' => array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100),
							),
						),

	// specific configuration settings for database based sessions
	'db'			=> array(
		'cookie_name'		=> 'fueldid',				// name of the session cookie for database based sessions
		'database'			=> null,					// name of the database name (as configured in config/db.php)
		'table'				=> 'sessions',				// name of the sessions table
		'gc_probability'	=> 5,						// probability % (between 0 and 100) for garbage collection
						),

	// specific configuration settings for redis based sessions
	'redis'			=> array(
		'cookie_name'		=> 'fuelrid',				// name of the session cookie for redis based sessions
		'database'			=> 'default',				// name of the redis database to use (as configured in config/db.php)
						),
);
