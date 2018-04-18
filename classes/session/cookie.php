<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

// --------------------------------------------------------------------

class Session_Cookie extends \Session_Driver
{
	/**
	 * array of driver config defaults
	 */
	protected static $_defaults = array(
		'cookie_name'  => 'fuelcid',
	);

	// --------------------------------------------------------------------

	public function __construct($config = array())
	{
		parent::__construct($config);

		// merge the driver config with the global config
		$this->config = array_merge($config, (isset($config['cookie']) and is_array($config['cookie'])) ? $config['cookie'] : static::$_defaults);

		$this->config = $this->_validate_config($this->config);
	}

	// --------------------------------------------------------------------

	/**
	 * create a new session
	 *
	 * @return	\Session_Cookie
	 */
	protected function create()
	{
		// create the session
		parent::create();

		// no need for a previous id here
		unset($this->keys['previous_id']);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * read the session
	 *
	 * @param	boolean, set to true if we want to force a new session to be created
	 * @return	\Session_Driver
	 */
	protected function read($force = false)
	{
		// get the session cookie
		$payload = $this->_get_cookie();

		// validate it
		if ($force)
		{
			// a forced session reset
		}
		elseif ($payload === false)
		{
			// no cookie found
		}
		elseif ( ! isset($payload[0]) or ! is_array($payload[0]))
		{
			logger('DEBUG', 'Error: not a valid cookie payload!');
		}
		elseif ($payload[0]['updated'] + $this->config['expiration_time'] <= $this->time->get_timestamp())
		{
			logger('DEBUG', 'Error: session id has expired!');
		}
		elseif ($this->config['match_ip'] and $payload[0]['ip_hash'] !== md5(\Input::ip().\Input::real_ip()))
		{
			logger('DEBUG', 'Error: IP address in the session doesn\'t match this requests source IP!');
		}
		elseif ($this->config['match_ua'] and $payload[0]['user_agent'] !== \Input::user_agent())
		{
			logger('DEBUG', 'Error: User agent in the session doesn\'t match the browsers user agent string!');
		}
		else
		{
			// session is valid, retrieve the payload
			if (isset($payload[0]) and is_array($payload[0]))
			{
				$this->keys  = $payload[0];
			}
			if (isset($payload[1]) and is_array($payload[1]))
			{
				$this->data  = $payload[1];
			}
			if (isset($payload[2]) and is_array($payload[2]))
			{
				$this->flash = $payload[2];
			}
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * write the current session
	 *
	 * @return	\Session_Cookie
	 */
	protected function write()
	{
		// do we have something to write?
		if ( ! empty($this->keys) or ! empty($this->data) or ! empty($this->flash))
		{
			// rotate the session id if needed
			$this->rotate(false);

			// record the last update time of the session
			$this->keys['updated'] = $this->time->get_timestamp();

			// then update the cookie
			$this->_set_cookie(array($this->keys, $this->data, $this->flash));
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * validate a driver config value
	 *
	 * @param	array	array with configuration values
	 * @return	array	validated and consolidated config
	 */
	public function _validate_config($config)
	{
		$validated = array();

		foreach ($config as $name => $item)
		{
			// filter out any driver config
			if (!is_array($item))
			{
				switch ($name)
				{
					case 'cookie_name':
						if ( empty($item) or ! is_string($item))
						{
							$item = 'fuelcid';
						}
					break;

					default:
						// no config item for this driver
					break;
				}

				// global config, was validated in the driver
				$validated[$name] = $item;
			}
		}

		// validate all global settings as well
		return parent::_validate_config($validated);
	}
}
