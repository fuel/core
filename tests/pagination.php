<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
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
	protected function set_request($uri)
	{
		// fake the uri for this request
		isset($_SERVER['PATH_INFO']) and $this->pathinfo = $_SERVER['PATH_INFO'];
		$_SERVER['PATH_INFO'] = '/'.$uri;

		// set Request::$main
		$this->request = \Request::forge($uri);
		$rp = new \ReflectionProperty($this->request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($this->request, $this->request);

		// set Request::$active
		$rp = new \ReflectionProperty($this->request, 'active');
		$rp->setAccessible(true);
		$rp->setValue($this->request, $this->request);
	}

	protected function setUp()
	{
		$this->old_base_url = Config::get('base_url');
	}

	public function tearDown()
	{
		// remove the fake uri
		if (property_exists($this, 'pathinfo'))
		{
			$_SERVER['PATH_INFO'] = $this->pathinfo;
		}
		else
		{
			unset($_SERVER['PATH_INFO']);
		}

		// reset Request::$main
		$request = \Request::forge();
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, false);

		// reset Request::$active
		$rp = new \ReflectionProperty($request, 'active');
		$rp->setAccessible(true);
		$rp->setValue($request, false);

		// ensure base_url is reset even if an exception occurs
		Config::set('base_url', $this->old_base_url);
	}

/**********************************
 * Tests for URI Segment Pagination
 **********************************/

	protected function set_uri_segment_config()
	{
		$this->config = array(
			'uri_segment'             => 3,
			'pagination_url'          => 'http://docs.fuelphp.com/welcome/index/',
			'total_items'             => 100,
			'per_page'                => 10,
			'wrapper'                 => "<div class=\"pagination\">\n\t{pagination}\n</div>\n",

			'first'                   => "<span class=\"first\">\n\t{link}\n</span>\n",
			'first-marker'            => "&laquo;&laquo;",
			'first-link'              => "\t\t<a href=\"{uri}\">{page}</a>\n",

			'first-inactive'          => "",
			'first-inactive-link'     => "",

			'previous'                => "<span class=\"previous\">\n\t{link}\n</span>\n",
			'previous-marker'         => "&laquo;",
			'previous-link'           => "\t\t<a href=\"{uri}\" rel=\"prev\">{page}</a>\n",

			'previous-inactive'       => "<span class=\"previous-inactive\">\n\t{link}\n</span>\n",
			'previous-inactive-link'  => "\t\t<a href=\"#\" rel=\"prev\">{page}</a>\n",

			'regular'                 => "<span>\n\t{link}\n</span>\n",
			'regular-link'            => "\t\t<a href=\"{uri}\">{page}</a>\n",

			'active'                  => "<span class=\"active\">\n\t{link}\n</span>\n",
			'active-link'             => "\t\t<a href=\"#\">{page}</a>\n",

			'next'                    => "<span class=\"next\">\n\t{link}\n</span>\n",
			'next-marker'            => "&raquo;",
			'next-link'               => "\t\t<a href=\"{uri}\" rel=\"next\">{page}</a>\n",

			'next-inactive'           => "<span class=\"next-inactive\">\n\t{link}\n</span>\n",
			'next-inactive-link'      => "\t\t<a href=\"#\" rel=\"next\">{page}</a>\n",

			'last'                    => "<span class=\"last\">\n\t{link}\n</span>\n",
			'last-marker'             => "&raquo;&raquo;",
			'last-link'               => "\t\t<a href=\"{uri}\">{page}</a>\n",

			'last-inactive'           => "",
			'last-inactive-link'      => "",
		);
	}

	public function test_uri_segment_auto_detect_pagination_url()
	{
		// set base_url
		Config::set('base_url', 'http://docs.fuelphp.com/');
		// set Request::$main & $active
		$this->set_request('welcome/index/5');

		$this->set_uri_segment_config();
		$this->config['pagination_url'] = null;
		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://docs.fuelphp.com/welcome/index/1';
		$this->assertEquals($expected, $test);
	}

	public function test_uri_segment_set_pagination_url_after_forging_fail()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/3');

		$this->set_uri_segment_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);
		$pagination->pagination_url = 'http://example.com/page/';

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		// not enough segments in the URI to add the page number
		$this->expectException('RunTimeException');

		$test = $_make_link->invoke($pagination, 1);
	}

	public function test_uri_segment_set_pagination_url_after_forging_success()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/3');

		$this->set_uri_segment_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);
		$pagination->pagination_url = 'http://example.com/this/page/';

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://example.com/this/page/1';
		$this->assertEquals($expected, $test);
	}

	public function test_uri_segment_get_total_pages()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/');

		$this->set_uri_segment_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);
		$test = $pagination->total_pages;
		$expected = 10;
		$this->assertEquals($expected, $test);
	}

	public function test_current_page_calculation_from_link()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/4');

		$this->set_uri_segment_config();

		$config = array(
			'total_items'    => 12,
			'per_page'       => 3,
			'uri_segment'    => 3,
		);

		$pagination = Pagination::forge(__METHOD__.'-1', $config);
		$test = $pagination->current_page;
		$expected = 4;
		$this->assertEquals($expected, $test);
	}

	public function test_current_page_calculation_from_config()
	{
		$this->set_request('welcome/index/6');

		$config = array(
			'per_page'       => 3,
			'total_items'    => 12,
			'uri_segment'    => 3,
			'current_page'   => 4,
		);

		$pagination = Pagination::forge(__METHOD__.'-2', $config);
		$test = $pagination->current_page;
		$expected = 4;
		$this->assertEquals($expected, $test);
	}

	/**
	 * raw render
	 *
	 */
	public function test_pagination_raw_render()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/5');

		$this->set_uri_segment_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);

		$expected = array(
			'previous' => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/4",
				'title' => "&laquo;",
				'type' => "previous",
			),
			0 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/3",
				'title' => 3,
				'type' => "regular",
			),
			1 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/4",
				'title' => 4,
				'type' => "regular",
			),
			2 => array(
				'uri' => "#",
				'title' => 5,
				'type' => "active",
			),
			3 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/6",
				'title' => 6,
				'type' => "regular",
			),
			4 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/7",
				'title' => 7,
				'type' => "regular",
			),
			'next' => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/6",
				'title' => "&raquo;",
				'type' => "next",
			),
		);

		// default link offset of 50%, active is the middle link
		$test = $pagination->render(true);
		$this->assertEquals($expected, $test);

		$expected = array(
			'previous' => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/4",
				'title' => "&laquo;",
				'type' => "previous",
			),
			0 => array(
				'uri' => "#",
				'title' => 5,
				'type' => "active",
			),
			1 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/6",
				'title' => 6,
				'type' => "regular",
			),
			2 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/7",
				'title' => 7,
				'type' => "regular",
			),
			3 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/8",
				'title' => 8,
				'type' => "regular",
			),
			4 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/9",
				'title' => 9,
				'type' => "regular",
			),
			'next' => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/6",
				'title' => "&raquo;",
				'type' => "next",
			),
		);

		$pagination->link_offset = 0;  // 0%, active is the first link
		$test = $pagination->render(true);
		$this->assertEquals($expected, $test);

		$expected = array(
			'previous' => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/4",
				'title' => "&laquo;",
				'type' => "previous",
			),
			0 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/1",
				'title' => 1,
				'type' => "regular",
			),
			1 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/2",
				'title' => 2,
				'type' => "regular",
			),
			2 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/3",
				'title' => 3,
				'type' => "regular",
			),
			3 => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/4",
				'title' => 4,
				'type' => "regular",
			),
			4 => array(
				'uri' => "#",
				'title' => 5,
				'type' => "active",
			),
			'next' => array(
				'uri' => "http://docs.fuelphp.com/welcome/index/6",
				'title' => "&raquo;",
				'type' => "next",
			),
		);

		$pagination->link_offset = 100;  // 100%, active is the last link
		$test = $pagination->render(true);
		$this->assertEquals($expected, $test);
	}

	/**
	 * first page
	 *
	 */
	public function test_uri_segment_first_page()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/');

		$this->set_uri_segment_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous-inactive"><a href="#" rel="prev">&laquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="active"><a href="#">1</a></span><span><a href="http://docs.fuelphp.com/welcome/index/2">2</a></span><span><a href="http://docs.fuelphp.com/welcome/index/3">3</a></span><span><a href="http://docs.fuelphp.com/welcome/index/4">4</a></span><span><a href="http://docs.fuelphp.com/welcome/index/5">5</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next"><a href="http://docs.fuelphp.com/welcome/index/2" rel="next">&raquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<div class="pagination"><span class="previous-inactive"><a href="#" rel="prev">&laquo;</a></span><span class="active"><a href="#">1</a></span><span><a href="http://docs.fuelphp.com/welcome/index/2">2</a></span><span><a href="http://docs.fuelphp.com/welcome/index/3">3</a></span><span><a href="http://docs.fuelphp.com/welcome/index/4">4</a></span><span><a href="http://docs.fuelphp.com/welcome/index/5">5</a></span><span class="next"><a href="http://docs.fuelphp.com/welcome/index/2" rel="next">&raquo;</a></span></div>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page
	 *
	 */
	public function test_uri_segment_nextlink_inactive()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/11');

		$this->set_uri_segment_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next-inactive"><a href="#" rel="next">&raquo;</a></span>';
		$this->assertEquals($expected, $output);

		$this->set_request('welcome/index/10');

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span><a href="http://docs.fuelphp.com/welcome/index/6">6</a></span><span><a href="http://docs.fuelphp.com/welcome/index/7">7</a></span><span><a href="http://docs.fuelphp.com/welcome/index/8">8</a></span><span><a href="http://docs.fuelphp.com/welcome/index/9">9</a></span><span class="active"><a href="#">10</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous"><a href="http://docs.fuelphp.com/welcome/index/9" rel="prev">&laquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<div class="pagination"><span class="previous"><a href="http://docs.fuelphp.com/welcome/index/9" rel="prev">&laquo;</a></span><span><a href="http://docs.fuelphp.com/welcome/index/6">6</a></span><span><a href="http://docs.fuelphp.com/welcome/index/7">7</a></span><span><a href="http://docs.fuelphp.com/welcome/index/8">8</a></span><span><a href="http://docs.fuelphp.com/welcome/index/9">9</a></span><span class="active"><a href="#">10</a></span><span class="next-inactive"><a href="#" rel="next">&raquo;</a></span></div>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * total page is 1
	 *
	 */
	public function test_uri_segment_total_page_is_one()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/10');

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

	public function test_uri_segment_make_link_with_no_query_string_ending_page_number()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/10');
		$this->set_uri_segment_config();
		$this->config['pagination_url'] = 'http://docs.fuelphp.com/welcome/index/55';

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://docs.fuelphp.com/welcome/index/1';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = 'http://docs.fuelphp.com/welcome/index/99';
		$this->assertEquals($expected, $test);
	}

	public function test_uri_segment_make_link_with_no_query_string_ending_slash()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/');
		$this->set_uri_segment_config();
		$this->config['pagination_url'] = 'http://docs.fuelphp.com/welcome/index/';

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://docs.fuelphp.com/welcome/index/1';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = 'http://docs.fuelphp.com/welcome/index/99';
		$this->assertEquals($expected, $test);
	}

	public function test_uri_segment_make_link_with_query_string_ending_page_number()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/55?foo=bar&fuel[]=php1&fuel[]=php2&');
		$this->set_uri_segment_config();

		// no define pagination_url
		$this->config['pagination_url'] = null;

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = '/welcome/index/1?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = '/welcome/index/99?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2';
		$this->assertEquals($expected, $test);
	}

	public function test_uri_segment_make_link_with_query_string_ending_slash()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/?foo=bar&fuel[]=php1&fuel[]=php2&');
		$this->set_uri_segment_config();

		// no define pagination_url
		$this->config['pagination_url'] = null;

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = '/welcome/index/1?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = '/welcome/index/99?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2';
		$this->assertEquals($expected, $test);
	}

/***********************************
 * Tests for Query String Pagination
 ***********************************/

	protected function set_query_string_config()
	{
		$this->config = array(
			'uri_segment'             => 'p',
			'pagination_url'          => 'http://docs.fuelphp.com/',
			'total_items'             => 100,
			'per_page'                => 10,
			'wrapper'                 => "<div class=\"pagination\">\n\t{pagination}\n</div>\n",

			'first'                   => "<span class=\"first\">\n\t{link}\n</span>\n",
			'first-marker'            => "&laquo;&laquo;",
			'first-link'              => "\t\t<a href=\"{uri}\">{page}</a>\n",

			'first-inactive'          => "",
			'first-inactive-link'     => "",

			'previous'                => "<span class=\"previous\">\n\t{link}\n</span>\n",
			'previous-marker'         => "&laquo;",
			'previous-link'           => "\t\t<a href=\"{uri}\" rel=\"prev\">{page}</a>\n",

			'previous-inactive'       => "<span class=\"previous-inactive\">\n\t{link}\n</span>\n",
			'previous-inactive-link'  => "\t\t<a href=\"#\" rel=\"prev\">{page}</a>\n",

			'regular'                 => "<span>\n\t{link}\n</span>\n",
			'regular-link'            => "\t\t<a href=\"{uri}\">{page}</a>\n",

			'active'                  => "<span class=\"active\">\n\t{link}\n</span>\n",
			'active-link'             => "\t\t<a href=\"#\">{page}</a>\n",

			'next'                    => "<span class=\"next\">\n\t{link}\n</span>\n",
			'next-marker'            => "&raquo;",
			'next-link'               => "\t\t<a href=\"{uri}\" rel=\"next\">{page}</a>\n",

			'next-inactive'           => "<span class=\"next-inactive\">\n\t{link}\n</span>\n",
			'next-inactive-link'      => "\t\t<a href=\"#\" rel=\"next\">{page}</a>\n",

			'last'                    => "<span class=\"last\">\n\t{link}\n</span>\n",
			'last-marker'             => "&raquo;&raquo;",
			'last-link'               => "\t\t<a href=\"{uri}\">{page}</a>\n",

			'last-inactive'           => "",
			'last-inactive-link'      => "",
		);
	}

	public function test_query_string_auto_detect_pagination_url()
	{
		// set base_url
		Config::set('base_url', 'http://docs.fuelphp.com/');
		// set Request::$main & $active
		$this->set_request('/');

		$this->set_query_string_config();
		$this->config['pagination_url'] = null;
		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://docs.fuelphp.com/?p=1';
		$this->assertEquals($expected, $test);
	}

	public function test_query_string_get_total_pages()
	{
		// set Request::$main & $active
		$this->set_request('/');

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
		// set Request::$main & $active
		$this->set_request('/');

		$this->set_query_string_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);
		$pagination->current_page = 1;

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous-inactive"><a href="#" rel="prev">&laquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="active"><a href="#">1</a></span><span><a href="http://docs.fuelphp.com/?p=2">2</a></span><span><a href="http://docs.fuelphp.com/?p=3">3</a></span><span><a href="http://docs.fuelphp.com/?p=4">4</a></span><span><a href="http://docs.fuelphp.com/?p=5">5</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next"><a href="http://docs.fuelphp.com/?p=2" rel="next">&raquo;</a></span>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * last page
	 *
	 */
	public function test_query_string_nextlink_inactive()
	{
		// set Request::$main & $active
		$this->set_request('/');

		$this->set_query_string_config();
		$pagination = Pagination::forge(__METHOD__, $this->config);
		$pagination->current_page = 10;

		$output = $pagination->next();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="next-inactive"><a href="#" rel="next">&raquo;</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->pages_render();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span><a href="http://docs.fuelphp.com/?p=6">6</a></span><span><a href="http://docs.fuelphp.com/?p=7">7</a></span><span><a href="http://docs.fuelphp.com/?p=8">8</a></span><span><a href="http://docs.fuelphp.com/?p=9">9</a></span><span class="active"><a href="#">10</a></span>';
		$this->assertEquals($expected, $output);

		$output = $pagination->previous();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<span class="previous"><a href="http://docs.fuelphp.com/?p=9" rel="prev">&laquo;</a></span>';
		$this->assertEquals($expected, $output);
	}

	public function test_query_string_make_link_by_request()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/?foo=bar&fuel[]=php1&fuel[]=php2&p=40');

		$this->set_query_string_config();
		$this->config['pagination_url'] = null;

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'welcome/index?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2&amp;p=1';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = 'welcome/index?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2&amp;p=99';
		$this->assertEquals($expected, $test);
	}

	public function test_query_string_make_link_by_pagination_url()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/?foo=bar&fuel[]=php1&fuel[]=php2&p=40');

		$this->set_query_string_config();
		$this->config['pagination_url'] = 'http://docs.fuelphp.com/?foo=bar&fuel[]=php1&fuel[]=php2';

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://docs.fuelphp.com/?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2&amp;p=1';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = 'http://docs.fuelphp.com/?foo=bar&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2&amp;p=99';
		$this->assertEquals($expected, $test);
	}

	public function test_query_string_make_link_by_pagination_url_include_page_number()
	{
		// set Request::$main & $active
		$this->set_request('welcome/index/?foo=bar&fuel[]=php1&fuel[]=php2&p=40');

		$this->set_query_string_config();
		$this->config['pagination_url'] = 'http://docs.fuelphp.com/?foo=bar&p=123&fuel[]=php1&fuel[]=php2';

		$pagination = Pagination::forge(__METHOD__, $this->config);

		// set _make_link() accessible
		$_make_link = new \ReflectionMethod($pagination, '_make_link');
		$_make_link->setAccessible(true);

		$test = $_make_link->invoke($pagination, 1);
		$expected = 'http://docs.fuelphp.com/?foo=bar&amp;p=1&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2';
		$this->assertEquals($expected, $test);

		$test = $_make_link->invoke($pagination, 99);
		$expected = 'http://docs.fuelphp.com/?foo=bar&amp;p=99&amp;fuel%5B0%5D=php1&amp;fuel%5B1%5D=php2';
		$this->assertEquals($expected, $test);
	}
}
