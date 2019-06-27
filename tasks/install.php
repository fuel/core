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

namespace Fuel\Tasks;

/**
 * Install task
 *
 * Run this task to set default write permissions and environment stuff
 * for your app. This could be expanded in app/tasks for application specific stuff.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Phil Sturgeon
 */

class Install
{
	public static function run()
	{
		$writable_paths = array(APPPATH.'cache', APPPATH.'logs', APPPATH.'tmp', APPPATH.'config');

		foreach ($writable_paths as $path)
		{
			if (@chmod($path, 0777))
			{
				\Cli::write("\t".'Made writable: '.$path, 'green');
			}
			else
			{
				\Cli::write("\t".'Failed to make writable: '.$path, 'red');
			}
		}
	}
}
