<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Cli class tests
 * 
 * @group Core
 * @group Cli
 */
class Test_Cli extends TestCase {
 	
	public function test_exec_speed()
	{
		$start = time();
		exec('sleep 2');
		$stop = time();
		
		$this->assertEquals($start + 2, $stop);
	
	}

	public function test_spawn_speed()
	{
		$start = time();
		\Cli::spawn('sleep 2');
		$stop = time();

		$this->assertEquals($start, $stop);
	}
}
