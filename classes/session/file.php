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

// --------------------------------------------------------------------

class Session_File extends \Session_Driver
{
	/**
	 * array of driver config defaults
	 */
	protected static $_defaults = array(
		'cookie_name'    => 'fuelfid',				// name of the session cookie for file based sessions
		'path'           =>	'/tmp',					// path where the session files should be stored
		'gc_probability' =>	5,						// probability % (between 0 and 100) for garbage collection
	);

	// --------------------------------------------------------------------

	public function __construct($config = array())
	{
		parent::__construct($config);

		// merge the driver config with the global config
		$this->config = array_merge($config, is_array($config['file']) ? $config['file'] : static::$_defaults);

		$this->config = $this->_validate_config($this->config);
	}

	// --------------------------------------------------------------------

	/**
	 * Garbage Collector
	 *
	 * @return	bool
	 */
	public function gc()
	{
		// do some garbage collection
		if (mt_rand(0, 100) < $this->config['gc_probability'])
		{
			if ($handle = opendir($this->config['path']))
			{
				$expire = $this->time->get_timestamp() - $this->config['expiration_time'];

				while (($file = readdir($handle)) !== false)
				{
					if (filetype($this->config['path'] . $file) == 'file' and
						strpos($file, $this->config['cookie_name'].'_') === 0 and
						filemtime($this->config['path'] . $file) < $expire)
					{
						@unlink($this->config['path'] . $file);
					}
				}

				closedir($handle);
			}
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * destroy the current session
	 *
	 * @return	\Session_File
	 */
	public function destroy()
	{
		// do we have something to destroy?
		if ( ! empty($this->keys))
		{
			// delete the session file
			$file = $this->config['path'].$this->config['cookie_name'].'_'.$this->keys['session_id'];
			if (is_file($file))
			{
				unlink($file);
			}
		}

		parent::destroy();

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
		$cookie = $this->_get_cookie();

		// if a cookie was present, find the session record
		if ($cookie and ! $force and isset($cookie[0]))
		{
			// read the session file
			$payload = $this->_read_file($cookie[0]);

			if ($payload === false)
			{
				// cookie present, but session record missing. force creation of a new session
				return $this->read(true);
			}

			// unpack the payload
			$payload = $this->_unserialize($payload);

			// session referral?
			if (isset($payload['rotated_session_id']))
			{
				$payload = $this->_read_file($payload['rotated_session_id']);
				if ($payload === false)
				{
					// cookie present, but session record missing. force creation of a new session
					return $this->read(true);
				}
				else
				{
					// unpack the payload
					$payload = $this->_unserialize($payload);
				}
			}

			if ( ! isset($payload[0]) or ! is_array($payload[0]))
			{
				logger('DEBUG', 'Error: not a valid session file payload!');
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
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * write the session
	 *
	 * @return	\Session_File
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

			// session payload
			$payload = $this->_serialize(array($this->keys, $this->data, $this->flash));

			// create the session file
			$this->_write_file($this->keys['session_id'], $payload);

			// was the session id rotated?
			if ( isset($this->keys['previous_id']) and $this->keys['previous_id'] != $this->keys['session_id'])
			{
				// point the old session file to the new one, we don't want to lose the session
				$payload = $this->_serialize(array('rotated_session_id' => $this->keys['session_id']));
				$this->_write_file($this->keys['previous_id'], $payload);
			}

			// then update the cookie
			$this->_set_cookie(array($this->keys['session_id']));

			// Run garbage collector
			$this->gc();
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Writes the session file
	 *
	 * @param	$session_id
	 * @param	$payload
	 * @return	boolean, true if it was an existing session, false if not
	 * @throws	\FuelException
	 */
	protected function _write_file($session_id, $payload)
	{
		// create the session file
		$file = $this->config['path'].$this->config['cookie_name'].'_'.$session_id;
		$exists = is_file($file);
		$handle = fopen($file, 'c');
		if ($handle)
		{
			// wait for a lock
			while( ! flock($handle, LOCK_EX));

			// erase existing contents
			ftruncate($handle, 0);

			// write the session data
			fwrite($handle, $payload);

			// flush any pending output
			fflush($handle);

			// release the lock
			flock($handle, LOCK_UN);

			// close the file
			fclose($handle);
		}
		else
		{
			throw new \FuelException('Could not open the session file in "'.$this->config['path']." for write access");
		}

		return $exists;
	}

	// --------------------------------------------------------------------

	/**
	 * Reads the session file
	 *
	 * @param	$session_id
	 * @return	mixed, the payload if the file exists, or false if not
	 */
	protected function _read_file($session_id)
	{
		$payload = false;

		$file = $this->config['path'].$this->config['cookie_name'].'_'.$session_id;

		// normalize the file
		$file = realpath($file);

		// make sure it exists and is in the config path
		if (is_file($file) and strpos($file, $this->config['path']) === 0)
		{
			$handle = fopen($file, 'r');
			if ($handle)
			{
				// wait for a lock
				while( ! flock($handle, LOCK_SH));

				// read the session data
				$payload = file_get_contents($file);

				//release the lock
				flock($handle, LOCK_UN);

				// close the file
				fclose($handle);

			}
		}

		// only return the payload if it looks like a serialized array
		return strpos($payload, 'a:') === 0 ? $payload : false;
	}

	// --------------------------------------------------------------------

	/**
	 * validate a driver config value
	 *
	 * @param	array	$config		array with configuration values
	 * @return	array	validated and consolidated config
	 * @throws	\FuelException
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
							$item = 'fuelfid';
						}
					break;

					case 'path':
						// do we have a path?
						if ( empty($item) or ! is_dir($item))
						{
							throw new \FuelException('You have specify a valid path to store the session data files.');
						}
						// and can we write to it?
						if ( ! is_writable($item))
						{
							throw new \FuelException('The webserver doesn\'t have write access to the path to store the session data files.');
						}
						// update the path, unify the slashes, and add the trailing slash
						$item = realpath(str_replace(array('/', '\\'), DS, $item)).DS;
					break;

					case 'gc_probability':
						// do we have a path?
						if ( ! is_numeric($item) or $item < 0 or $item > 100)
						{
							// default value: 5%
							$item = 5;
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
