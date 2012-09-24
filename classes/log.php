<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Log Class
 *
 * @package		Fuel
 * @category	Logging
 * @author		Phil Sturgeon
 * @link		http://docs.fuelphp.com/classes/log.html
 */
class Log
{

	/**
	 * default instance
	 *
	 * @var  array
	 */
	protected static $_instance = null;

	/**
	 * All the Asset instances
	 *
	 * @var  array
	 */
	protected static $_instances = array();

	/**
	 * Default configuration values
	 *
	 * @var  array
	 */
	protected static $default_config = array(
		'driver' => 'file',
		'levels' => array(
			1  => 'Error',
			2  => 'Warning',
			3  => 'Debug',
			4  => 'Info',
		)
	);

	public static function _init()
	{
		\Config::load('file', true);
		\Config::load('log', true);

		// make sure the configured chmod values are octal
		$chmod = \Config::get('file.chmod.folders', 0777);
		is_string($chmod) and \Config::set('file.chmod.folders', octdec($chmod));

		$chmod = \Config::get('file.chmod.files', 0666);
		is_string($chmod) and \Config::set('file.chmod.files', octdec($chmod));

		$config = \Config::get('log', array());
		\Config::set('log', array_merge(static::$default_config, $config));

		// creates driver instance
		static::instance();
	}

	/**
	 * Factory
	 *
	 * Produces fully configured log driver instances
	 *
	 */
	public static function forge()
	{
		$config = \Config::get('log', array());
		$config = array_merge(static::$default_config, $config);

		// validating custom levels
		$levels = array_intersect_key($config['levels'], static::$default_config['levels']);
		if ( ! empty($levels))
		{
			throw new \Log_Exception('You can not overwrite default log levels.');
		}
		else
		{
			$config['levels'] = static::$default_config['levels'] + $config['levels'];
		}

		if (empty($config['driver']))
		{
			throw new \Log_Exception('No log driver given or no default log driver set.');
		}

		// bail out if we don't need logging at all
		$active = $config['threshold'] === 0 ? false : true;

		// if it's not an array, assume it's an "up to" level
		if ( ! is_array($config['threshold']))
		{
			$levels = array_slice($config['levels'], 0, $config['threshold'], true);
		}
		else
		{
			$levels = array_intersect_key($config['levels'], array_flip($config['threshold']));
		}

		// determine the driver to load
		$class = '\\Log_'.ucfirst($config['driver']);

		$driver = new $class($levels, $active);

		// do we already have a driver instance?
		if (isset(static::$_instances[$config['driver']]))
		{
			// if so, they must be using the same driver class!
			$class_instance = 'Fuel\\Core\\'.$class;
			if (static::$_instances[$config['driver']] instanceof $class_instance)
			{
				throw new \FuelException('You can not instantiate two different logs using the same driver "'.$config['driver'].'"');
			}
		}
		else
		{
			// store this instance
			static::$_instances[$config['driver']] =& $driver;
		}

		return static::$_instances[$config['driver']];
	}

	/**
	 * create or return the driver instance
	 *
	 * @param	void
	 * @access	public
	 * @return	Log_Driver object
	 */
	public static function instance($instance = null)
	{
		if ($instance !== null)
		{
			if ( ! array_key_exists($instance, static::$_instances))
			{
				return false;
			}

			return static::$_instances[$instance];
		}

		if (static::$_instance === null)
		{
			static::$_instance = static::forge();
		}

		return static::$_instance;
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function info($msg, $method = null)
	{
		return static::instance()->write(\Fuel::L_INFO, $msg, $method);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function debug($msg, $method = null)
	{
		return static::instance()->write(\Fuel::L_DEBUG, $msg, $method);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function warning($msg, $method = null)
	{
		return static::instance()->write(\Fuel::L_WARNING, $msg, $method);
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function error($msg, $method = null)
	{
		return static::instance()->write(\Fuel::L_ERROR, $msg, $method);
	}

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @access	public
	 * @param	int|string	the error level
	 * @param	string	the error message
	 * @param	string	information about the method
	 * @return	bool
	 */
	public static function write($level, $msg, $method = null)
	{
		return static::instance()->write($level, $msg, $method);
	}
}