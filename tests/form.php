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
		$output = Form::input('name', '"\'H&M\'"');
		$expected = '<input name="name" value="&quot;&#39;H&amp;M&#39;&quot;" type="text" id="form_name" />';
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
		$output = Form::textarea('name', '"\'H&M\'"');
		$expected = '<textarea name="name" id="form_name">&quot;&#39;H&amp;M&#39;&quot;</textarea>';
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
						'key_"\'"' => 'val_"\'"',
			)
		);
		$expected = '<select name="fieldname" id="form_fieldname">
	<option value="key_H&amp;M" style="text-indent: 0px;">val_H&amp;M</option>
	<option value="key_&quot;&#39;&quot;" style="text-indent: 0px;">val_&quot;&#39;&quot;</option>
</select>';
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
		$expected = '<select name="fieldname" id="form_fieldname">
	<option value="key_H&amp;M" style="text-indent: 0px;">val_H&amp;M</option>
	<option value="key_&quot;&#39;&quot;" style="text-indent: 0px;">val_&quot;&#39;&quot;</option>
</select>';
		$this->assertEquals($expected, $output);
	}
}
