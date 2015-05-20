<?php

namespace Fuel\Core;

/**
 * DB config data parser
 */
class Config_Db implements Config_Interface
{
	protected $identifier;

	protected $ext = '.db';

	protected $vars = array();

	protected $database;

	protected $table;

	/**
	 * Sets up the file to be parsed and variables
	 *
	 * @param   string  $file  Config identifier name
	 * @param   array   $vars  Variables to parse in the data retrieved
	 * @return  void
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

		$this->database = \Config::get('config.database', null);
		$this->table = \Config::get('config.table_name', 'config');
	}

	/**
	 * Loads the config file(s).
	 *
	 * @param   bool  $overwrite  Whether to overwrite existing values
	 * @return  array  the config array
	 */
	public function load($overwrite = false, $cache = true)
	{
		$config = array();

		// try to retrieve the config from the database
		try
		{
			$result = \DB::select('config')->from($this->table)->where('identifier', '=', $this->identifier)->execute($this->database);
		}
		catch (Database_Exception $e)
		{
			// strip the actual query from the message
			$msg = $e->getMessage();
			$msg = substr($msg, 0, strlen($msg)  - strlen(strrchr($msg, ':')));

			// and rethrow it
			throw new \Database_Exception($msg);
		}

		// did we succeed?
		if ($result->count())
		{
			empty($result[0]['config']) or $config = unserialize($this->parse_vars($result[0]['config']));
		}

		return $config;
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
	 * @return  bool       DB result
	 */
	public function save($contents)
	{
		// prep the contents
		$this->prep_vars($contents);
		$contents = serialize($contents);

		// update the config in the database
		$result = \DB::update($this->table)->set(array('config' => $contents, 'hash' => uniqid()))->where('identifier', '=', $this->identifier)->execute($this->database);

		// if there wasn't an update, do an insert
		if ($result === 0)
		{
			list($notused, $result) = \DB::insert($this->table)->set(array('identifier' => $this->identifier, 'config' => $contents, 'hash' => uniqid()))->execute($this->database);
		}

		return $result === 1;
	}
}
