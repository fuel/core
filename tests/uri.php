<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2015 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Html class tests
 *
 * @group Core
 * @group Uri
 */
class Test_Uri extends TestCase
{
	public function setUp()
	{
		$this->old_url_suffix = Config::get('url_suffix');
		$this->old_index_file = Config::get('index_file');
		$this->old_base_url = Config::get('base_url');
	}

	public function tearDown()
	{
		Config::set('url_suffix', $this->old_url_suffix);
		Config::set('index_file', $this->old_index_file);
		Config::set('base_url', $this->old_base_url);
	}

	/**
	 * Tests Uri::create()
	 *
	 * @test
	 */
	public function test_create()
	{
		Config::set('url_suffix', '');

		$prefix = Uri::create('');

		Config::set('index_file', 'index.php');
		$output = Uri::create('controller/method');
		$expected = $prefix."index.php/controller/method";
		$this->assertEquals($expected, $output);

		Config::set('index_file', '');

		$output = Uri::create('controller/method');
		$expected = $prefix."controller/method";
		$this->assertEquals($expected, $output);

		$output = Uri::create('controller/:some', array('some' => 'thing', 'and' => 'more'), array('what' => ':and'));
		$expected = $prefix."controller/thing?what=more";
		$this->assertEquals($expected, $output);

		Config::set('url_suffix', '.html');

		$output = Uri::create('controller/method');
		$expected = $prefix."controller/method.html";
		$this->assertEquals($expected, $output);

		$output = Uri::create('controller/:some', array('some' => 'thing', 'and' => 'more'), array('what' => ':and'));
		$expected = $prefix."controller/thing.html?what=more";
		$this->assertEquals($expected, $output);

		$output = Uri::create('http://example.com/controller/:some', array('some' => 'thing', 'and' => 'more'), array('what' => ':and'));
		$expected = "http://example.com/controller/thing.html?what=more";
		$this->assertEquals($expected, $output);

		$output = Uri::create('http://example.com/controller/:some', array('some' => 'thing', 'and' => 'more'), array('what' => ':and'), true);
		$expected = "https://example.com/controller/thing.html?what=more";
		$this->assertEquals($expected, $output);

		$output = Uri::create('https://example.com/controller/:some', array('some' => 'thing', 'and' => 'more'), array('what' => ':and'), false);
		$expected = "http://example.com/controller/thing.html?what=more";
		$this->assertEquals($expected, $output);

	}

	/**
	 * Tests Uri::base()
	 *
	 * @test
	 */
	public function test_base()
	{
		Config::set('base_url', null);
		Config::set('index_file', false);

		$output = Uri::base();
		$expected = null;
		$this->assertEquals($expected, $output);

		Config::set('base_url', 'http://example.com/');
		Config::set('index_file', 'index.php');

		$output = Uri::base();
		$expected = 'http://example.com/index.php/';
		$this->assertEquals($expected, $output);

		$output = Uri::base(false);
		$expected = 'http://example.com/';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Uri::current()
	 *
	 * @test
	 */
	public function test_current()
	{
		$output = Uri::current();
		$expected = Uri::create();
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Uri::build_query_string()
	 *
	 * @test
	 */
	public function test_build_query_string()
	{
		$output = Uri::build_query_string(array('varA' => 'varA'), 'varB', array('varC' => 'varC'));
		$expected = "varA=varA&varB=1&varC=varC";
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Uri::update_query_string()
	 *
	 * @test
	 */
	public function test_update_query_string()
	{
		Config::set('base_url', 'http://example.com/test');
		Config::set('index_file', null);
		Config::set('url_suffix', '');
		$_GET = array('one' => 1, 'two' => 2);

		$output = Uri::update_query_string(array('three' => 3));
		$expected = 'http://example.com/test?one=1&two=2&three=3';
		$this->assertEquals($expected, $output);

		$output = Uri::update_query_string(array('two' => 3));
		$expected = 'http://example.com/test?one=1&two=3';
		$this->assertEquals($expected, $output);

		$output = Uri::update_query_string(array('four' => 4), 'http://localhost/controller');
		$expected = 'http://localhost/controller?four=4';
		$this->assertEquals($expected, $output);

		$output = Uri::update_query_string('three', 3, true);
		$expected = 'https://example.com/test?one=1&two=2&three=3';
		$this->assertEquals($expected, $output);
	}
}
