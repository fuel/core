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
 * Response class tests
 *
 * @group Core
 * @group Response
 */
class Test_Response extends TestCase
{
	/**
	 * Tests Response set_header
	 * @test
	 */
	public function test_set_header() {
		$response = Response::forge();

		// insert name and value into header values
		$name = 'header name';
		$value = 'header value';
		$response->set_header($name, $value);
		$this->assertEquals($value, $response->headers[$name]);

		// update previously added header value
		$new_value = 'new header value';
		$response->set_header($name, $new_value);
		$this->assertEquals($new_value, $response->headers[$name]);

		// insert name and value array into header values
		$response->set_header($name, $value, false);
		$this->assertContains(array($name, $value), $response->headers);
	}

	/**
	 * Tests Reponse get_header
	 * @test
	 */
	public function test_get_header() {
		$response = Response::forge();

		// execute without parameter
		$this->assertEquals($response->headers, $response->get_header());

		// get not existing header value
		$this->assertNull($response->get_header('test'));

		// get existing header value
		$name = 'name';
		$value = 'value';
		$response->set_header($name, $value);
		$this->assertEquals($value, $response->get_header($name));
	}

	/**
	 * Test body
	 * @test
	 */
	public function test_body() {
		$response = Response::forge();

		// execute without parameter and check initial value
		$this->assertNull($response->body());

		// execute with parameter
		$value = 'body value';
		$result = $response->body($value);
		$this->assertEquals(get_class($result), get_class($response));

		// execute without parameter and check updated value
		$this->assertEquals($value, $response->body());
	}

	/**
	 * Test Response __toString
	 * @test
	 */
	public function test___toString() {
		$response = Response::forge();

		// check when body is null
		$this->assertTrue(is_string($response->__toString()));
		$this->assertSame((string) null, $response->__toString());
		// check when body is not null
		$value = 12345;
		$response->body($value);
		$this->assertTrue(is_string($response->__toString()));
		$this->assertSame((string) $value, $response->__toString());

		$value = '54321';
		$response->body($value);
		$this->assertTrue(is_string($response->__toString()));
		$this->assertSame($value, $response->__toString());
	}
}
