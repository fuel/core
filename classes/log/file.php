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
class Log_File extends \Log_Driver
{

	/**
	 * Default configuration values
	 *
	 * @var  array
	 */
	protected static $default_config = array(
		'path' => '/', // APPPATH.'logs/'
		'date_format'  => 'Y-m-d H:i:s',
	);

	public function __construct($levels = array(), $active = true)
	{
		$this->levels = $levels;
		$this->active = $active;

		// defining the default path
		static::$default_config['path'] = APPPATH.'logs/';

		$config = \Config::get('log.file', array());
		\Config::set('log.file', array_merge(static::$default_config, $config));
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

		// and write it to the logfile
		$filepath = \Config::get('log.file.path').date('Y/m').'/';

		if ( ! is_dir($filepath))
		{
			$old = umask(0);

			mkdir($filepath, \Config::get('file.chmod.folders', 0777), true);
			umask($old);
		}

		$filename = $filepath.date('d').'.php';

		$message  = '';

		if ( ! $exists = file_exists($filename))
		{
			$message .= "<"."?php defined('COREPATH') or exit('No direct script access allowed'); ?".">".PHP_EOL.PHP_EOL;
		}

		if ( ! $fp = @fopen($filename, 'a'))
		{
			return false;
		}

		$call = '';
		if ( ! empty($method))
		{
			$call .= $method;
		}

		$message .= $level.' '.(($level == 'info') ? ' -' : '-').' ';
		$message .= date(\Config::get('log.file.date_format'));
		$message .= ' --> '.(empty($call) ? '' : $call.' - ').$msg.PHP_EOL;

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		if ( ! $exists)
		{
			$old = umask(0);
			@chmod($filename, \Config::get('file.chmod.files', 0666));
			umask($old);
		}

		return true;
	}

}
