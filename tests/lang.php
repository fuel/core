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
 * Lang class tests
 * 
 * @group Core
 * @group Lang
 */
class Test_Lang extends TestCase {
 
	/**
	 * Test for Lang::line()
	 * 
	 * @test
	 */	
	public function test_line()
	{
		Lang::load('test');
		$output = Lang::line('hello', array('name' => 'Bob'));
		$expected = 'Hello there Bob!';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Lang::line()
	 * 
	 * @test
	 */	
	public function test_line_invalid()
	{
		Lang::load('test');
		$output = Lang::line('non_existant_hello', array('name' => 'Bob'));
		$expected = 'non_existant_hello';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Lang::set()
	 * 
	 * @test
	 */	
	public function test_set_return_true()
	{
		$output = Lang::set('testing_set_valid', 'Ahoy :name!');
		$this->assertTrue($output);
	}
	
	/**
	 * Test for Lang::set()
	 * 
	 * @test
	 */
	public function test_set()
	{
		Lang::set('testing_set_valid', 'Ahoy :name!');
		$output = Lang::line('testing_set_valid', array('name' => 'Bob'));
		$expected = 'Ahoy Bob!';
		$this->assertEquals($expected, $output);
	}
}
