<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.5
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Debug class tests
 *
 * @group Core
 * @group Debug
 */
class Test_Debug extends TestCase
{
 	public function test_debug_dump_normally()
 	{
 		// Set to browser mode.
 		\Fuel::$is_cli = false;

 		\Debug::dump(1, 2, 3);
 	}

  	public function test_debug_dump_by_call_user_func_array()
 	{
 		// Set to browser mode.
 		\Fuel::$is_cli = false;

 		call_user_func_array('\\Debug::dump', array(1, 2, 3));
 	}
}
