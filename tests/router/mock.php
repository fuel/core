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

/**
 * Mock for Router. Static functions are not fun to unit test.
 * PHPUnit 4 removes staticExpects, this mock class is a workaround.
 */

class Test_Router_Mock extends Router
{
	public static $check_class = null;
	public static $get_prefix = null;

	/**
	 * Proxy to $check_class.
	 *
	 * @see Router::check_class()
	 */
	protected static function check_class($class)
	{
		$callback =  static::$check_class;

		return $callback($class);
	}

	/**
	 * Proxy to $get_prefix.
	 *
	 * @see Router::get_prefix()
	 */
	protected static function get_prefix()
	{
		$callback =  static::$get_prefix;

		return $callback();
	}
}
