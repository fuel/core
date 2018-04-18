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

class FakeRequest extends Request_Driver {
	public function execute(array $additional_params = array()) { /* nop */ }

	public function test_mime_in_header($mime, $accept_header)
	{
		return $this->mime_in_header($mime, $accept_header);
	}
}

/**
 * Request class tests
 *
 * @group Core
 * @group Request
 */
class Test_Request extends TestCase
{
	public function mime_testpairs()
	{
		return [
			['application/json', '*/*', true],
			['application/xml', '*/*', true],
			['image/jpeg', '*/*', true],
			['application/json', 'image/*', false],
			['application/json', 'application/*', true],
			['image/jpeg', 'image/*', true],
			['image/jpeg', 'image/jpeg', true],
			['image/jpeg', 'image/png', false],
		];
	}

	/**
	 * @dataProvider mime_testpairs
	 */
	public function test_mime_type_matching($response_mime, $accept_header, $expected)
	{
		$req = FakeRequest::forge('ignore');
		$this->assertSame($expected, $req->test_mime_in_header($response_mime, $accept_header));
	}

	public function auto_format_testdata()
	{
		return [
			['text/csv', "\"first\",\"second\",\"third\"\n\"1\",\"2\",\"3\"\n", '*/*', [['first' => '1', 'second' => '2', 'third' => '3']]],
			['application/json', '{"foo": "bar"}', '*/*', ['foo' => 'bar']],
			['application/json', '["x",1,2]', 'application/*', ['x', 1, 2]],
			['application/json', '[]', 'application/json', []],
			['application/json', '[1, 2, 3]', 'application/csv', null],
			['application/json; charset=utf8', '[1, 2, 3]', 'application/json', [1, 2, 3]],
		];
	}

	/**
	 * @dataProvider auto_format_testdata
	 */
	public function test_mime_type_auto_format($response_mime, $response_data, $accept_header, $parsed_data)
	{
		$req = FakeRequest::forge('ignore');
		$req->set_auto_format(true);

		if ($parsed_data === null)
		{
			$this->expectException('\OutOfRangeException');
		}
		$req->set_response($response_data, 200, $response_mime, [], $accept_header);
		$this->assertEquals($parsed_data, $req->response()->body());
	}
}
