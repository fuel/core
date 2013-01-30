<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.5
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
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
	protected static $upload = false;

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

		// get the auto_process status
		$auto_process = isset($config['auto_process']) ? $config['auto_process'] : false;
		unset($config['auto_process']);

		// add the callbacks
		$config['langCallback'] = '\\Upload::langCallback';
		$config['moveCallback'] = null;

		// get an upload instance
		static::$upload = new \FuelPHP\Upload\Upload($config);

		// auto validate the files if required
		if ($auto_process)
		{
			static::$upload->validate();
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Lang callback function, translates Upload error messages
	 *
	 * @param  int  $error  Number of the error message we want the language string for
	 *
	 * @return  string  Language string retrieved
	 */
	public static function langCallback($error)
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
	public static function moveCallback($from, $to)
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
				$data[$item] = $value;
			}
			$data['field'] = str_replace('.', ':', $data['field']);
			$data['error'] = ! $file->isValid();
			$data['errors'] = array();
			$result[] = $data;
		}

		return $result;
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
				$item == 'element' and $item = 'field';
				$item == 'tmp_name' and $item = 'file';
				$data[$item] = $value;
			}
			$data['field'] = str_replace('.', ':', $data['field']);
			$data['error'] = ! $file->isValid();
			$data['errors'] = array();
			foreach ($file->getErrors() as $error)
			{
				$data['errors'][] = array($error->getError(), $error->getMessage());
			}
			$result[] = $data;
		}

		return $result;
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
		$event = str_replace(array('before', 'after', 'validate'), array('before_save', 'after_save', 'after_validate'), $event);

		static::$upload->register($event, $callback);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Normalize the $_FILES array and store the result in $files
	 *
	 * @return	void
	 */
	public static function process($config = array())
	{
		foreach (static::$upload->getValidFiles() as $file)
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
		static::$with_ftp = \Ftp::forge($config, $connect);
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
		foreach (static::$upload->getValidFiles() as $file)
		{
			$file->save();
		}
	}
}
