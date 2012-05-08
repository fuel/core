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
 * Form class tests
 *
 * @group Core
 * @group Form
 */
class Test_Form extends TestCase
{
	/**
	* Tests Form::input()
	*
	* test for data prepping
	*
	* @test
	*/
	public function test_input_prep()
	{
		$output = Form::input('name', '"H&M"');
		$expected = '<input name="name" value="&quot;H&amp;M&quot;" type="text" id="form_name" />';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::input()
	*
	* test for dont_prep
	*
	* @test
	*/
	public function test_input_dont_prep()
	{
		$output = Form::input('name', '&quot;&#39;H&amp;M&#39;&quot;', array('dont_prep' => true));
		$expected = '<input name="name" value="&quot;&#39;H&amp;M&#39;&quot;" type="text" id="form_name" />';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::textarea()
	*
	* test for data prepping
	*
	* @test
	*/
	public function test_textarea_prep()
	{
		$output = Form::textarea('name', '"H&M"');
		$expected = '<textarea name="name" id="form_name">&quot;H&amp;M&quot;</textarea>';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::textarea()
	*
	* test for dont_prep
	*
	* @test
	*/
	public function test_textarea_dont_prep()
	{
		$output = Form::textarea('name', '&quot;&#39;H&amp;M&#39;&quot;', array('dont_prep' => true));
		$expected = '<textarea name="name" id="form_name">&quot;&#39;H&amp;M&#39;&quot;</textarea>';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::select()
	*
	* test for data prepping
	*
	* @test
	*/
	public function test_select_prep()
	{
		$output = Form::select('fieldname', null,
			array(
						'key_H&M' => 'val_H&M',
						'key_""' => 'val_""',
			)
		);
		$expected = '<select name="fieldname" id="form_fieldname">'.PHP_EOL
					.'	<option value="key_H&amp;M" style="text-indent: 0px;">val_H&amp;M</option>'.PHP_EOL
					.'	<option value="key_&quot;&quot;" style="text-indent: 0px;">val_&quot;&quot;</option>'.PHP_EOL
					.'</select>';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::prep_value()
	*
	* @test
	*/
	public function test_prep_value()
	{
		$output = Form::prep_value('<"H&M">');
		$expected = '&lt;&quot;H&amp;M&quot;&gt;';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::select()
	*
	* test for dont_prep
	*
	* @test
	*/
	public function test_select_dont_prep()
	{
		$output = Form::select('fieldname', null,
			array(
						'key_H&amp;M' => 'val_H&amp;M',
						'key_&quot;&#39;&quot;' => 'val_&quot;&#39;&quot;',
			),
			array(
						'dont_prep' => true,
			)
		);
		$expected = '<select name="fieldname" id="form_fieldname">'.PHP_EOL
					.'	<option value="key_H&amp;M" style="text-indent: 0px;">val_H&amp;M</option>'.PHP_EOL
					.'	<option value="key_&quot;&#39;&quot;" style="text-indent: 0px;">val_&quot;&#39;&quot;</option>'.PHP_EOL
					.'</select>';
		$this->assertEquals($expected, $output);
	}

	/**
	* Tests Form::prep_value()
	*
	* test of invalid string
	*
	* @test
	*/
	public function test_prep_value_invalid_utf8()
	{
		// 6 byte UTF-8 string, which is invalid now
		$utf8_string = "\xFC\x84\x80\x80\x80\x80";
		$output = Form::prep_value($utf8_string);
		$expected = '';
		$this->assertEquals($expected, $output);
	}
}
