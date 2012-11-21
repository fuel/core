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
	public static function tearDownAfterClass()
	{
		// reset Request::$main
		$request = \Request::forge();
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, false);
		// reset Request::$active
		$rp = new \ReflectionProperty($request, 'active');
		$rp->setAccessible(true);
		$rp->setValue($request, false);
	}

/**********************************
 * Tests for URI Segment Pagination
 **********************************/

	protected function set_uri_segment_config()
	{
		$this->config = array(
			'uri_segment'    => 3,
			'pagination_url' => 'http://docs.fuelphp.com/welcome/index/',
			'total_items'    => 100,
			'per_page'       => 10,
		);
	}

	public function test_uri_segment_auto_detect_pagination_url()
	{
		// set Request::$main
		$request = \Request::forge('welcome/index/3');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);
		// set Request::$active
		$rp = new \ReflectionProperty($request, 'active');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);
		// set base_url
		Config::set('base_url', 'http://docs.fuelphp.com/welcome/index/');
		$uri = new \Uri('/welcome/index/3');

		$this->set_uri_segment_config();
		$this->config['pagination_url'] = null;

		$pagination = Pagination::forge(__METHOD__, $this->config);
		$test = $pagination->pagination_url;
		$expected = 'http://docs.fuelphp.com/welcome/index/welcome/index/{page}';
		$this->assertEquals($expected, $test);

		// reset base_url
		Config::set('base_url', null);
		// reset Request::$active
		$rp->setValue($request, false);
	}

	public function test_uri_segment_get_total_pages()
	{
		// set Request::$main
		$request = \Request::forge('welcome/index/');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);

		$this->set_uri_segment_config();

		$pagination = Pagination::forge(__METHOD__, $this->config);
		$test = $pagination->total_pages;
		$expected = 10;
		$this->assertEquals($expected, $test);
	}

	/**
	 * first page
	 *
	 */
	public function test_uri_segment_first_page()
	{
		// set Request::$main
		$request = \Request::forge('welcome/index/');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);

		$this->set_uri_segment_config();

		$pagination = Pagination::forge(__METHOD__, $this->config);

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous-inactive"><a href="#">&laquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="active"><a href="#">1</a></span><span><a href="http://docs.fuelphp.com/welcome/index/2">2</a></span><span><a href="http://docs.fuelphp.com/welcome/index/3">3</a></span><span><a href="http://docs.fuelphp.com/welcome/index/4">4</a></span><span><a href="http://docs.fuelphp.com/welcome/index/5">5</a></span><span><a href="http://docs.fuelphp.com/welcome/index/6">6</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next"><a href="http://docs.fuelphp.com/welcome/index/2">&raquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<div class="pagination"><span class="previous-inactive"><a href="#">&laquo;</a></span><span class="active"><a href="#">1</a></span><span><a href="http://docs.fuelphp.com/welcome/index/2">2</a></span><span><a href="http://docs.fuelphp.com/welcome/index/3">3</a></span><span><a href="http://docs.fuelphp.com/welcome/index/4">4</a></span><span><a href="http://docs.fuelphp.com/welcome/index/5">5</a></span><span><a href="http://docs.fuelphp.com/welcome/index/6">6</a></span><span class="next"><a href="http://docs.fuelphp.com/welcome/index/2">&raquo;</a></span></div>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page
	 *
	 */
	public function test_uri_segment_nextlink_inactive()
	{
		// set Request::$main
		$request = \Request::forge('welcome/index/10');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);

		$this->set_uri_segment_config();

		$pagination = Pagination::forge(__METHOD__, $this->config);

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next-inactive"><a href="#">&raquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span><a href="http://docs.fuelphp.com/welcome/index/6">6</a></span><span><a href="http://docs.fuelphp.com/welcome/index/7">7</a></span><span><a href="http://docs.fuelphp.com/welcome/index/8">8</a></span><span><a href="http://docs.fuelphp.com/welcome/index/9">9</a></span><span class="active"><a href="#">10</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous"><a href="http://docs.fuelphp.com/welcome/index/9">&laquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<div class="pagination"><span class="previous"><a href="http://docs.fuelphp.com/welcome/index/9">&laquo;</a></span><span><a href="http://docs.fuelphp.com/welcome/index/6">6</a></span><span><a href="http://docs.fuelphp.com/welcome/index/7">7</a></span><span><a href="http://docs.fuelphp.com/welcome/index/8">8</a></span><span><a href="http://docs.fuelphp.com/welcome/index/9">9</a></span><span class="active"><a href="#">10</a></span><span class="next-inactive"><a href="#">&raquo;</a></span></div>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * total page is 1
	 *
	 */
	public function test_uri_segment_total_page_is_one()
	{
		$this->set_uri_segment_config();
		$this->config['per_page'] = 1000;

		$pagination = Pagination::forge(__METHOD__, $this->config);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '';
		$this->assertEquals($expected, $output);

		$output = $pagination->render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '';
		$this->assertEquals($expected, $output);
	}

/***********************************
 * Tests for Query String Pagination
 ***********************************/

	protected function set_query_string_config()
	{
		$this->config = array(
			'uri_segment'    => 'p',
			'pagination_url' => 'http://docs.fuelphp.com/',
			'total_items'    => 100,
			'per_page'       => 10,
		);
	}

	public function test_query_string_auto_detect_pagination_url()
	{
		// set base_url
		Config::set('base_url', 'http://docs.fuelphp.com/');
		// set Request::$main
		$request = \Request::forge('/');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);

		$this->set_query_string_config();
		$this->config['pagination_url'] = null;

		$pagination = Pagination::forge(__METHOD__, $this->config);
		$test = $pagination->pagination_url;
		$expected = 'http://docs.fuelphp.com/?p={page}';
		$this->assertEquals($expected, $test);

		// reset base_url
		Config::set('base_url', null);
	}

	public function test_query_string_get_total_pages()
	{
		$this->set_query_string_config();

		$pagination = Pagination::forge(__METHOD__, $this->config);
		$test = $pagination->total_pages;
		$expected = 10;
		$this->assertEquals($expected, $test);
	}

	/**
	 * first page
	 *
	 */
	public function test_query_string_first_page()
	{
		$this->set_query_string_config();

		$pagination = Pagination::forge(__METHOD__, $this->config);
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
	public function test_query_string_nextlink_inactive()
	{
		$this->set_query_string_config();

		$pagination = Pagination::forge(__METHOD__, $this->config);
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
