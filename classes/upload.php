<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Upload Class
 *
 * @package		Fuel
 * @category	Core
 * @link		http://docs.fuelphp.com/classes/upload.html
 */
class Upload
{

	/* ---------------------------------------------------------------------------
	 * ERROR CODE CONSTANTS
	 * --------------------------------------------------------------------------- */

	// duplicate the PHP standard error codes for consistency
	const UPLOAD_ERR_OK         = UPLOAD_ERR_OK;
	const UPLOAD_ERR_INI_SIZE   = UPLOAD_ERR_INI_SIZE;
	const UPLOAD_ERR_FORM_SIZE  = UPLOAD_ERR_FORM_SIZE;
	const UPLOAD_ERR_PARTIAL    = UPLOAD_ERR_PARTIAL;
	const UPLOAD_ERR_NO_FILE    = UPLOAD_ERR_NO_FILE;
	const UPLOAD_ERR_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR;
	const UPLOAD_ERR_CANT_WRITE = UPLOAD_ERR_CANT_WRITE;
	const UPLOAD_ERR_EXTENSION  = UPLOAD_ERR_EXTENSION;

	// and add our own error codes
	const UPLOAD_ERR_MAX_SIZE             = 101;
	const UPLOAD_ERR_EXT_BLACKLISTED      = 102;
	const UPLOAD_ERR_EXT_NOT_WHITELISTED  = 103;
	const UPLOAD_ERR_TYPE_BLACKLISTED     = 104;
	const UPLOAD_ERR_TYPE_NOT_WHITELISTED = 105;
	const UPLOAD_ERR_MIME_BLACKLISTED     = 106;
	const UPLOAD_ERR_MIME_NOT_WHITELISTED = 107;
	const UPLOAD_ERR_MAX_FILENAME_LENGTH  = 108;
	const UPLOAD_ERR_MOVE_FAILED          = 109;
	const UPLOAD_ERR_DUPLICATE_FILE       = 110;
	const UPLOAD_ERR_MKDIR_FAILED         = 111;
	const UPLOAD_ERR_FTP_FAILED           = 112;

	/* ---------------------------------------------------------------------------
	 * STATIC PROPERTIES
	 * --------------------------------------------------------------------------- */
	/**
	 * @var object FuelPHP\Upload\Upload object
	 */
	protected static $upload = null;

	/**
	 * @var object Ftp object
	 */
	protected static $with_ftp = false;

	/* ---------------------------------------------------------------------------
	 * STATIC METHODS
	 * --------------------------------------------------------------------------- */

	/**
	 * class initialisation, load the config and process $_FILES if needed
	 *
	 * @return	void
	 */
	public static function _init()
	{
		// get the language file for this upload
		\Lang::load('upload', true);

		// get the config for this upload
		\Config::load('upload', true);

		// fetch the config
		$config = \Config::get('upload', array());

		// add the language callback to link into Fuel's Lang class
		$config['langCallback'] = '\\Upload::lang_callback';

		// get an upload instance
		if (class_exists('Fuel\Upload\Upload'))
		{
			static::$upload = new \Fuel\Upload\Upload($config);
		}

		// 1.6.1 fallback
		elseif (class_exists('FuelPHP\Upload\Upload'))
		{
			static::$upload = new \FuelPHP\Upload\Upload($config);
		}

		else
		{
			throw new \FuelException('Can not load \Fuel\Upload\Upload. Did you run composer to install it?');
		}

		// if auto-process is not enabled, load the uploaded files
		if ( ! $config['auto_process'])
		{
			static::$upload->processFiles();
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * return the Upload instance
	 *
	 * @return	\FuelPHP\Upload\Upload
	 */
	public static function instance()
	{
		return static::$upload;
	}

	// ---------------------------------------------------------------------------

	/**
	 * Lang callback function, translates Upload error messages
	 *
	 * @param  int  $error  Number of the error message we want the language string for
	 *
	 * @return  string  Language string retrieved
	 */
	public static function lang_callback($error)
	{
		return \Lang::get('upload.error_'.$error, array(), '');
	}

	// ---------------------------------------------------------------------------

	/**
	 * Move callback function, custom method to move an uploaded file. In Fuel 1.x
	 * this method is used for FTP uploads only
	 *
	 * @param  string  $file  The FQFN of the file to move
	 * @param  string  $file  The FQFN of the file destination
	 *
	 * @return  bool  Result of the move operation
	 */
	public static function move_callback($from, $to)
	{
		if (static::$with_ftp)
		{
			return static::$with_ftp->upload($from, $to, \Config::get('upload.ftp_mode'), \Config::get('upload.ftp_permissions'));
		}

		return false;
	}

	// ---------------------------------------------------------------------------

	/**
	 * Check if we have valid files
	 *
	 * @return	bool	true if static:$files contains uploaded files that are valid
	 */
	public static function is_valid()
	{
		return static::$upload->getValidFiles() == array() ? false : true;
	}

	// ---------------------------------------------------------------------------

	/**
	 * Get the list of validated files
	 *
	 * @return	array	list of uploaded files that are validated
	 */
	public static function get_files($index = null)
	{
		// convert element name formats
		is_string($index) and $index = str_replace(':', '.', $index);

		$files = static::$upload->getValidFiles($index);

		// convert the file object to 1.x compatible data
		$result = array();

		foreach ($files as $file)
		{
			$data = array();
			foreach ($file as $item => $value)
			{
				$item == 'element' and $item = 'field';
				$item == 'tmp_name' and $item = 'file';
				$item == 'filename' and $item = 'saved_as';
				$item == 'path' and $item = 'saved_to';
				$data[$item] = $value;
			}
			$data['field'] = str_replace('.', ':', $data['field']);
			$data['error'] = ! $file->isValid();
			$data['errors'] = array();
			$result[] = $data;
		}

		// compatibility with < 1.5, return the single entry if only one was found
		if (func_num_args() and count($result) == 1)
		{
			return reset($result);
		}
		else
		{
			return $result;
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Get the list of non-validated files
	 *
	 * @return	array	list of uploaded files that failed to validate
	 */
	public static function get_errors($index = null)
	{
		// convert element name formats
		is_string($index) and $index = str_replace(':', '.', $index);

		$files = static::$upload->getInvalidFiles($index);

		// convert the file object to 1.x compatible data
		$result = array();

		foreach ($files as $file)
		{
			$data = array();
			foreach ($file as $item => $value)
			{
				// swap item names for BC
				$item == 'element' and $item = 'field';
				$item == 'tmp_name' and $item = 'file';
				$item == 'filename' and $item = 'saved_as';
				$item == 'path' and $item = 'saved_to';
				$data[$item] = $value;
			}
			$data['field'] = str_replace('.', ':', $data['field']);
			$data['error'] = ! $file->isValid();
			$data['errors'] = array();
			foreach ($file->getErrors() as $error)
			{
				$data['errors'][] = array('error' => $error->getError(), 'message' => $error->getMessage());
			}
			$result[] = $data;
		}

		// compatibility with < 1.5, return the single entry if only one was found
		if (func_num_args() and count($result) == 1)
		{
			return reset($result);
		}
		else
		{
			return $result;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Register
	 *
	 * Registers a Callback for a given event
	 *
	 * @param	string	The name of the event
	 * @param	mixed	callback information
	 *
	 * @return	void
	 */
	public static function register($event, $callback)
	{
		// make sure we're setting the correct events
		$event = str_replace(array('before', 'after', 'validate'), array('before_save', 'after_save', 'after_validation'), $event);

		static::$upload->register($event, $callback);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Process the uploaded files, and run the validation
	 *
	 * @return	void
	 */
	public static function process($config = array())
	{
		foreach (static::$upload->getAllFiles() as $file)
		{
			$file->setConfig($config);
			$file->validate();
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Upload files with FTP
	 *
	 * @param   string|array  The name of the config group to use, or a configuration array.
	 * @param   bool          Automatically connect to this server.
	 */
	public static function with_ftp($config = 'default', $connect = true)
	{
		if (static::$with_ftp = \Ftp::forge($config, $connect))
		{
			// if we have an ftp object, activate the move callback
			static::$upload->setConfig('moveCallback', '\\Upload\\move_callback');
		}
		else
		{
			// creating the ftp object failed, disable the callback
			static::$upload->setConfig('moveCallback', null);
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * save uploaded file(s)
	 *
	 * @param	mixed	if int, $files element to move. if array, list of elements to move, if none, move all elements
	 * @param	string	path to move to
	 * @return	void
	 */
	public static function save()
	{
		// storage for arguments
		$path = null;
		$ids = array();

		// do we have any arguments
		if (func_num_args())
		{
			// process them
			foreach (func_get_args() as $arg)
			{
				if (is_string($arg))
				{
					$path = $arg;
				}
				elseif(is_numeric($arg))
				{
					in_array($arg, $ids) or $ids[] = $arg;
				}
				elseif(is_array($arg))
				{
					$ids = array_merge($ids, $arg);
				}
			}
		}

		// now process the files
		$counter = 0;
		foreach (static::$upload->getValidFiles() as $file)
		{
			// do we want to process this file?
			if ( ! empty($ids) and ! in_array($counter++, $ids))
			{
				// nope
				continue;
			}

			// was a custom path defined?
			$path and $file->setConfig('path', $path);

			// save the file
			$file->save();
		}
	}
}
