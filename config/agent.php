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
	 *  Browscap
	 * -------------------------------------------------------------------------
	 *
	 *  Manual browscap parsing configuration.
	 *
	 *  This will be used when your PHP installation has no browscap defined
	 *  in your php.ini, httpd.conf or .htaccess, and you can't configure one.
	 *
	 */

	'browscap' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Manual parsing
		 * ---------------------------------------------------------------------
		 */

		'enabled' => true,

		/**
		 * ---------------------------------------------------------------------
		 *  Source address
		 * ---------------------------------------------------------------------
		 *
		 *  Location from where the updated browscap file can be downloaded.
		 *
		 *  For major browsers and search engines only:
		 *
		 *      'http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'
		 *
		 *  For full list ( about 3x of the lite version ):
		 *
		 *      'http://browscap.org/stream?q=Full_PHP_BrowsCapINI'
		 *
		 */

		'url' => 'http://browscap.org/stream?q=Lite_PHP_BrowsCapINI',

		/**
		 * ---------------------------------------------------------------------
		 *  Download method
		 * ---------------------------------------------------------------------
		 *
		 *  Method used to download the updated browscap file.
		 *
		 *  Possible values are:
		 *
		 *      'local', 'wrapper' or 'curl'
		 *
		 */

		'method' => 'wrapper',

		/**
		 * ---------------------------------------------------------------------
		 *  Proxy settings
		 * ---------------------------------------------------------------------
		 *
		 *  Optional http proxy configuration.
		 *
		 *  This will be used for both the 'wrapper' and 'curl' methods.
		 *
		 */

		'proxy' => array(
			/**
			 * -----------------------------------------------------------------
			 *  Hostname or IP address of your proxy
			 * -----------------------------------------------------------------
			 *
			 *  [!] This does NOT work:
			 *
			 *      'http://proxy.example.org'
			 *
			 *  Use these instead:
			 *
			 *      'proxy.example.org' or '1.1.1.1'
			 *
			 */

			'host' => null,

			/**
			 * -----------------------------------------------------------------
			 *  TCP port number the proxy listens at
			 * -----------------------------------------------------------------
			 */

			'port' => null,

			/**
			 * -----------------------------------------------------------------
			 *  Authentication
			 * -----------------------------------------------------------------
			 *
			 *  Authentication type to use.
			 *
			 *  Possible values are:
			 *
			 *      'none', 'basic' or 'ntlm'
			 *
			 *  [!] WARNING:
			 *
			 *  The 'wrapper' method only supports 'basic'. Other methods
			 *  will be evaluated as 'none'.
			 *
			 */

			'auth' => 'none',

			/**
			 * -----------------------------------------------------------------
			 *  Credentials
			 * -----------------------------------------------------------------
			 *
			 *  If your proxy requires authentication, set username and password
			 *  here.
			 *
			 */

			'username' => null,
			'password' => null,
		 ),

		/**
		 * ---------------------------------------------------------------------
		 *  Filename
		 * ---------------------------------------------------------------------
		 *
		 *  Filename for the local browscap.ini file (for method 'local').
		 *
		 *  Default value is ''
		 *
		 */

		'file' => '/tmp/php_browscap.ini',
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Cache
	 * -------------------------------------------------------------------------
	 *
	 *  The agent class caches all matched agent strings for future reference
	 *  so the browscap file doesn't need to be loaded, as it's quite large.
	 *
	 *  Also, the parsed and condensed browscap ini file is stored in cache as
	 *  well, so when a new user agent string needs to be looked up, no further
	 *  parsing is needed.
	 *
	 */

	'cache' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Driver
		 * ---------------------------------------------------------------------
		 *
		 *  Storage driver to use to cache agent class entries. If not defined,
		 *  the default driver defined in 'config/cache.php' will be used.
		 *
		 */

		'driver' => '',

		/**
		 * ---------------------------------------------------------------------
		 *  Expiration
		 * ---------------------------------------------------------------------
		 *
		 *  Number of seconds after which a cached agent result expires.
		 *
		 *	Default value is 604800 (every 7 days)
		 *
		 *  [!] INFO:
		 *
		 *  To prevent abuse of the site publishing the browsecap files,
		 *  you can not set the expiry time lower than 7200 (2 hours).
		 *
		 */

		'expiry' => 604800,

		/**
		 * ---------------------------------------------------------------------
		 *  Identifier
		 * ---------------------------------------------------------------------
		 *
		 *  Identifier used to store agent class cache elements
		 *
		 *	Default value is 'fuel.agent'
		 *
		 */

		'identifier' => 'fuel.agent',
	),
);
