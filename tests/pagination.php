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
			'uri_segment'    => null,
			'pagination_url' => 'http://docs.fuelphp.com/?p={page}',
			'total_items'    => 100,
			'per_page'       => 10,
		);
	}

	public function test_get_total_pages()
	{
		$pagination = Pagination::forge('mypagination', $this->config);
		$pagination->current_page = 1;
		$test = $pagination->total_pages;
		$expected = 10;
		$this->assertEquals($expected, $test);
	}

	/**
	 * first page
	 *
	 */
	public function test_first_page()
	{
		$pagination = Pagination::forge('mypagination', $this->config);
		$pagination->current_page = 1;

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous-inactive"><a href="#">&laquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="active"><a href="#">1</a></span><span><a href="http://docs.fuelphp.com/?p=2">2</a></span><span><a href="http://docs.fuelphp.com/?p=3">3</a></span><span><a href="http://docs.fuelphp.com/?p=4">4</a></span><span><a href="http://docs.fuelphp.com/?p=5">5</a></span><span><a href="http://docs.fuelphp.com/?p=6">6</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next"><a href="http://docs.fuelphp.com/?p=2">&raquo;</a></span>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page
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

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span><a href="http://docs.fuelphp.com/?p=6">6</a></span><span><a href="http://docs.fuelphp.com/?p=7">7</a></span><span><a href="http://docs.fuelphp.com/?p=8">8</a></span><span><a href="http://docs.fuelphp.com/?p=9">9</a></span><span class="active"><a href="#">10</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous"><a href="http://docs.fuelphp.com/?p=9">&laquo;</a></span>';
		$this->assertEquals($expected, $output);
	}
}
