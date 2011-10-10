<?php

namespace Fuel\Core;

/**
 * A base Config File class for File based configs.
 */
abstract class Config_File implements Config_Interface
{
	protected $file;

	protected $vars = array();

	/**
	 * Sets up the file to be parsed and variables
	 *
	 * @param   string  $file  Config file name
	 * @param   array   $vars  Variables to parse in the file
	 * @return  void
	 */
	public function __construct($file, $vars = array())
	{
		$this->file = $file;

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
	 * @return  array  the config array
	 */
	public function load($overwrite = false)
	{
		$paths = $this->find_file();
		$config = array();

		foreach ($paths as $path)
		{
			$config = $overwrite ?
			              array_merge($config, $this->load_file($path)) :
			              \Arr::merge($config, $this->load_file($path));
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
		return pathinfo($this->file, PATHINFO_FILENAME);
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
	 * Finds the given config files
	 *
	 * @param   bool  $multiple  Whether to load multiple files or not
	 * @return  array
	 */
	protected function find_file($multiple = true)
	{
		$paths = \Fuel::find_file('config', $this->file, $this->ext, $multiple);
		$paths = array_merge(\Fuel::find_file('config/' . \Fuel::$env, $this->file, $this->ext, $multiple), $paths);

		if (count($paths) > 0)
		{
			return array_reverse($paths);
		}

		return array();
	}

	/**
	 * Must be implemented by child class.  Gets called for each file to load.
	 */
	abstract protected function load_file($file);
}
