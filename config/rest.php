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
	 *  Return Format
	 * -------------------------------------------------------------------------
	 *
	 *  Default format of the data to be returned.
	 *
	 */

	'default_format' => 'xml',

	/**
	 * -------------------------------------------------------------------------
	 *  XML Basenode
	 * -------------------------------------------------------------------------
	 *
	 */

	'xml_basenode' => 'xml',

	/**
	 * -------------------------------------------------------------------------
	 *  Realm
	 * -------------------------------------------------------------------------
	 *
	 *  Name for the password protected REST API displayed on login dialogs.
	 *
	 */

	'realm' => 'REST API',

	/**
	 * -------------------------------------------------------------------------
	 *  Authentication
	 * -------------------------------------------------------------------------
	 *
	 *  Authentication type.
	 *
	 *  Possible values are:
	 *
	 *      ''       = no login required.
	 *      'basic'  = unsecure login.
	 *      'digest' = more secure login.
	 *
	 *  Or, you can define a method name in your REST controller that handles
	 *  authorization.
	 *
	 */

	'auth' => '',

	/**
	 * -------------------------------------------------------------------------
	 *  Credentials
	 * -------------------------------------------------------------------------
	 *
	 *  Usernames and passwords for login.
	 *
	 *  The value is following this format:
	 *
	 *      array('username' => 'password')
	 *
	 */

	'valid_logins' => array('admin' => '1234'),

	/**
	 * -------------------------------------------------------------------------
	 *  Performance
	 * -------------------------------------------------------------------------
	 *
	 *  Disabling this setting will speed up your requests if you do not use
	 *  a 'ACCEPT' header.
	 *
	 */

	'ignore_http_accept' => false,
);
