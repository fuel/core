<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  [!] NOTICE
 * -----------------------------------------------------------------------------
 *
 *  If you need to make modifications to the default configuration,
 *  copy this file to your 'app/config' folder, and make them in there.
 *
 *  This will allow you to upgrade FuelPHP without losing your custom config.
 *
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Behavior
	 * -------------------------------------------------------------------------
	 *
	 *  If true, the '$_FILES' array will be processed when the class is loaded.
	 *
	 */

	'auto_process' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - Max Size
	 * -------------------------------------------------------------------------
	 *
	 *  Maximum size of the uploaded file in bytes.
	 *
	 *  0 = no maximum limit.
	 *
	 */

	'max_size' => 0,

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - Extensions Whitelist
	 * -------------------------------------------------------------------------
	 *
	 *  List of file extensions which are allowed to upload.
	 *
	 */

	'ext_whitelist' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - Extensions Blacklist
	 * -------------------------------------------------------------------------
	 *
	 *  List of file extensions which are NOT allowed to upload.
	 *
	 */

	'ext_blacklist' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - File Type Whitelist
	 * -------------------------------------------------------------------------
	 *
	 *  List of file types which are allowed to upload.
	 *
	 *  Type is the part of the mime-type, before the slash.
	 *
	 *  Example:
	 *
	 *      If mime-type = 'image/jpeg', then type = 'image';
	 *
	 */

	'type_whitelist' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - File Type Blacklist
	 * -------------------------------------------------------------------------
	 *
	 *  List of file types which are NOT allowed to upload.
	 *
	 */

	'type_blacklist' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - MIME Type Whitelist
	 * -------------------------------------------------------------------------
	 *
	 *  List of MIME types which are allowed to upload.
	 *
	 */

	'mime_whitelist' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Validation - MIME Type Whitelist
	 * -------------------------------------------------------------------------
	 *
	 *  List of MIME types which are NOT allowed to upload.
	 *
	 */

	'mime_blacklist' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Prefix
	 * -------------------------------------------------------------------------
	 *
	 *  Prefix given to every file when saved.
	 *
	 */

	'prefix' => '',

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Suffix
	 * -------------------------------------------------------------------------
	 *
	 *  Suffix given to every file when saved.
	 *
	 */

	'suffix' => '',

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Extension
	 * -------------------------------------------------------------------------
	 *
	 *  Replace the extension of the uploaded file by this extension.
	 *
	 */

	'extension' => '',

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Path
	 * -------------------------------------------------------------------------
	 *
	 *  Default path the uploaded files will be saved to.
	 *
	 */

	'path' => '',

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Path Creation
	 * -------------------------------------------------------------------------
	 *
	 *  Create the path if it doesn't exist.
	 *
	 */

	'create_path' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Path Permission
	 * -------------------------------------------------------------------------
	 *
	 *  Permissions to be set on the path after creation.
	 *
	 */

	'path_chmod' => 0777,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - File Permission
	 * -------------------------------------------------------------------------
	 *
	 *  Permissions to be set on the uploaded file after being saved.
	 *
	 */

	'file_chmod' => 0666,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - File Naming
	 * -------------------------------------------------------------------------
	 *
	 *  If true, add a number suffix to the file if the file already exists.
	 *
	 */

	'auto_rename' => true,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - File Overwriting
	 * -------------------------------------------------------------------------
	 *
	 *  If true, overwrite the file if it already exists
	 *  (only if 'auto_rename' is false).
	 *
	 */

	'overwrite' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - File Random Naming
	 * -------------------------------------------------------------------------
	 *
	 *  If true, generate a random filename for the file being saved.
	 *
	 */

	'randomize' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Normalize File Naming
	 * -------------------------------------------------------------------------
	 *
	 *  If true, normalize the filename (convert to ASCII, replace spaces
	 *  with underscores).
	 *
	 */

	'normalize' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Style for File Naming
	 * -------------------------------------------------------------------------
	 *
	 *  Change case of file name.
	 *
	 *  Case will be changed after all other transformations.
	 *
	 *  Valid values are 'upper', 'lower', and false.
	 *
	 */

	'change_case' => false,

	/**
	 * -------------------------------------------------------------------------
	 *  Saving - Size for File Naming
	 * -------------------------------------------------------------------------
	 *
	 *  Maximum length of the filename after all name modifications
	 *  have been made.
	 *
	 *  0 = no maximum limit.
	 *
	 */

	'max_length' => 0,
);
