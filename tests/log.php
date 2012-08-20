<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Log class tests
 *
 * @group Core
 * @group Log
 */
class Test_Log extends TestCase
{

	public function setUp()
	{
		$this->log_threshold = \Config::get('log_threshold');
		// set the log threshold to a known value
		\Config::set('log_threshold', Fuel::L_DEBUG);
	}

	public function tearDown()
	{
		\Config::set('log_threshold', $this->log_threshold);
	}

	/**
	 * Test for Log::info()
	 *
	 * @test
	 */
	public function test_info()
	{
		$output = Log::info('testing log info');
		$this->assertFalse($output);	// log level is set to DEBUG
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
		$this->assertFalse($output);	// default log level is DEBUG
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

	/**
	 * Test for Log::write()
	 *
	 * @test
	 */
	public function test_write_custom_level()
	{
		$output = Log::write('Custom', 'testing custom level log', 'Log::write');
		$this->assertTrue($output);
	}
}
