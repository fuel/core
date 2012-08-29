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
class Log_Db extends \Log_Driver
{

	/**
	 * Default configuration values
	 *
	 * @var  array
	 */
	protected static $default_config = array(
		'table' => 'logs'
	);

	public function __construct($levels = array(), $active = true)
	{
		$this->levels = $levels;
		$this->active = $active;

		$config = \Config::get('log.db', array());
		\Config::set('log.db', array_merge(static::$default_config, $config));
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
	public function write($level, $msg, $method = null)
	{
		if ($this->active === false)
		{
			return false;
		}

		// if $level is string, it is custom level.
		if (is_int($level))
		{
			// do we need to log the message with this level?
			if ( ! array_key_exists($level, $this->levels))
			{
				return false;
			}

			// store the label for this level for future use
			$level = $this->levels[$level];
		}

		// if profiling is active log the message to the profile
		if (Config::get('profiling'))
		{
			\Console::log($method.' - '.$msg);
		}

		$message = (empty($call) ? '' : $call.' - ').$msg.PHP_EOL;

		\DB::insert(\Config::get('log.db.table'))
			->set(array(
				'level'   => $level,
				'message' => $message,
				'date'    => \Date::forge()->get_timestamp()
			))
			->execute();

		return true;
	}

}
