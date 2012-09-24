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


abstract class Log_Driver
{

	/**
	 * Driver Levels
	 *
	 * @var	array
	 */
	protected $levels = array();

	/**
	 * is logging active?
	 *
	 * @var  bool
	 */
	protected $active = true;

	abstract function __construct($levels = array(), $active = true);

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public function error($msg, $method = null)
	{
		return $this->write(\Fuel::L_ERROR, $msg, $method);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public function warning($msg, $method = null)
	{
		return $this->write(\Fuel::L_WARNING, $msg, $method);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public function debug($msg, $method = null)
	{
		return $this->write(\Fuel::L_DEBUG, $msg, $method);
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public function info($msg, $method = null)
	{
		return $this->write(\Fuel::L_INFO, $msg, $method);
	}

	abstract function write($level, $msg, $method = null);

}


