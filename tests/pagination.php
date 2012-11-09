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
		$this->config = array(
			'pagination_url' => 'http://docs.fuelphp.com/',
			'uri_segment'    => 2,
			'total_items'    => 100,
			'per_page'       => 10,
		);
	}

	/**
	 * first page: previous inactive
	 *
	 */
	public function test_previouslink_inactive()
	{
		$pagination = Pagination::forge('mypagination', $this->config);
		$pagination->current_page = 1;
		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous-inactive"><a href="#">&laquo;</a></span>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * first page: next active
	 *
	 */
	public function test_nextlink_active()
	{
		$pagination = Pagination::forge('mypagination', $this->config);
		$pagination->current_page = 1;
		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next"><a href="http://docs.fuelphp.com/2">&raquo;</a></span>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page: next inactive
	 *
	 */
	public function test_nextlink_inactive()
	{
		$pagination = Pagination::forge('mypagination', $this->config);
		$pagination->current_page = 10;
		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next-inactive"><a href="#">&raquo;</a></span>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page: previous active
	 *
	 */
	public function test_previouslink_active()
	{
		$pagination = Pagination::forge('mypagination', $this->config);
		$pagination->current_page = 10;
		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous"><a href="http://docs.fuelphp.com/9">&laquo;</a></span>';
		$this->assertEquals($expected, $output);
	}
}
