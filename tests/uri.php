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
 * Html class tests
 *
 * @group Core
 * @group Uri
 */
class Test_Uri extends TestCase
{

	/**
	 * Tests Uri::create()
	 *
	 * @test
	 */
	public function test_create()
	{
		Config::set('url_suffix', '');

		$prefix = Uri::create('');

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

}


