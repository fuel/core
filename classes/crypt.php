<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

import('phpseclib/Crypt/AES', 'vendor');
import('phpseclib/Crypt/Hash', 'vendor');

use \PHPSecLib\Crypt_AES;
use \PHPSecLib\Crypt_Hash;

class Crypt {

	/*
	 * Crypto object used to encrypt/decrypt
	 *
	 * @var	object
	 */
	private static $crypter = null;

	/*
	 * Hash object used to generate hashes
	 *
	 * @var	object
	 */
	private static $hasher = null;

	/*
	 * Crypto configuration
	 *
	 * @var	array
	 */
	private static $config = array();

	/*
	 * initialisation and auto configuration
	 */
	public static function _init()
	{
		static::$crypter = new Crypt_AES();
		static::$hasher = new Crypt_Hash('sha256');

		// load the config
		\Config::load('crypt', true);
		static::$config = \Config::get('crypt', array ());

		// generate a crypto key if we don't have one
		if ( ! isset(static::$config['crypto_key']))
		{
			$crypto_key = '';
			for ($i = 0; $i < 8; $i++) {
				$crypto_key.= static::safe_b64encode(pack('n', mt_rand(0, 0xFFFF)));
			}
			static::$config['crypto_key'] = $crypto_key;
		}

		// generate a crypto iv if we don't have one
		if ( ! isset(static::$config['crypto_iv']))
		{
			$crypto_iv = '';
			for ($i = 0; $i < 8; $i++) {
				$crypto_iv .= static::safe_b64encode(pack('n', mt_rand(0, 0xFFFF)));
			}
			static::$config['crypto_iv'] = $crypto_iv;
		}

		// generate a hmac hash key if we don't have one
		if ( ! isset(static::$config['crypto_hmac']))
		{
			$crypto_hmac = '';
			for ($i = 0; $i < 8; $i++) {
				$crypto_hmac.= static::safe_b64encode(pack('n', mt_rand(0, 0xFFFF)));
			}
			static::$config['crypto_hmac'] = $crypto_hmac;
		}


		// update the config if new keys were generated
		if (isset($crypto_key) || isset($crypto_iv) || isset($crypto_hmac))
		{
			\Config::save('crypt', static::$config);
		}

		static::$crypter->enableContinuousBuffer();

		static::$hasher->setKey(static::safe_b64decode(static::$config['crypto_hmac']));
	}

	// --------------------------------------------------------------------

	/*
	 * encrypt a string value, optionally with a custom key
	 *
	 * @param	string	value to encrypt
	 * @param	string	optional custom key to be used for this encryption
	 * @access	public
	 * @return	string	encrypted value
	 */
	public static function encode($value, $key = false)
	{
		$key ? static::$crypter->setKey($key) : static::$crypter->setKey(static::safe_b64decode(static::$config['crypto_key']));
		static::$crypter->setIV(static::safe_b64decode(static::$config['crypto_iv']));

		$value = static::$crypter->encrypt($value);
		return static::safe_b64encode(static::add_hmac($value));

	}

	// --------------------------------------------------------------------

	/*
	 * decrypt a string value, optionally with a custom key
	 *
	 * @param	string	value to decrypt
	 * @param	string	optional custom key to be used for this encryption
	 * @access	public
	 * @return	string	encrypted value
	 */
	public static function decode($value, $key = false)
	{
		$key ? static::$crypter->setKey($key) : static::$crypter->setKey(static::safe_b64decode(static::$config['crypto_key']));
		static::$crypter->setIV(static::safe_b64decode(static::$config['crypto_iv']));

		$value = static::safe_b64decode($value);
		if ($value = static::validate_hmac($value))
		{
			return static::$crypter->decrypt($value);
		}
		else
		{
			return false;
		}
	}

	// --------------------------------------------------------------------

	private static function safe_b64encode($value)
	{
		$data = base64_encode($value);
		$data = str_replace(array('+','/','='),array('-','_',''),$data);
		return $data;
	}

	private static function safe_b64decode($value)
	{
		$data = str_replace(array('-','_'),array('+','/'),$value);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}

	private static function add_hmac($value)
	{
		// calculate the hmac-sha256 hash of this value
		$hmac = static::safe_b64encode(static::$hasher->hash($value));

		// append it and return the hmac protected string
		return $value.$hmac;
	}

	private static function validate_hmac($value)
	{
		// strip the hmac-sha256 hash from the value
		$hmac = substr($value, strlen($value)-43);

		// and remove it from the value
		$value = substr($value, 0, strlen($value)-43);

		// only return the value if it wasn't tampered with
		return (static::safe_b64encode(static::$hasher->hash($value)) === $hmac) ? $value : false;
	}
}

/* End of file crypt.php */
