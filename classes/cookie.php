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
 * @link       http://docs.fuelphp.com/classes/cookie.html
 */
class Cookie
{
	/**
	 * @var  array  Cookie class configuration defaults
	 */
	protected static $config = array(
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
	 * @param   string  $name cookie name
	 * @param   mixed   $default default value to return
	 * @return  string
	 */
	public static function get($name = null, $default = null)
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
	 * @param   string    $name        name of cookie
	 * @param   string    $value       value of cookie
	 * @param   integer   $expiration  lifetime in seconds
	 * @param   string    $path        path of the cookie
	 * @param   string    $domain      domain of the cookie
	 * @param   boolean   $secure      if true, the cookie should only be transmitted over a secure HTTPS connection
	 * @param   boolean   $http_only   if true, the cookie will be made accessible only through the HTTP protocol
	 * @return  boolean
	 */
	public static function set($name, $value, $expiration = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		// you can't set cookies in CLi mode
		if (\Fuel::$is_cli)
		{
			return false;
		}

		$value = \Fuel::value($value);

		// use the class defaults for the other parameters if not provided
		is_null($expiration) and $expiration = static::$config['expiration'];
		is_null($path) and $path = static::$config['path'];
		is_null($domain) and $domain = static::$config['domain'];
		is_null($secure) and $secure = static::$config['secure'];
		is_null($http_only) and $http_only = static::$config['http_only'];

		// add the current time so we have an offset
		$expiration = $expiration > 0 ? $expiration + time() : 0;

		return setcookie($name, $value, $expiration, $path, $domain, $secure, $http_only);
	}

	/**
	 * Deletes a cookie by making the value null and expiring it.
	 *
	 *     Cookie::delete('theme');
	 *
	 * @param   string   $name       cookie name
 	 * @param   string   $path       path of the cookie
	 * @param   string   $domain     domain of the cookie
	 * @param   boolean  $secure     if true, the cookie should only be transmitted over a secure HTTPS connection
	 * @param   boolean  $http_only  if true, the cookie will be made accessible only through the HTTP protocol
	 * @return  boolean
	 * @uses    static::set
	 */
	public static function delete($name, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		// Remove the cookie
		unset($_COOKIE[$name]);

		// Nullify the cookie and make it expire
		return static::set($name, null, -86400, $path, $domain, $secure, $http_only);
	}
}
