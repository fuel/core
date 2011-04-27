<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

return array(

	/**
	 * Manual browscap parsing configuration.
	 *
	 * This will be used when your PHP installation has no browscap defined
	 * in your php.ini, httpd.conf or .htaccess, and you can't configure one.
	 */
	'browscap' => array(

		/**
		 * Whether of not manual parsing is enabled.
		 *
		 * set to false to disable this functionality.
		 */
		'enabled' => true,

		/**
		 * Refresh interval.
		 *
		 * when enabled, the agent class downloads a new version of the browscap
		 * file and parses it so it can be used for lookups. This process can
		 * take quite long, so you don't want to do this to often, and probably
		 * out of hours. The interval is defined in minutes.
		 *
		 *	Default: 10080 (every 7 days)
		 */
		'interval' => 10080,

	),

	/**
	 * Location of the agent class cache files.
	 *
	 *	Default: APPPATH.'cache/'
	 *
	 */
	'path' => APPPATH.'cache'.DS,

	/**
	 * Cache expiry.
	 *
	 * Number of seconds after which a cached agent result expires.
	 *
	 *	Default: 604800 (every 7 days)
	 *
	 */
	'expiry' => 604800,

);

/* End of file config/agent.php */
