<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Log core class facade for the Monolog composer package.
 *
 * This class will provide the interface between the Fuel v1.x class API
 * and the Monolog package, in preparation for FuelPHP v2.0
 */
class Log
{
	/**
	 * container for the Monolog instance
	 */
	protected static $monolog = null;

	/**
	 * log file path
	 */
	protected static $path = null;

	/**
	 * log file filename
	 */
	protected static $filename = null;

	/**
	 * create the monolog instance
	 */
	public static function _init()
	{
		static::$monolog = new \Monolog\Logger('fuelphp');
		static::initialize();
	}

	/**
	 * return the monolog instance
	 */
	public static function instance()
	{
		return static::$monolog;
	}

	/**
	 * initialize the created the monolog instance
	 */
	public static function initialize()
	{
		// load the file config
		\Config::load('file', true);

		// get the required folder permissions
		$permission = \Config::get('file.chmod.folders', 0777);

		// determine the name and location of the logfile
		$path = \Config::get('log_path', APPPATH.'logs'.DS);

		// and make sure it exsts
		if ( ! is_dir($path) or ! is_writable($path))
		{
			\Config::set('log_threshold', \Fuel::L_NONE);
			throw new \FuelException('Unable to create the log file. The configured log path "'.$path.'" does not exist.');
		}

		// determine the name of the logfile
		$filename = \Config::get('log_file', null);
		if (empty($filename))
		{
			$filename = date('Y').DS.date('m').DS.date('d').'.php';
		}

		$fullpath = dirname($filename);

		// make sure the log directories exist
		try
		{
			// make sure the full path exists
			if ( ! is_dir($path.$fullpath))
			{
				\File::create_dir($path, $fullpath, $permission);
			}

			// open the file
			$handle = fopen($path.$filename, 'a');
		}
		catch (\Exception $e)
		{
			\Config::set('log_threshold', \Fuel::L_NONE);
			throw new \FuelException('Unable to access the log file. Please check the permissions on '.\Config::get('log_path').'. ('.$e->getMessage().')');
		}

		static::$path = $path;
		static::$filename = $filename;

		if ( ! filesize($path.$filename))
		{
			fwrite($handle, "<?php defined('COREPATH') or exit('No direct script access allowed'); ?>".PHP_EOL.PHP_EOL);
			chmod($path.$filename, \Config::get('file.chmod.files', 0666));
		}
		fclose($handle);

		// create the streamhandler, and activate the handler
		$stream = new \Monolog\Handler\StreamHandler($path.$filename, \Monolog\Logger::DEBUG);
		$formatter = new \Monolog\Formatter\LineFormatter("%level_name% - %datetime% --> %message%".PHP_EOL, \Config::get('log_date_format', 'Y-m-d H:i:s'));
		$stream->setFormatter($formatter);
		static::$monolog->pushHandler($stream);
	}

	/**
	 * Get the current log filename, optionally with a prefix or suffix.
	 */
	public static function logfile($prefix = '', $suffix = '')
	{
		$ext = pathinfo(static::$filename, PATHINFO_EXTENSION);
		$path = dirname(static::$filename);
		$file = pathinfo(static::$filename, PATHINFO_FILENAME);
		return static::$path.$path.DS.$prefix.$file.$suffix.($ext?('.'.$ext):'');
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function info($msg, $context = null)
	{
		return static::write(\Fuel::L_INFO, $msg, $context);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function debug($msg, $context = null)
	{
		return static::write(\Fuel::L_DEBUG, $msg, $context);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function warning($msg, $context = null)
	{
		return static::write(\Fuel::L_WARNING, $msg, $context);
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function error($msg, $context = null)
	{
		return static::write(\Fuel::L_ERROR, $msg, $context);
	}

	/**
	 * Write a log entry to Monolog
	 *
	 * @param	int|string    $level     the log level
	 * @param	string        $msg      the log message
	 * @param	array         $context  message context
	 * @return	bool
	 * @throws	\FuelException
	 */
	public static function log($level, $msg, array $context = array())
	{
		// bail out if we don't need logging at all
		if ( ! static::need_logging($level))
		{
			return false;
		}

		// if profiling is active log the message to the profile
		if (\Config::get('profiling'))
		{
			\Console::log($msg);
		}

		// log the message
		static::instance()->log($level, $msg, $context);

		return true;
	}

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	int|string    $level     the log level
	 * @param	string        $msg      the log message
	 * @param	string|array  $context  message context
	 * @return	bool
	 * @throws	\FuelException
	 */
	public static function write($level, $msg, $context = null)
	{
		// bail out if we don't need logging at all
		if (($level = static::need_logging($level)) === false)
		{
			return false;
		}

		// for compatibility with Monolog contexts
		if (is_array($context))
		{
			return static::log($level, $msg, $context);
		}

		// if profiling is active log the message to the profile
		if (\Config::get('profiling'))
		{
			empty($context) ? \Console::log($msg) : \Console::log($context.' - '.$msg);
		}

		// log the message
		empty($context) ? static::instance()->log($level, $msg) : static::instance()->log($level, $context.' - '.$msg);

		return true;
	}

	/**
	 * Check if a message with this log level needs logging
	 *
	 * @param	int|string    $level     the log level
	 * @return	bool
	 * @throws	\FuelException
	 */
	protected static function need_logging($level)
	{
		// defined default error labels
		static $levels = array(
			100 => 'DEBUG',
			200 => 'INFO',
			250 => 'NOTICE',
			300 => 'WARNING',
			400 => 'ERROR',
			500 => 'CRITICAL',
			550 => 'ALERT',
			600 => 'EMERGENCY',
		);

		// defined old default error labels
		static $oldlabels = array(
			1  => 'Error',
			2  => 'Warning',
			3  => 'Debug',
			4  => 'Info',
		);

		// get the levels defined to be logged
		$loglabels = \Config::get('log_threshold');

		// bail out if we don't need logging at all
		if ($loglabels == \Fuel::L_NONE)
		{
			// this entry should not be logged
			return false;
		}

		// if it's not an array, assume it's an "up to" level
		if ( ! is_array($loglabels))
		{
			$a = array();
			foreach ($levels as $l => $label)
			{
				$l >= $loglabels and $a[] = $l;
			}
			$loglabels = $a;
		}

		// convert the level to monolog standards if needed
		if (is_int($level) and isset($oldlabels[$level]))
		{
			$level = strtoupper($oldlabels[$level]);
		}
		if (is_string($level))
		{
			if ( ! $level = array_search($level, $levels))
			{
				$level = 250;	// can't map it, convert it to a NOTICE
			}
		}

		// make sure $level has the correct value
		if ((is_int($level) and ! isset($levels[$level])) or (is_string($level) and ! array_search(strtoupper($level), $levels)))
		{
			throw new \FuelException('Invalid level "'.$level.'" passed to logger()');
		}

		// do we need to log the message with this level?
		if ( ! in_array($level, $loglabels))
		{
			// this entry should not be logged
			return false;
		}

		// this entry should be logged
		return $level;
	}

}
