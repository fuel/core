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

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */


return array(
	'prep_value'			=> true,
	'auto_id'				=> true,
	'auto_id_prefix'		=> 'form_',
	'form_method'			=> 'post',
	'form_template'			=> "\t\t{form_open}\n{fields}\n\t\t{form_close}\n",
	'field_template'		=> "\t\t\t{label} {field} {error_msg}\n",
	'multi_field_template'	=> "\t\t\t{group_label}{required}\n {fields}\t\t\t{label} {field}{fields}",
	'required_mark'			=> '*',
	'inline_errors'			=> false,
	'error_class'			=> 'invalid'
);


