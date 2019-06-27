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

/**
 * Date class tests
 *
 * @group Core
 * @group Date
 */
class Test_Date extends TestCase
{
	protected function setUp()
	{
		// make sure the locale and language are is set correctly for the tests
		setlocale(LC_ALL, 'en_US') === false and setlocale(LC_ALL, 'en_US.UTF8');
		\Config::set('language', 'en');
	}

	/**
	 * Test for Date::days_in_month()
	 *
	 * @test
	 */
	public function test_days_in_month()
	{
		$output = Date::days_in_month(8);
		$expected = 31;
		$this->assertEquals($expected, $output);

		$output = Date::days_in_month(2, 2001);
		$expected = 28;
		$this->assertEquals($expected, $output);

		$output = Date::days_in_month(2, 2000);
		$expected = 29;
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::days_in_month(0)
	 * @expectedException UnexpectedValueException
	 * @test
	 */
	public function test_days_in_month_0_exception()
	{
		$output = Date::days_in_month(0);
	}

	/**
	 * Test for Date::days_in_month(13)
	 * @expectedException UnexpectedValueException
	 * @test
	 */
	public function test_days_in_month_13_exception()
	{
		$output = Date::days_in_month(13);
	}

	/**
	 * Test for Date::format()
	 *
	 * @test
	 */
	public function test_format()
	{
		$default_timezone = date_default_timezone_get();
		date_default_timezone_set('UTC');

		$output = Date::forge( 1294176140 )->format("%m/%d/%Y");
		$expected = "01/04/2011";

		date_default_timezone_set($default_timezone);

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::get_timestamp()
	 *
	 * @test
	 */
	public function test_get_timestamp()
	{
		$output = Date::forge( 1294176140 )->get_timestamp();
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
		$output = Date::forge( 1294176140, "Europe/London" )->get_timezone();
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
		$output = Date::forge( 1294176140 )->set_timezone("America/Chicago")->get_timezone();
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

	/**
	 * Test for Date::range_to_array()
	 *
	 * @test
	 */
	public function test_range_to_array()
	{
		$start = Date::create_from_string('2015-10-01', '%Y-%m-%d');
		$end   = Date::create_from_string('2016-03-01', '%Y-%m-%d');
		$range = Date::range_to_array($start, $end, "+1 month");

		$expected = array('2015-10-01', '2015-11-01', '2015-12-01', '2016-01-01', '2016-02-01', '2016-03-01');
		$output = array();
		foreach ($range as $r)
		{
			$output[] = $r->format('%Y-%m-%d');
		}

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::range_to_array()
	 *
	 * @test
	 */
	public function test_range_to_array_empty()
	{
		$start = Date::create_from_string('2016-03-01', '%Y-%m-%d');
		$end   = Date::create_from_string('2015-10-01', '%Y-%m-%d');
		$range = Date::range_to_array($start, $end, "+1 month");

		$expected = array();
		$output = array();
		foreach ($range as $r)
		{
			$output[] = $r->format('%Y-%m-%d');
		}

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::range_to_array()
	 *
	 * @test
	 */
	public function test_range_to_array_days()
	{
		$start = Date::create_from_string('2015-10-01', '%Y-%m-%d');
		$end   = Date::create_from_string('2015-10-05', '%Y-%m-%d');
		$range = Date::range_to_array($start, $end, "+4 days");

		$expected = array('2015-10-01', '2015-10-05');
		$output = array();
		foreach ($range as $r)
		{
			$output[] = $r->format('%Y-%m-%d');
		}

		$this->assertEquals($expected, $output);
	}

	/**
	 * Test for Date::range_to_array()
	 * @expectedException UnexpectedValueException
	 *
	 * @test
	 */
	public function test_range_to_array_invalid()
	{
		$start = Date::create_from_string('2015-10-01', '%Y-%m-%d');
		$end   = Date::create_from_string('2015-10-02', '%Y-%m-%d');
		$range = Date::range_to_array($start, $end, "-2 days");
	}
}
