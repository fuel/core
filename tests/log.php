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
 * Log class tests
 * 
 * @group Core
 * @group Log
 */
class Test_Log extends TestCase {
 	
	/**
	 * Test for Log::info()
	 * 
	 * @test
	 */
	public function test_info()
	{
		$output = Log::info('testing log info');
		$this->assertTrue($output);
	}
	
	/**
	 * Test for Log::debug()
	 * 
	 * @test
	 */	
	public function test_debug()
	{
		$output = Log::debug('testing log debug');
		$this->assertTrue($output);
	}

	/**
	 * Test for Log::error()
	 * 
	 * @test
	 */
	public function test_error()
	{
		$output = Log::error('testing log error');
		$this->assertTrue($output);
	}
	
	/**
	 * Test for Log::info()
	 * 
	 * @test
	 */
	public function test_info_method()
	{
		$output = Log::info('testing log info', 'Log::info');
		$this->assertTrue($output);
	}
	
	/**
	 * Test for Log::debug()
	 * 
	 * @test
	 */
	public function test_debug_method()
	{
		$output = Log::debug('testing log debug', 'Log::debug');
		$this->assertTrue($output);
	}
	
	/**
	 * Test for Log::error()
	 * 
	 * @test
	 */
	public function test_error_method()
	{
		$output = Log::error('testing log error', 'Log::error');
		$this->assertTrue($output);
	}
}
