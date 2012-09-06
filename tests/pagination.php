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
 * Pagination class tests
 *
 * @group Core
 * @group Pagination
 */
class Test_Pagination extends TestCase
{
	public function setup()
	{
		Pagination::set_config(array(
			'pagination_url' => 'http://docs.fuelphp.com/',
			'uri_segment' => 2,
			'total_items' => 10,
			'per_page' => 5,
		));
	}

	/**
	 * first page: previous inactive
	 *
	 */
	public function test_previouslink_inactive()
	{
		Pagination::set_config(array(
			'current_page' => 1,
		));
		$output = Pagination::prev_link('prev');
		$expected = ' <span class="previous-inactive"><a href="#">&laquo; prev</a> </span>';
		$this->assertEquals($expected, $output);
	}

	public function test_previouslink_active()
	{
		Pagination::set_config(array(
			'current_page' => 2,
		));
		$output = Pagination::prev_link('prev');
		$expected = '<span class="previous"> <a href="http://docs.fuelphp.com">&laquo; prev</a> </span>';
		$this->assertEquals($expected, $output);
	}

	public function test_nextlink_active()
	{
		Pagination::set_config(array(
			'current_page' => 1,
		));
		$output = Pagination::next_link('next');
		$expected = '<span class="next"> <a href="http://docs.fuelphp.com/2">next &raquo;</a> </span>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page: next inactive
	 *
	 */
	public function test_nextlink_inactive()
	{
		Pagination::set_config(array(
			'current_page' => 2,
		));
		$output = Pagination::next_link('next');
		$expected = ' <span class="next-inactive"><a href="#">next &raquo;</a> </span>';
		$this->assertEquals($expected, $output);
	}

}
