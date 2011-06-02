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

namespace Fuel\Core;

/**
 * Cookie class
 *
 * @package    Fuel
 * @category   Helpers
 * @author     Kohana Team
 * @modified   Fuel Development Team
 * @copyright  (c) 2008-2010 Kohana Team
 * @license    http://kohanaframework.org/license
 * @link       http://fuelphp.com/docs/classes/cookie.html
 */
class Cookie {

	/**
	 * @var  array  Cookie class configuration defaults
	 */
	private static $config = array(
		'expiration'            => 0,
		'path'                  => '/',
		'domain'                => null,
		'secure'                => false,
		'http_only'             => false,
	);

	/*
	 * initialisation and auto configuration
	 */
	public static function _init()
	{
		static::$config = array_merge(static::$config, \Config::get('cookie', array()));
	}

	/**
	 * Gets the value of a signed cookie. Cookies without signatures will not
	 * be returned. If the cookie signature is present, but invalid, the cookie
	 * will be deleted.
	 *
	 *     // Get the "theme" cookie, or use "blue" if the cookie does not exist
	 *     $theme = Cookie::get('theme', 'blue');
	 *
	 * @param   string  cookie name
	 * @param   mixed   default value to return
	 * @return  string
	 */
	public static function get($name, $default = null)
	{
		return \Input::cookie($name, $default);
	}

	/**
	 * Sets a signed cookie. Note that all cookie values must be strings and no
	 * automatic serialization will be performed!
	 *
	 *     // Set the "theme" cookie
	 *     Cookie::set('theme', 'red');
	 *
	 * @param   string   name of cookie
	 * @param   string   value of cookie
	 * @param   integer  lifetime in seconds
	 * @param   string   path of the cookie
	 * @param   string   domain of the cookie
	 * @return  boolean
	 */
	public static function set($name, $value, $expiration = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		// If nothing is provided, use the standard amount of time
		if ($expiration === null)
		{
			$expiration = static::$config['expiration'];
		}
		// If it's set, add the current time so we have an offset
		else
		{
			$expiration = $expiration > 0 ? $expiration + time() : 0;
		}

		// use the class defaults for the other parameters if not provided
		is_null($path) && $path = static::$config['path'];
		is_null($domain) && $domain = static::$config['domain'];
		is_null($secure) && $secure = static::$config['secure'];
		is_null($http_only) && $http_only = static::$config['http_only'];

		return setcookie($name, $value, $expiration, $path, $domain, $secure, $http_only);
	}

	/**
	 * Deletes a cookie by making the value null and expiring it.
	 *
	 *     Cookie::delete('theme');
	 *
	 * @param   string   cookie name
	 * @return  boolean
	 * @uses    static::set
	 */
	public static function delete($name)
	{
		// Remove the cookie
		unset($_COOKIE[$name]);

		// Nullify the cookie and make it expire
		return static::set($name, null, -86400);
	}
}

/* End of file cookie.php */
