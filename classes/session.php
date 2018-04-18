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

namespace Fuel\Core;

/**
 * Session Class
 *
 * @package		Fuel
 * @category	Core
 * @author		Harro "WanWizard" Verton
 * @link		http://docs.fuelphp.com/classes/session.html
 */
class Session
{
	/**
	 * default session driver instance
	 */
	protected static $_instance = null;

	/**
	 * array of loaded instances
	 */
	protected static $_instances = array();

	/**
	 * array of global config defaults
	 */
	protected static $_defaults = array(
		'driver'                    => 'cookie',
		'match_ip'                  => false,
		'match_ua'                  => true,
		'cookie_domain'             => '',
		'cookie_path'               => '/',
		'cookie_http_only'          => null,
		'encrypt_cookie'            => true,
		'expire_on_close'           => false,
		'expiration_time'           => 7200,
		'rotation_time'             => 300,
		'flash_id'                  => 'flash',
		'flash_auto_expire'         => true,
		'flash_expire_after_get'    => true,
		'post_cookie_name'          => '',
	);

	// --------------------------------------------------------------------

	/**
	 * Initialize by loading config & starting default session
	 */
	public static function _init()
	{
		\Config::load('session', true);

		if (\Config::get('session.auto_initialize', true))
		{
			// create the default instance if required
			static::$_instance = static::forge();

			// and start it if it wasn't auto-started
			if ( ! \Config::get('session.auto_start', true))
			{
				static::$_instance->start();
			}
		}

		if (\Config::get('session.native_emulation', false))
		{
			// emulate native PHP sessions
			session_set_save_handler(
				// open
				function ($savePath, $sessionName) {
					return true;
				},
				// close
				function () {
					return true;
				},
				// read
				function ($sessionId) {
					// copy all existing session vars into the PHP session store
					$_SESSION = \Session::get();
					$_SESSION['__org__'] = $_SESSION;
					return '';
				},
				// write
				function ($sessionId, $data) {
					// get the original data
					$org = isset($_SESSION['__org__']) ? $_SESSION['__org__'] : array();
					unset($_SESSION['__org__']);

					// do we need to remove stuff?
					if ($remove = array_diff_key($org, $_SESSION))
					{
						\Session::delete(array_keys($remove));
					}

					// add or update the remainder
					empty($_SESSION) or \Session::set($_SESSION);
					return true;
				},
				// destroy
				function ($sessionId) {
					\Session::destroy();
					return true;
				},
				// gc
				function ($lifetime) {
					return true;
				}
			);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Factory
	 *
	 * Produces fully configured session driver instances
	 *
	 * @param	array|string	$custom	full driver config or just driver type
	 * @return	mixed
	 * @throws	\FuelException
	 * @throws	\Session_Exception
	 */
	public static function forge($custom = array())
	{
		$config = \Config::get('session', array());

		// When a string was passed it's just the driver type
		if ( ! empty($custom) and ! is_array($custom))
		{
			$custom = array('driver' => $custom);
		}

		$config = array_merge(static::$_defaults, $config, $custom);

		if (empty($config['driver']))
		{
			throw new \Session_Exception('No session driver given or no default session driver set.');
		}

		// determine the driver to load
		$class = '\\Session_'.ucfirst($config['driver']);

		$driver = new $class($config);

		// get the driver's cookie name
		$cookie = $driver->get_config('cookie_name');

		// do we already have a driver instance for this cookie?
		if (isset(static::$_instances[$cookie]))
		{
			// if so, they must be using the same driver class!
			$class_instance = 'Fuel\\Core'.$class;
			if ( ! static::$_instances[$cookie] instanceof $class_instance)
			{
				throw new \FuelException('You can not instantiate two different sessions using the same cookie name "'.$cookie.'"');
			}
		}
		else
		{
			// store this instance
			static::$_instances[$cookie] =& $driver;

			// start the session if needed
			if (\Config::get('session.auto_start', true))
			{
				$driver->start();
			}
		}

		// return the session instance
		return static::instance($cookie);
	}

	// --------------------------------------------------------------------

	/**
	 * class constructor
	 *
	 * @param	void
	 */
	final private function __construct() {}

	// --------------------------------------------------------------------

	/**
	 * create or return the driver instance
	 *
	 * @param	void
	 * @return	\Session_Driver object
	 */
	public static function instance($instance = null)
	{
		// if a named instance is requested
		if ($instance !== null)
		{
			// return it if it exists
			if ( ! array_key_exists($instance, static::$_instances))
			{
				return false;
			}

			return static::$_instances[$instance];
		}

		// return the default instance
		return static::forge();
	}

	// --------------------------------------------------------------------

	/**
	 * set session variables
	 *
	 * @param	string|array	$name	name of the variable to set or array of values, array(name => value)
	 * @param	mixed			$value	value
	 * @return	\Session_Driver
	 */
	public static function set($name, $value = null)
	{
		return static::instance()->set($name, $value);
	}

	// --------------------------------------------------------------------

	/**
	 * get session variables
	 *
	 * @param	string	$name		name of the variable to get
	 * @param	mixed	$default	default value to return if the variable does not exist
	 * @return	mixed
	 */
	public static function get($name = null, $default = null)
	{
		return static::instance()->get($name, $default);
	}

	// --------------------------------------------------------------------

	/**
	 * delete a session variable
	 *
	 * @param	string	$name	name of the variable to delete
	 * @return	Session_Driver
	 */
	public static function delete($name)
	{
		return static::instance()->delete($name);
	}

	// --------------------------------------------------------------------

	/**
	 * get session key variables
	 *
	 * @param	string	$name	name of the variable to get, default is 'session_id'
	 * @return	mixed
	 */
	public static function key($name = 'session_id')
	{
		return static::$_instance ? static::instance()->key($name) : null;
	}

	// --------------------------------------------------------------------

	/**
	 * set session flash variables
	 *
	 * @param	string	$name	name of the variable to set
	 * @param	mixed	$value	value
	 * @return	void
	 */
	public static function set_flash($name, $value = null)
	{
		return static::instance()->set_flash($name, $value);
	}

	// --------------------------------------------------------------------

	/**
	 * get session flash variables
	 *
	 * @param	string	$name		name of the variable to get
	 * @param	mixed	$default	default value to return if the variable does not exist
	 * @param	bool	$expire		true if the flash variable needs to expire immediately
	 * @return	mixed
	 */
	public static function get_flash($name = null, $default = null, $expire = null)
	{
		return static::instance()->get_flash($name, $default, $expire);
	}

	// --------------------------------------------------------------------

	/**
	 * keep session flash variables
	 *
	 * @param	string	$name	name of the variable to keep
	 * @return	\Session_Driver
	 */
	public static function keep_flash($name = null)
	{
		return static::instance()->keep_flash($name);
	}

	// --------------------------------------------------------------------

	/**
	 * delete session flash variables
	 *
	 * @param	string	$name	name of the variable to delete
	 * @return	\Session_Driver
	 */
	public static function delete_flash($name = null)
	{
		return static::instance()->delete_flash($name);
	}

	// --------------------------------------------------------------------

	/**
	 * start the session
	 *
	 * @return	\Session_Driver
	 */
	public static function start()
	{
		return static::instance()->start();
	}

	// --------------------------------------------------------------------

	/**
	 * write the session
	 *
	 * @param	bool	$save	if true, save the session on close
	 *
	 * @return	\Session_Driver
	 */
	public static function close($save = true)
	{
		return static::instance()->close($save);
	}

	// --------------------------------------------------------------------

	/**
	 * reset the session
	 *
	 * @return	\Session_Driver
	 */
	public static function reset()
	{
		return static::instance()->reset();
	}

	// --------------------------------------------------------------------

	/**
	 * rotate the session id
	 *
	 * @return	\Session_Driver
	 */
	public static function rotate()
	{
		return static::instance()->rotate();
	}

	// --------------------------------------------------------------------

	/**
	 * destroy the current session
	 *
	 * @return	\Session_Driver
	 */
	public static function destroy()
	{
		return static::instance()->destroy();
	}

}
