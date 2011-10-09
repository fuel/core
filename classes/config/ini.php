<?php

namespace Fuel\Core;

/**
 * INI Config file parser
 */
class Config_Ini extends \Config_File
{
	protected $ext = '.ini';

	/**
	 * Loads in the given file and parses it.
	 *
	 * @param   string  $file  File to load
	 * @return  array
	 */
	protected function load_file($file)
	{
		$contents = $this->parse_vars(file_get_contents($file));
		return parse_ini_string($contents, true);
	}
}
