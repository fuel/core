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
 * Format class tests
 * 
 * @group Core
 * @group Format
 */
class Test_Format extends TestCase {
	
	public static function array_provider()
	{
		return array(
			array(
				array(
					array('field1' => 'Value 1', 'field2' => 35, 'field3' => 123123),
					array('field1' => 'Value 1', 'field2' => "Value\nline 2", 'field3' => 'Value 3'),
				),
				'field1,field2,field3
"Value 1","35","123123"
"Value 1","Value
line 2","Value 3"',
			),
		);
	}

	/**
	 * Test for Format::forge($foo, 'csv')->to_array()
	 *
	 * @test
	 * @dataProvider array_provider
	 */
	public function test_from_csv($array, $csv)
	{
		$this->assertEquals($array, Format::forge($csv, 'csv')->to_array());
	 
	}
	
	/**
	 * Test for Format::forge($foo)->to_csv()
	 *
	 * @test
	 * @dataProvider array_provider
	 */
	public function test_to_csv($array, $csv)
	{
		$this->assertEquals($csv, Format::forge($array)->to_csv());
	}
}
