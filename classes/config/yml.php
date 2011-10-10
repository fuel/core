<?php

namespace Fuel\Core;

/**
 * Yaml Config file parser
 */
class Config_Yml extends \Config_File
{
	protected $ext = '.yml';

	/**
	 * Loads in the given file and parses it.
	 *
	 * @param   string  $file  File to load
	 * @return  array
	 */
	protected function load_file($file)
	{
		$contents = $this->parse_vars(file_get_contents($file));
		return \Format::forge($contents, 'yaml')->to_array();
	}
}
