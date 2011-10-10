<?php

namespace Fuel\Core;

/**
 * PHP Config file parser
 */
class Config_Php extends \Config_File
{
	protected $ext = '.php';

	/**
	 * Loads in the given file and parses it.
	 *
	 * @param   string  $file  File to load
	 * @return  array
	 */
	protected function load_file($file)
	{
		return \Fuel::load($file);
	}
}
