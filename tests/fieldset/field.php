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
 * Fieldset_Field class tests
 *
 * @group Core
 * @group Fieldset
 */
class Test_Fieldset_Field extends TestCase
{
	protected function setUp()
	{
		Config::load('form');
		Config::set('form', array(
			'prep_value'            => true,
			'auto_id'               => true,
			'auto_id_prefix'        => 'form_',
			'form_method'           => 'post',
			'form_template'         => "\n\t\t{open}\n\t\t<table>\n{fields}\n\t\t</table>\n\t\t{close}\n",
			'fieldset_template'     => "\n\t\t<tr><td colspan=\"2\">{open}<table>\n{fields}</table></td></tr>\n\t\t{close}\n",
			'field_template'        => "\t\t<tr>\n\t\t\t<td class=\"{error_class}\">{label}{required}</td>\n\t\t\t<td class=\"{error_class}\">{field} <span>{description}</span> {error_msg}</td>\n\t\t</tr>\n",
			'multi_field_template'  => "\t\t<tr>\n\t\t\t<td class=\"{error_class}\">{group_label}{required}</td>\n\t\t\t<td class=\"{error_class}\">{fields}\n\t\t\t\t{field} {label}<br />\n{fields}<span>{description}</span>\t\t\t{error_msg}\n\t\t\t</td>\n\t\t</tr>\n",
			'error_template'        => '<span>{error_msg}</span>',
			'required_mark'         => '*',
			'inline_errors'         => false,
			'error_class'           => 'validation_error',
		));
	}

	public function test_label_for()
	{
		$form = Fieldset::forge('form_test');

		$options = array('option1', 'option2');

		$form->add('fuel_text', 'Text field')->set_template('{label}');
		$form->add('fuel_radio', '', array('options' => $options, 'type' => 'radio', 'value' => 1))->set_template('{fields}{label}{fields}');
		$form->add('fuel_checkbox', '', array('options' => $options, 'type' => 'checkbox', 'value' => 1))->set_template('{fields}{label}{fields}');

		$text_html = '<label for="form_fuel_text">Text field</label>';
		$radio_html = '<label for="form_fuel_radio_0">option1</label><label for="form_fuel_radio_1">option2</label>';
		$checkbox_html = '<label for="form_fuel_checkbox_0">option1</label><label for="form_fuel_checkbox_1">option2</label>';

		$this->assertEquals($form->field('fuel_text')->build(), $text_html);
		$this->assertEquals($form->field('fuel_radio')->build(), $radio_html);
		$this->assertEquals($form->field('fuel_checkbox')->build(), $checkbox_html);
	}
}