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
 * Date class tests
 * 
 * @group Core
 * @group Date
 */
class Tests_Date extends TestCase {

	/**
	 * Test for Date::days_in_month()
	 * 
	 * @test
	 */
	public function test_days_in_month()
	{
		$output = Date::days_in_month(2);
		$expected = 28;
		$this->assertEquals($expected, $output);

		$output = Date::days_in_month(2,2000);
		$expected = 29;
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::format()
	 * 
	 * @test
	 */
	public function test_format()
	{
		date_default_timezone_set('UTC');
		
		$output = Date::Factory( 1294176140 )->format("%m/%d/%Y");
		$expected = "01/04/2011";

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::get_timestamp()
	 * 
	 * @test
	 */
	public function test_get_timestamp()
	{
		$output = Date::Factory( 1294176140 )->get_timestamp();
		$expected = 1294176140;

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::get_timezone()
	 * 
	 * @test
	 */
	public function test_get_timezone()
	{
		$output = Date::Factory( 1294176140, "Europe/London" )->get_timezone();
		$expected = "Europe/London";

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::set_timezone()
	 * 
	 * @test
	 */
	public function test_set_timezone()
	{
		$output = Date::Factory( 1294176140 )->set_timezone("America/Chicago")->get_timezone();
		$expected = "America/Chicago";
		
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::time_ago()
	 * 
	 * @test
	 */
	public function test_time_ago_null_timestamp()
	{
		$output = Date::time_ago(null);
		
		$this->assertEquals(null, $output);
	}

	/**
	 * Test for Date::time_ago()
	 * 
	 * @test
	 */
	public function test_time_ago_one_month()
	{
		$march_30_2011 = 1301461200;
		$april_30_2011 = 1304139600;
		$output = Date::time_ago($march_30_2011, $april_30_2011);
		
		$this->assertEquals('1 month ago', $output);
	}

	/**
	 * Test for Date::time_ago()
	 * 
	 * @test
	 */
	public function test_time_ago_two_months()
	{
		$march_30_2011 = 1301461200;
		$may_30_2011 = 1306731600;
		
		$output = Date::time_ago($march_30_2011, $may_30_2011);
		
		$this->assertEquals('2 months ago', $output);
	}
}

/* End of file DateTest.php */