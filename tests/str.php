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
 * Str class tests
 * 
 * @group Core
 * @group Str
 */
class Tests_Str extends TestCase {

	public function truncate_provider()
	{
		return array(
			array(15, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'),
		);
	}

	/**
	 * Test for Str::truncate()
	 * 
	 * @test
	 * @dataProvider truncate_provider
	 */
	public function test_truncate_plain($limit, $string)
	{
		$output = Str::truncate($string, $limit);
		$expected = 'Lorem ipsum dol...';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Str::truncate()
	 * 
	 * @test
	 * @dataProvider truncate_provider
	 */
	public function test_truncate_custom_continuation($limit, $string)
	{
		$output = Str::truncate($string, $limit, '..');
		$expected = 'Lorem ipsum dol..';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Str::truncate()
	 * 
	 * @test
	 * @dataProvider truncate_provider
	 */
	public function test_truncate_not_html($limit, $string)
	{
		$string = '<h1>'.$string.'</h1>';
		
		$output = Str::truncate($string, $limit, '...', false);
		$expected = '<h1>Lorem ipsum...';
		$this->assertEquals($expected, $output);
		
		$output = Str::truncate($string, $limit, '...', true);
		$expected = '<h1>Lorem ipsum dol...</h1>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Str::truncate()
	 * 
	 * @test
	 * @dataProvider truncate_provider
	 */
	public function test_truncate_is_html($limit, $string)
	{
		$string = '<h1>'.$string.'</h1>';
		
		$output = Str::truncate($string, $limit, '...', true);
		$expected = '<h1>Lorem ipsum dol...</h1>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Str::truncate()
	 * 
	 * @test
	 * @dataProvider truncate_provider
	 */
	public function test_truncate_multiple_tags($limit, $string)
	{
		$limit = 400;
		$string = '<p><strong>'.$string.'</strong></p>';
		
		$output = Str::truncate($string, $limit, '...', true);
		$this->assertEquals($string, $output);
	}

	/**
	 * Test for Str::increment()
	 * 
	 * @test
	 */
	public function test_increment()
	{
		$values = array('valueA', 'valueB', 'valueC');
		
		for ($i = 0; $i < count($values); $i ++)
		{
			$output = Str::increment($values[$i], $i);
			$expected = $values[$i].'_'.$i;
			
			$this->assertEquals($expected, $output);
		}
	}
	
	/**
	 * Test for Str::lower()
	 * 
	 * @test
	 */
	public function test_lower()
	{
		$output = Str::lower('HELLO WORLD');
		$expected = "hello world";
		
		$this->assertEquals($expected, $output);
	}
	
	/**
	 * Test for Str::upper()
	 * 
	 * @test
	 */
	public function test_upper()
	{
		$output = Str::upper('hello world');
		$expected = "HELLO WORLD";
		
		$this->assertEquals($expected, $output);
	}
	
	/**
	 * Test for Str::lcfirst()
	 * 
	 * @test
	 */
	public function test_lcfirst()
	{
		$output = Str::lcfirst('Hello World');
		$expected = "hello World";
		
		$this->assertEquals($expected, $output);
	}
	
	/**
	 * Test for Str::ucfirst()
	 * 
	 * @test
	 */
	public function test_ucfirst()
	{
		$output = Str::ucfirst('hello world');
		$expected = "Hello world";

		$this->assertEquals($expected, $output);
	}
	
	/**
	 * Test for Str::ucwords()
	 * 
	 * @test
	 */
	public function test_ucwords()
	{
		$output = Str::ucwords('hello world');
		$expected = "Hello World";

		$this->assertEquals($expected, $output);
	}

}