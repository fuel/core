<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Html class tests
 *
 * @group Core
 * @group Html
 */
class Tests_Html extends TestCase {

	/**
	 * Tests Html::meta()
	 *
	 * @test
	 */
	public function test_meta()
	{
		$output = Html::meta('description', 'Meta Example!');
		$expected = '<meta name="description" content="Meta Example!" />';
		$this->assertEquals($expected, $output);

		$output = Html::meta('robots', 'no-cache');
		$expected = '<meta name="robots" content="no-cache" />';
		$this->assertEquals($expected, $output);

		$meta = array(
			array('name' => 'robots', 'content' => 'no-cache'),
			array('name' => 'description', 'content' => 'Meta Example'),
			array('name' => 'keywords', 'content' => 'fuel, rocks'),
			);

		$output = Html::meta($meta);
		$expected = '
<meta name="robots" content="no-cache" />
<meta name="description" content="Meta Example" />
<meta name="keywords" content="fuel, rocks" />';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::anchor()
	 *
	 * @test
	 */
	public function test_anchor()
	{
		// External uri
		$output = Html::anchor('http://google.com', 'Go to Google');
		$expected = '<a href="http://google.com">Go to Google</a>';
		$this->assertEquals($expected, $output);

		$output = Html::anchor('javascript:do();', 'Do()');
		$expected = '<a href="javascript:do();">Do()</a>';
		$this->assertEquals($expected, $output);

		$output = Html::anchor('http://google.com', 'Go to Google', array('rel' => 'example', 'class' => 'sample', 'style' => 'color:red;'));
		$expected = '<a rel="example" class="sample" style="color:red;" href="http://google.com">Go to Google</a>';
		$this->assertEquals($expected, $output);

		// Internal uri
		$output = Html::anchor('controller/method', 'Method');
		$expected = '<a href="' . Uri::create('controller/method') . '">Method</a>';
		$this->assertEquals($expected, $output);

		// Query string tests
		Config::set('url_suffix', '');

		$output = Html::anchor('search?q=query', 'Search');
		$expected = '<a href="search?q=query">Search</a>';
		$this->assertEquals($expected, $output);

		Config::set('url_suffix', '.html');

		$output = Html::anchor('search?q=query', 'Search');
		$expected = '<a href="search.html?q=query">Search</a>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::img()
	 *
	 * This test does not account for the image file existing in
	 * the filesystem. There are no images bundled with the framework
	 * by default, so no reliable test can be run on an actual image.
	 *
	 * @test
	 */
	public function test_img()
	{
		$image_path = 'image.png';

		// Internal uri
		$output = Html::img('image.png');
		$expected = '<img src="'. $image_path . '" alt="image" />';
		$this->assertEquals($expected, $output);

		$output = Html::img('image.png', array('alt' => 'Image'));
		$expected = '<img alt="Image" src="'. $image_path . '" />';
		$this->assertEquals($expected, $output);

		// External uri
		$output = Html::img('http://google.com/image.png');
		$expected = '<img src="http://google.com/image.png" />';
	}

	/**
	 * Tests Html::prep_url()
	 *
	 * @test
	 */
	public function test_prep_url()
	{
		$output = Html::prep_url('google.com');
		$expected = 'http://google.com';
		$this->assertEquals($expected, $output);

		$output = Html::prep_url('google.com', 'https');
		$expected = 'https://google.com';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::mail_to()
	 *
	 * @test
	 */
	public function test_mail_to()
	{
		$output = Html::mail_to('test@test.com');
		$expected = '<a href="mailto:test@test.com">test@test.com</a>';
		$this->assertEquals($expected, $output);

		$output = Html::mail_to('test@test.com', 'Email');
		$expected = '<a href="mailto:test@test.com">Email</a>';
		$this->assertEquals($expected, $output);

		$output = Html::mail_to('test@test.com', NULL, 'Test');
		$expected = '<a href="mailto:test@test.com?subject=Test">test@test.com</a>';
		$this->assertEquals($expected, $output);

		$output = Html::mail_to('test@test.com', 'Test', 'Test');
		$expected = '<a href="mailto:test@test.com?subject=Test">Test</a>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::doctype()
	 *
	 * @test
	 */
	public function test_doctype()
	{
		$output = Html::doctype();
		$expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		$this->assertEquals($expected, $output);

		$output = Html::doctype('html5');
		$expected = '<!DOCTYPE html>';
		$this->assertEquals($expected, $output);

		// Ensure static::$html5 is set
		$doctype = Html::doctype('html5');
		$this->assertTrue(Html::$html5);

		// Ensure === false if doctype is invalid
		$this->assertFalse(Html::doctype('shouldfail'));
	}

	/**
	 * Tests Html::ul() & Html::ol()
	 *
	 * @test
	 */
	public function test_lists()
	{
		$list = array('one', 'two');

		$output = Html::ul($list);
		$expected = '<ul>
	<li>one</li>
	<li>two</li>
</ul>
';
		$this->assertEquals($expected, $output);

		$output = Html::ol($list);
		$expected = '<ol>
	<li>one</li>
	<li>two</li>
</ol>
';
		$this->assertEquals($expected, $output);
	}
}


