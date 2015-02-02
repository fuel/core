<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2015 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

use \PHPSecLib\Crypt_AES;
use \PHPSecLib\Crypt_Hash;

class Crypt
{
	/**
	 * All the Crypt instances
	 *
	 * @var  array
	 */
	protected static $instances = array();

	/**
	 * Default crypto configuration
	 *
	 * @var	array
	 */
	protected static $defaults = array();

	/**
	 * initialisation and auto configuration
	 */
	public static function _init()
	{
		// load the default config
		\Config::load('crypt', true);
		static::$defaults = \Config::get('crypt', array ());

		// generate random crypto keys if we don't have them or they are incorrect length
		$update = false;
		foreach(array('crypto_key', 'crypto_iv', 'crypto_hmac') as $key)
		{
			if ( empty(static::$defaults[$key]) or (strlen(static::$defaults[$key]) % 4) != 0)
			{
				$crypto = '';
				for ($i = 0; $i < 8; $i++)
				{
					$crypto .= $this->_safe_b64encode(pack('n', mt_rand(0, 0xFFFF)));
				}
				static::$defaults[$key] = $crypto;
				$update = true;
			}
		}

		// update the config if needed
		if ($update === true)
		{
			try
			{
				\Config::save('crypt', static::$defaults);
			}
			catch (\FileAccessException $e)
			{
				// failed to write the config file, inform the user
				echo \View::forge('errors/crypt_keys', array(
					'keys' => static::$defaults
				));
				die();
			}
		}
	}

	/**
	 * Acts as a Multiton.  Will return the requested instance, or will create
	 * a new named one if it does not exist.
	 *
	 * @param   string    $name  The instance name
	 *
	 * @return  Crypt
	 */
	public static function instance($name = '_default_', array $config = array())
	{
		if ( ! \array_key_exists($name, static::$instances))
		{
			static::$instances[$name] = static::forge($config);
		}

		return static::$instances[$name];
	}

	/**
	 * Gets a new instance of the Crypt class.
	 *
	 * @param   array  $config  Optional config override
	 * @return  Crypt
	 */
	public static function forge(array $config = array())
	{
		return new static($config);
	}

	/**
	 * Magic method, capture static calls to this class
	 *
	 * @var  string  name of the method called
	 * @var  array   arguments to be passed to the method
	 *
	 * @return mixed
	 */
	public static function __callStatic ($method, array $args)
	{
		// fetch the default instance
		$instance = static::instance();

		// make sure the called method exists
		if (method_exists($instance, $method))
		{
			return call_user_func_array(array($instance, $method), $args);
		}

		// oops, it didn't...
		trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
	}

	/**
	 * Crypto object used to encrypt/decrypt
	 *
	 * @var	object
	 */
	protected $crypter = null;

	/**
	 * Hash object used to generate hashes
	 *
	 * @var	object
	 */
	protected $hasher = null;

	/**
	 * Crypto configuration
	 *
	 * @var	array
	 */
	protected $config = array();

	/**
	 * Sets up the theme object.  If a config is given, it will not use the config
	 * file.
	 *
	 * @param   array  $config  Optional config override
	 * @return  void
	 */
	public function __construct(array $config = array())
	{
		// allow config overloading for a specific instance
		$this->config = array_merge(static::$defaults, $config);

		// create the crypter and hasher objects
		$this->crypter = new Crypt_AES();
		$this->hasher = new Crypt_Hash('sha256');

		// and initialize them
		$this->crypter->enableContinuousBuffer();
		$this->hasher->setKey($this->_safe_b64decode($this->config['crypto_hmac']));
	}

	/**
	 * Magic method, capture calls to this class
	 *
	 * @var  string  name of the method called
	 * @var  array   arguments to be passed to the method
	 *
	 * @return mixed
	 */
	public function __call($method, array $args)
	{
		// make sure the called method exists
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $args);
		}

		// oops, it didn't...
		trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
	}

	/**
	 * encrypt a string value, optionally with a custom key
	 *
	 * @param	string	value to encrypt
	 * @param	string	optional custom key to be used for this encryption
	 * @param	int	optional key length
	 * @access	public
	 * @return	string	encrypted value
	 */
	protected function encode($value, $key = false, $keylength = false)
	{
		if ( ! $key)
		{
			$key = $this->_safe_b64decode($this->config['crypto_key']);
			// Used for backwards compatibility with encrypted data prior
			// to FuelPHP 1.7.2, when phpseclib was updated, and became a
			// bit smarter about figuring out key lengths.
			$keylength = 128;
		}

		if ($keylength)
		{
			$this->crypter->setKeyLength($keylength);
		}

		$this->crypter->setKey($key);
		$this->crypter->setIV($this->_safe_b64decode($this->config['crypto_iv']));

		$value = $this->crypter->encrypt($value);
		return $this->_safe_b64encode($this->_add_hmac($value));
	}

	// --------------------------------------------------------------------

	/**
	 * decrypt a string value, optionally with a custom key
	 *
	 * @param	string	value to decrypt
	 * @param	string	optional custom key to be used for this encryption
	 * @param	int	optional key length
	 * @access	public
	 * @return	string	encrypted value
	 */
	protected function decode($value, $key = false, $keylength = false)
	{
		if ( ! $key)
		{
			$key = $this->_safe_b64decode($this->config['crypto_key']);
			// Used for backwards compatibility with encrypted data prior
			// to FuelPHP 1.7.2, when phpseclib was updated, and became a
			// bit smarter about figuring out key lengths.
			$keylength = 128;
		}

		if ($keylength)
		{
			$this->crypter->setKeyLength($keylength);
		}

		$this->crypter->setKey($key);
		$this->crypter->setIV($this->_safe_b64decode($this->config['crypto_iv']));

		$value = $this->_safe_b64decode($value);
		if ($value = $this->_validate_hmac($value))
		{
			return $this->crypter->decrypt($value);
		}
		else
		{
			return false;
		}
	}

	// --------------------------------------------------------------------

	protected function _safe_b64encode($value)
	{
		$data = base64_encode($value);
		$data = str_replace(array('+','/','='), array('-','_',''), $data);
		return $data;
	}

	protected function _safe_b64decode($value)
	{
		$data = str_replace(array('-','_'), array('+','/'), $value);
		$mod4 = strlen($data) % 4;
		if ($mod4)
		{
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}

	protected function _add_hmac($value)
	{
		// calculate the hmac-sha256 hash of this value
		$hmac = $this->_safe_b64encode($this->hasher->hash($value));

		// append it and return the hmac protected string
		return $value.$hmac;
	}

	protected function _validate_hmac($value)
	{
		// strip the hmac-sha256 hash from the value
		$hmac = substr($value, strlen($value)-43);

		// and remove it from the value
		$value = substr($value, 0, strlen($value)-43);

		// only return the value if it wasn't tampered with
		return ($this->_secure_compare($this->_safe_b64encode($this->hasher->hash($value)), $hmac)) ? $value : false;
	}

	protected function _secure_compare($a, $b)
	{
		// make sure we're only comparing equal length strings
		if (strlen($a) !== strlen($b))
		{
			return false;
		}

		// and that all comparisons take equal time
		$result = 0;
		for ($i = 0; $i < strlen($a); $i++)
		{
			$result |= ord($a[$i]) ^ ord($b[$i]);
		}
		return $result === 0;
	}
}
