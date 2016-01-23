<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

// --------------------------------------------------------------------

class Session_Db extends \Session_Driver
{
	/*
	 * @var	session database result object
	 */
	protected $record = null;

	/**
	 * array of driver config defaults
	 */
	protected static $_defaults = array(
		'cookie_name'    => 'fueldid',				// name of the session cookie for database based sessions
		'table'          => 'sessions',				// name of the sessions table
		'gc_probability' => 5,						// probability % (between 0 and 100) for garbage collection
	);

	// --------------------------------------------------------------------

	public function __construct($config = array())
	{
		// merge the driver config with the global config
		$this->config = array_merge($config, is_array($config['db']) ? $config['db'] : static::$_defaults);

		$this->config = $this->_validate_config($this->config);
	}

	// --------------------------------------------------------------------

	/**
	 * create a new session
	 *
	 * @return	\Session_Db
	 */
	public function create($payload = '')
	{
		// create a new session
		$this->keys['session_id']  = $this->_new_session_id();
		$this->keys['previous_id'] = $this->keys['session_id'];	// prevents errors if previous_id has a unique index
		$this->keys['ip_hash']     = md5(\Input::ip().\Input::real_ip());
		$this->keys['user_agent']  = \Input::user_agent();
		$this->keys['created']     = $this->time->get_timestamp();
		$this->keys['updated']     = $this->keys['created'];

		// add the payload
		$this->keys['payload'] = $payload;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * read the session
	 *
	 * @param	boolean, set to true if we want to force a new session to be created
	 * @return	\Session_Driver
	 */
	public function read($force = false)
	{
		// initialize the session
		$this->data = array();
		$this->keys = array();
		$this->flash = array();
		$this->record = null;

		// get the session cookie
		$cookie = $this->_get_cookie();

		// if a cookie was present, find the session record
		if ($cookie and ! $force and isset($cookie[0]))
		{
			// read the session record
			$this->record = \DB::select()->where('session_id', '=', $cookie[0])->from($this->config['table'])->execute($this->config['database']);

			// record found?
			if ($this->record->count())
			{
				$payload = $this->_unserialize($this->record->get('payload'));
			}
			else
			{
				// try to find the session on previous id
				$this->record = \DB::select()->where('previous_id', '=', $cookie[0])->from($this->config['table'])->execute($this->config['database']);

				// record found?
				if ($this->record->count())
				{
					$payload = $this->_unserialize($this->record->get('payload'));
				}
				else
				{
					// cookie present, but session record missing. force creation of a new session
					logger('DEBUG', 'Error: Session cookie with ID "'.$cookie[0].'" present but corresponding record is missing');
					return $this->read(true);
				}
			}

			if ( ! isset($payload[0]) or ! is_array($payload[0]))
			{
				logger('DEBUG', 'Error: not a valid db session payload!');
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

		return parent::read();
	}

	// --------------------------------------------------------------------

	/**
	 * write the current session
	 *
	 * @return	$this
	 * @throws	\Database_Exception
	 * @throws	\FuelException
	 */
	public function write()
	{
		// do we have something to write?
		if ( ! empty($this->keys) or ! empty($this->data) or ! empty($this->flash))
		{
			parent::write();

			// rotate the session id if needed
			$this->rotate(false);

			// record the last update time of the session
			$this->keys['updated'] = $this->time->get_timestamp();

			// add a random identifier, we need the payload to be absolutely unique
			$this->flash[$this->config['flash_id'].'::__session_identifier__'] = array('state' => 'expire', 'value' => sha1(uniqid(rand(), true)));

			// create the session record, and add the session payload
			$session = $this->keys;
			$session['payload'] = $this->_serialize(array($this->keys, $this->data, $this->flash));

			try
			{
				// do we need to create a new session?
				if (is_null($this->record))
				{
					// create the new session record
					list($notused, $result) = \DB::insert($this->config['table'], array_keys($session))->values($session)->execute($this->config['database']);
				}
				else
				{
					// update the database
					$result = \DB::update($this->config['table'])->set($session)->where('session_id', '=', $this->record->get('session_id'))->execute($this->config['database']);

					// if it failed, perhaps we have lost a session id due to rotation?
					if ($result === 0)
					{
						// if so, there must be a session record with our session_id as previous_id
						$result = \DB::select()->where('previous_id', '=', $this->record->get('session_id'))->from($this->config['table'])->execute($this->config['database']);
						if ($result->count())
						{
							logger(\Fuel::L_WARNING, 'Session update failed, session record recovered using previous id. Lost rotation data?');

							// update the session data
							$this->keys['session_id'] = $result->get('session_id');
							$this->keys['previous_id'] = $result->get('previous_id');

							// and recreate the payload
							$session = $this->keys;
							$session['payload'] = $this->_serialize(array($this->keys, $this->data, $this->flash));

							// and update the database
							$result = \DB::update($this->config['table'])->set($session)->where('session_id', '=', $this->keys['session_id'])->execute($this->config['database']);
						}
						else
						{
							logger(\Fuel::L_ERROR, 'Session update failed, session record could not be recovered using the previous id');
							$result = false;
						}
					}
				}

				// update went well?
				if ($result !== 0)
				{
					// then update the cookie
					$this->_set_cookie(array($this->keys['session_id']));
				}

				// Run garbage collector
				$this->gc();
			}
			catch (Database_Exception $e)
			{
				// strip the actual query from the message
				$msg = $e->getMessage();
				$msg = substr($msg, 0, strlen($msg)  - strlen(strrchr($msg, ':')));

				// and rethrow it
				throw new \Database_Exception($msg);
			}
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Garbage Collector
	 *
	 * @return	bool
	 */
	public function gc()
	{
		if (mt_rand(0, 100) < $this->config['gc_probability'])
		{
			$expired = $this->time->get_timestamp() - $this->config['expiration_time'];
			$result = \DB::delete($this->config['table'])->where('updated', '<', $expired)->execute($this->config['database']);
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * destroy the current session
	 *
	 * @return	\Session_Db
	 */
	public function destroy()
	{
		// do we have something to destroy?
		if ( ! empty($this->keys) and ! empty($this->record))
		{
			// delete the session record
			$result = \DB::delete($this->config['table'])->where('session_id', '=', $this->keys['session_id'])->execute($this->config['database']);
		}

		// reset the stored session data
		$this->record = null;

		parent::destroy();

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * validate a driver config value
	 *
	 * @param	array	$config	array with configuration values
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
							$item = 'fueldid';
						}
					break;

					case 'database':
						// do we have a database?
						if ( empty($item) or ! is_string($item))
						{
							\Config::load('db', true);
							$item = \Config::get('db.active', false);
						}
						if ($item === false)
						{
							throw new \FuelException('You have specify a database to use database backed sessions.');
						}
					break;

					case 'table':
						// and a table name?
						if ( empty($item) or ! is_string($item))
						{
							throw new \FuelException('You have specify a database table name to use database backed sessions.');
						}
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
