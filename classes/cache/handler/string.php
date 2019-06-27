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

class Cache_Handler_String implements \Cache_Handler_Driver
{
	public function readable($contents)
	{
		return (string) $contents;
	}

	public function writable($contents)
	{
		return (string) $contents;
	}
}
