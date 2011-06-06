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
	 * Tests Html::h()
	 * 
	 * @test
	 */
	public function test_h()
	{
		$output = Html::h('Example');
		$expected = "<h1>Example</h1>";
		$this->assertEquals($expected, $output);

		$output = Html::h('Some other example', '2', array('id' => 'h2', 'class' => 'sample', 'style' => 'color:red;'));
		$expected = '<h2 id="h2" class="sample" style="color:red;">Some other example</h2>';
		$this->assertEquals($expected, $output);

		$attributes = array('id' => 'sample', 'class' => 'sample', 'style' => 'color:blue;');
		$output = Html::h('Variable!', '3', $attributes);
		$expected = '<h3 id="sample" class="sample" style="color:blue;">Variable!</h3>';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::br()
	 * 
	 * @test
	 */
	public function test_br()
	{
		$output = Html::br();
		$expected = "<br />";
		$this->assertEquals($expected, $output);

		$output = Html::br('2', array('id' => 'example', 'class' => 'sample', 'style' => 'color:red;'));
		$expected = '<br id="example" class="sample" style="color:red;" /><br id="example" class="sample" style="color:red;" />';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::hr()
	 * 
	 * @test
	 */
	public function test_hr()
	{
		$output = Html::hr();
		$expected = "<hr />";
		$this->assertEquals($expected, $output);

		$output = Html::hr(array('id' => 'example', 'class' => 'sample', 'style' => 'color:red;'));
		$expected = '<hr id="example" class="sample" style="color:red;" />';
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::title()
	 * 
	 * @test
	 */
	public function test_title()
	{
		$output = Html::title();
		$expected = "<title></title>";
		$this->assertEquals($expected, $output);

		$output = Html::title('Some Title!');
		$expected = "<title>Some Title!</title>";
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Html::nbs()
	 * 
	 * @test
	 */
	public function test_nbs()
	{
		$output = Html::nbs();
		$expected = "&nbsp;";
		$this->assertEquals($expected, $output);

		$output = Html::nbs(2);
		$expected = "&nbsp;&nbsp;";
		$this->assertEquals($expected, $output);
	}

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
		$index_url = \Config::get('index_file', '');
		
		if (!empty($index_url))
		{
			$index_url .= '/';
		}
		
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
		$expected = '<a href="' . $index_url . 'controller/method">Method</a>';
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
		$index_url = \Config::get('index_file', '');
		
		if (!empty($index_url))
		{
			$index_url .= '/';
		}
		
		// Internal uri
		$output = Html::img('image.png');
		$expected = '<img src="'. $index_url . 'image.png" alt="image" />';
		$this->assertEquals($expected, $output);
		
		$output = Html::img('image.png', array('alt' => 'Image'));
		$expected = '<img alt="Image" src="'. $index_url . 'image.png" />';
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
	 * Tests Html::header()
	 * 
	 * @test
	 */
	public function test_header()
	{
		// Doctype != html5
		Html::$html5 = FALSE;
		
		$output = Html::header();
		$expected = '<div id="header"></div>';
		$this->assertEquals($expected, $output);
		
		$output = Html::header('Content');
		$expected = '<div id="header">Content</div>';
		$this->assertEquals($expected, $output);
		
		// Doctype = html5
		Html::$html5 = TRUE;
		$output = Html::header();
		$expected = '<header></header>';
		$this->assertEquals($expected, $output);
		
		$output = Html::header('Content');
		$expected = '<header>Content</header>';
		$this->assertEquals($expected, $output);
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

/* End of file html.php */
