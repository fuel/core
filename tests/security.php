<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Security class tests
 *
 * @group Core
 * @group Security
 */
class Test_Security extends TestCase
{
	/**
	* Tests Security::htmlentities()
	*
	* @test
	*/
	public function test_htmlentities_doublequote_and_ampersand()
	{
		$output = Security::htmlentities('"H&M"');
		$expected = '&quot;H&amp;M&quot;';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Security::htmlentities()
	*
	* @test
	*/
	public function test_htmlentities_singlequote()
	{
		$output = Security::htmlentities("'");
		$expected = '&#039;';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Security::htmlentities()
	*
	* @test
	*/
	public function test_htmlentities_charactor_references_no_double_encode()
	{
		$output = Security::htmlentities('You must write & as &amp;');
		$expected = 'You must write &amp; as &amp;';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Security::htmlentities()
	*
	* @test
	*/
	public function test_htmlentities_charactor_references_double_encode()
	{
		$config = \Config::get('security.htmlentities_double_encode');
		\Config::set('security.htmlentities_double_encode', true);

		$output = Security::htmlentities('You must write & as &amp;');
		$expected = 'You must write &amp; as &amp;amp;';
		$this->assertEquals($expected, $output);

		\Config::set('security.htmlentities_double_encode', $config);
	}

	/**
	* Tests Security::htmlentities()
	*
	* @test
	*/
	public function test_htmlentities_double_encode()
	{
		$output = Security::htmlentities('"H&M"');
		$output = Security::htmlentities($output);
		$expected = '&quot;H&amp;M&quot;';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Security::clean()
	*
	* @test
	*/
	public function test_clean()
	{
		// test correct recursive cleaning
		$input = array(
			array(' level1 '),
			array(
				array(' level2 '),
				array(
					array(' level3 '),
					array(
						array(' level4 '),
					),
				),
			),
		);

		$expected = array(
			array('level1'),
			array(
				array('level2'),
				array(
					array('level3'),
					array(
						array('level4'),
					),
				),
			),
		);

		$output = Security::clean($input, array('trim'));
		$this->assertEquals($expected, $output);
	}

}
