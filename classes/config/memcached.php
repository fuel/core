<?php

namespace Fuel\Core;

/**
 * DB config data parser
 */
class Config_Memcached implements Config_Interface
{
	/**
	 * @var array of driver config defaults
	 */
	protected static $config = array(
		'identifier' => 'config',
		'servers' => array(
			array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100),
		),
	);

	/**
	 * @var \Memcached	storage for the memcached object
	 */
	protected static $memcached = false;

	/**
	 * driver initialisation
	 *
	 * @throws \FuelException
	 */
	public static function _init()
	{
		static::$config = array_merge(static::$config, \Config::get('config.memcached', array()));

		if (static::$memcached === false)
		{
			// do we have the PHP memcached extension available
			if ( ! class_exists('Memcached') )
			{
				throw new \FuelException('Memcached config storage is required, but your PHP installation doesn\'t have the Memcached extension loaded.');
			}

			// instantiate the memcached object
			static::$memcached = new \Memcached();

			// add the configured servers
			static::$memcached->addServers(static::$config['servers']);

			// check if we can connect to all the server(s)
			$added = static::$memcached->getStats();
			foreach (static::$config['servers'] as $server)
			{
				$server = $server['host'].':'.$server['port'];
				if ( ! isset($added[$server]) or $added[$server]['pid'] == -1)
				{
					throw new \FuelException('Memcached config storage is required, but there is no connection possible. Check your configuration.');
				}
			}
		}
	}

	// --------------------------------------------------------------------

	protected $identifier;

	protected $ext = '.mem';

	protected $vars = array();

	/**
	 * Sets up the file to be parsed and variables
	 *
	 * @param   string  $identifier  Config identifier name
	 * @param   array   $vars        Variables to parse in the data retrieved
	 */
	public function __construct($identifier = null, $vars = array())
	{
		$this->identifier = $identifier;

		$this->vars = array(
			'APPPATH' => APPPATH,
			'COREPATH' => COREPATH,
			'PKGPATH' => PKGPATH,
			'DOCROOT' => DOCROOT,
		) + $vars;
	}

	/**
	 * Loads the config file(s).
	 *
	 * @param   bool  $overwrite  Whether to overwrite existing values
	 * @param   bool  $cache      This parameter will ignore in this implement.
	 * @return  array  the config array
	 */
	public function load($overwrite = false, $cache = true)
	{
		// fetch the config data from the Memcached server
		$result = static::$memcached->get(static::$config['identifier'].'_'.$this->identifier);

		return $result === false ? array() : $result;
	}

	/**
	 * Gets the default group name.
	 *
	 * @return  string
	 */
	public function group()
	{
		return $this->identifier;
	}

	/**
	 * Parses a string using all of the previously set variables.  Allows you to
	 * use something like %APPPATH% in non-PHP files.
	 *
	 * @param   string  $string  String to parse
	 * @return  string
	 */
	protected function parse_vars($string)
	{
		foreach ($this->vars as $var => $val)
		{
			$string = str_replace("%$var%", $val, $string);
		}

		return $string;
	}

	/**
	 * Replaces FuelPHP's path constants to their string counterparts.
	 *
	 * @param   array  $array  array to be prepped
	 * @return  array  prepped array
	 */
	protected function prep_vars(&$array)
	{
		static $replacements = false;

		if ($replacements === false)
		{
			foreach ($this->vars as $i => $v)
			{
				$replacements['#^('.preg_quote($v).'){1}(.*)?#'] = "%".$i."%$2";
			}
		}

		foreach ($array as $i => $value)
		{
			if (is_string($value))
			{
				$array[$i] = preg_replace(array_keys($replacements), array_values($replacements), $value);
			}
			elseif(is_array($value))
			{
				$this->prep_vars($array[$i]);
			}
		}
	}

	/**
	 * Formats the output and saved it to disc.
	 *
	 * @param   $contents  $contents    config array to save
	 * @throws  \FuelException
	 */
	public function save($contents)
	{
		// write it to the memcached server
		if (static::$memcached->set(static::$config['identifier'].'_'.$this->identifier, $contents, 0) === false)
		{
			throw new \FuelException('Memcached returned error code "'.static::$memcached->getResultCode().'" on write. Check your configuration.');
		}
	}
}
