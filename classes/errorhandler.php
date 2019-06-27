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
 * Exception class for standard PHP errors, this will make them catchable
 */
class PhpErrorException extends \ErrorException
{
	public static $count = 0;

	public static $loglevel = \Fuel::L_ERROR;

	/**
	 * Allow the error handler from recovering from error types defined in the config
	 */
	public function recover()
	{
		// handle the error based on the config and the environment we're in
		if (static::$count <= \Config::get('errors.throttle', 10))
		{
			if (\Fuel::$env != \Fuel::PRODUCTION and ($this->code & error_reporting()) == $this->code)
			{
				static::$count++;
				\Errorhandler::exception_handler($this);
			}
			else
			{
				logger(static::$loglevel, $this->code.' - '.$this->message.' in '.$this->file.' on line '.$this->line);
			}
		}
		elseif (\Fuel::$env != \Fuel::PRODUCTION
				and static::$count == (\Config::get('errors.throttle', 10) + 1)
				and ($this->severity & error_reporting()) == $this->severity)
		{
			static::$count++;
			\Errorhandler::notice('Error throttling threshold was reached, no more full error reports are shown.', true);
		}
	}
}

/**
 *
 */
class Errorhandler
{
	public static $loglevel = \Fuel::L_ERROR;

	public static $levels = array(
		0                   => 'Error',
		E_ERROR             => 'Fatal Error',
		E_WARNING           => 'Warning',
		E_PARSE             => 'Parsing Error',
		E_NOTICE            => 'Notice',
		E_CORE_ERROR        => 'Core Error',
		E_CORE_WARNING      => 'Core Warning',
		E_COMPILE_ERROR     => 'Compile Error',
		E_COMPILE_WARNING   => 'Compile Warning',
		E_USER_ERROR        => 'User Error',
		E_USER_WARNING      => 'User Warning',
		E_USER_NOTICE       => 'User Notice',
		E_STRICT            => 'Runtime Notice',
		E_RECOVERABLE_ERROR => 'Runtime Recoverable error',
		E_DEPRECATED        => 'Runtime Deprecated code usage',
		E_USER_DEPRECATED   => 'User Deprecated code usage',
	);

	public static $fatal_levels = array(E_PARSE, E_ERROR, E_USER_ERROR, E_COMPILE_ERROR);

	public static $non_fatal_cache = array();

	/**
	 * Native PHP shutdown handler
	 *
	 * @return  string
	 */
	public static function shutdown_handler()
	{
		$last_error = error_get_last();

		// Only show valid fatal errors
		if ($last_error AND in_array($last_error['type'], static::$fatal_levels))
		{
			$severity = static::$levels[$last_error['type']];
			$error = new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'], $last_error['line']);
			logger(static::$loglevel, $severity.' - '.$last_error['message'].' in '.$last_error['file'].' on line '.$last_error['line'], array('exception' => $error));

			if (\Fuel::$env != \Fuel::PRODUCTION)
			{
				static::show_php_error($error);
			}
			else
			{
				static::show_production_error($error);
			}

			exit(1);
		}
	}

	/**
	 * PHP Exception handler
	 *
	 * @param   Exception  $e  the exception
	 * @return  bool
	 */
	public static function exception_handler($e)
	{
		// make sure we've got something useful passed
		if ($e instanceOf \Exception or (PHP_VERSION_ID >= 70000 and $e instanceOf \Error))
		{
			if (method_exists($e, 'handle'))
			{
				return $e->handle();
			}

			$severity = ( ! isset(static::$levels[$e->getCode()])) ? $e->getCode() : static::$levels[$e->getCode()];
			logger(static::$loglevel, $severity.' - '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(), array('exception' => $e));

			if (\Fuel::$env != \Fuel::PRODUCTION)
			{
				static::show_php_error($e);
			}
			else
			{
				static::show_production_error($e);
			}
		}
		else
		{
			die('Something was passed to the Exception handler that was neither an Error or an Exception !!!');
		}

		return true;
	}

	/**
	 * PHP Error handler
	 *
	 * @param   int     $severity  the severity code
	 * @param   string  $message   the error message
	 * @param   string  $filepath  the path to the file throwing the error
	 * @param   int     $line      the line number of the error
	 * @return  bool    whether to continue with execution
	 */
	public static function error_handler($severity, $message, $filepath, $line)
	{
		// don't do anything if error reporting is disabled
		if (error_reporting() !== 0)
		{
			$fatal = (bool) ( ! in_array($severity, \Config::get('errors.continue_on', array())));

			if ($fatal)
			{
				throw new \PhpErrorException($message, $severity, 0, $filepath, $line);
			}
			else
			{
				// non-fatal, recover from the error
				$e = new \PhpErrorException($message, $severity, 0, $filepath, $line);
				$e->recover();
			}
		}

		return true;
	}

	/**
	 * Shows a small notice error, only when not in production or when forced.
	 * This is used by several libraries to notify the developer of certain things.
	 *
	 * @param   string  $msg          the message to display
	 * @param   bool    $always_show  whether to force display the notice or not
	 * @return  void
	 */
	public static function notice($msg, $always_show = false)
	{
		$trace = array_merge(array('file' => '(unknown)', 'line' => '(unknown)'), \Arr::get(debug_backtrace(), 1));
		logger(\Fuel::L_DEBUG, 'Notice - '.$msg.' in '.$trace['file'].' on line '.$trace['line']);

		if (\Fuel::$is_test or ( ! $always_show and (\Fuel::$env == \Fuel::PRODUCTION or \Config::get('errors.notices', true) === false)))
		{
			return;
		}

		$data['message']	= $msg;
		$data['type']		= 'Notice';
		$data['filepath']	= \Fuel::clean_path($trace['file']);
		$data['line']		= $trace['line'];
		$data['function']	= $trace['function'];

		echo \View::forge('errors'.DS.'php_short', $data, false);
	}

	/**
	 * Shows an error.  It will stop script execution if the error code is not
	 * in the errors.continue_on whitelist.
	 *
	 * @param   Exception  $e  the exception to show
	 * @return  void
	 */
	protected static function show_php_error($e)
	{
		$fatal = (bool) ( ! in_array($e->getCode(), \Config::get('errors.continue_on', array())));
		$data = static::prepare_exception($e, $fatal);

		if ($fatal)
		{
			$data['contents'] = ob_get_contents();
			while (ob_get_level() > 0)
			{
				ob_end_clean();
			}
			ob_start(\Config::get('ob_callback', null));
		}
		else
		{
			static::$non_fatal_cache[] = $data;
		}

		if (\Fuel::$is_cli)
		{
			\Cli::write(\Cli::color($data['severity'].' - '.$data['message'].' in '.\Fuel::clean_path($data['filepath']).' on line '.$data['error_line'], 'red'));
			if (\Config::get('cli_backtrace'))
			{
				\Cli::write('Stack trace:');
				\Cli::write(\Debug::backtrace($e->getTrace()));
			}

			if ( ! $fatal)
			{
				return;
			}

			exit(1);
		}

		if ($fatal)
		{
			if ( ! headers_sent())
			{
				$protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
				header($protocol.' 500 Internal Server Error');
			}

			$data['non_fatal'] = static::$non_fatal_cache;

			try
			{
				exit(\View::forge('errors'.DS.'php_fatal_error', $data, false));
			}
			catch (\FuelException $view_exception)
			{
				exit($data['severity'].' - '.$data['message'].' in '.\Fuel::clean_path($data['filepath']).' on line '.$data['error_line']);
			}
		}

		try
		{
			echo \View::forge('errors'.DS.'php_error', $data, false);
		}
		catch (\FuelException $e)
		{
			echo $e->getMessage().'<br />';
		}
	}

	/**
	 * Shows the errors/production view and exits.  This only gets
	 * called when an error occurs in production mode.
	 *
	 * @return  void
	 */
	protected static function show_production_error($e)
	{
		// when we're on CLI, always show the php error
		if (\Fuel::$is_cli)
		{
			return static::show_php_error($e);
		}

		if ( ! headers_sent())
		{
			$protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
			header($protocol.' 500 Internal Server Error');
		}
		exit(\View::forge('errors'.DS.'production'));
	}

	protected static function prepare_exception($e, $fatal = true)
	{
		$data = array();
		$data['type']		= get_class($e);
		$data['severity']	= $e->getCode();
		$data['message']	= $e->getMessage();
		$data['filepath']	= $e->getFile();
		$data['error_line']	= $e->getLine();
		$data['backtrace']	= $e->getTrace();

		// support for additional DB info
		if ($e instanceof \Database_Exception and $e->getDbCode())
		{
			$data['severity'] .= ' ('.$e->getDbCode().')';
		}

		$data['severity'] = ( ! isset(static::$levels[$data['severity']])) ? $data['severity'] : static::$levels[$data['severity']];

		// support for additional SoapFault info
		if ($e instanceof \SoapFault)
		{
			$data['soap']['faultcode'] = $e->faultcode;
			$data['soap']['faultstring'] = $e->faultstring;
			$data['soap']['errortype'] = empty($e->detail->ExceptionDetail) ? '' : $e->detail->ExceptionDetail->Type;
			$data['soap']['backtrace'] = empty($e->detail->ExceptionDetail) ? '' : $e->detail->ExceptionDetail->StackTrace;
			$data['soap']['backtrace'] = explode("\n", str_replace(array("\r\n","\n\r","\r"),"\n",$data['soap']['backtrace']));
		}

		foreach ($data['backtrace'] as $key => $trace)
		{
			if ( ! isset($trace['file']))
			{
				unset($data['backtrace'][$key]);
			}
			elseif ($trace['file'] == COREPATH.'classes/error.php')
			{
				unset($data['backtrace'][$key]);
			}
		}

		$data['debug_lines'] = \Debug::file_lines($data['filepath'], $data['error_line'], $fatal);
		$data['orig_filepath'] = $data['filepath'];
		$data['filepath'] = \Fuel::clean_path($data['filepath']);

		$data['filepath'] = str_replace("\\", "/", $data['filepath']);

		return $data;
	}

}
