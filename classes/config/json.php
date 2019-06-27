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

/**
 * JSON Config file parser
 */
class Config_Json extends \Config_File
{
	/**
	 * @var  string  the extension used by this JSON file parser
	 */
	protected $ext = '.json';

	/**
	 * Loads in the given file and parses it.
	 *
	 * @param   string  $file  File to load
	 * @return  array
	 */
	protected function load_file($file)
	{
		$contents = $this->parse_vars(file_get_contents($file));
		return json_decode($contents, true);
	}

	/**
	 * Returns the formatted config file contents.
	 *
	 * @param   array   $contents config array
	 * @return  string  formatted config file contents
	 */
	protected function export_format($contents)
	{
		$this->prep_vars($contents);
		return \Format::forge()->to_json($contents, true);
	}
}
