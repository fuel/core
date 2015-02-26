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
 * Fieldset class tests
 *
 * @group Core
 * @group Fieldset
 */
class Test_Fieldset extends TestCase
{
	public function setUp()
	{
		// fake the uri for this request
		isset($_SERVER['PATH_INFO']) and $this->pathinfo = $_SERVER['PATH_INFO'];
		$_SERVER['PATH_INFO'] = '/welcome/index';

		// set Request::$main
		$request = \Request::forge('welcome/index');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);
		\Request::active($request);
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
	}

	/**
	 * Test of "for" attribute in label tag
	 */
	public function test_for_in_label()
	{
		$form = Fieldset::forge(__METHOD__)->set_config(array(
			// regular form definitions
			'prep_value'                 => true,
			'auto_id'                    => true,
			'auto_id_prefix'             => 'form_',
			'form_method'                => 'post',
			'form_template'              => "\n\t\t{open}\n\t\t<table>\n{fields}\n\t\t</table>\n\t\t{close}\n",
			'fieldset_template'          => "\n\t\t<tr><td colspan=\"2\">{open}<table>\n{fields}</table></td></tr>\n\t\t{close}\n",
			'field_template'             => "\t\t<tr>\n\t\t\t<td class=\"{error_class}\">{label}{required}</td>\n\t\t\t<td class=\"{error_class}\">{field} <span>{description}</span> {error_msg}</td>\n\t\t</tr>\n",
			'multi_field_template'       => "\t\t<tr>\n\t\t\t<td class=\"{error_class}\">{group_label}{required}</td>\n\t\t\t<td class=\"{error_class}\">{fields}\n\t\t\t\t{field} {label}<br />\n{fields}<span>{description}</span>\t\t\t{error_msg}\n\t\t\t</td>\n\t\t</tr>\n",
			'error_template'             => '<span>{error_msg}</span>',
			'group_label'	             => '<span>{label}</span>',
			'required_mark'              => '*',
			'inline_errors'              => false,
			'error_class'                => 'validation_error',

			// tabular form definitions
			'tabular_form_template'      => "<table>{fields}</table>\n",
			'tabular_field_template'     => "{field}",
			'tabular_row_template'       => "<tr>{fields}</tr>\n",
			'tabular_row_field_template' => "\t\t\t<td>{label}{required}&nbsp;{field} {icon} {error_msg}</td>\n",
			'tabular_delete_label'       => "Delete?",
		));
		$ops = array('male', 'female');
		$form->add('gender', '', array(
			'options' => $ops, 'type' => 'radio', 'value' => 1,
		));

		$output = $form->build();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<form action="welcome/index" accept-charset="utf-8" method="post"><table><tr><td class=""></td><td class=""><input type="radio" value="0" id="form_gender_0" name="gender" /> <label for="form_gender_0">male</label><br /><input type="radio" value="1" id="form_gender_1" name="gender" checked="checked" /> <label for="form_gender_1">female</label><br /><span></span></td></tr></table></form>';
		$this->assertEquals($expected, $output);
	}
}
