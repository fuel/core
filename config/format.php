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

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */

return array(
	'csv' => array(
		'import' => array(
			'delimiter' => ',',
			'enclosure' => '"',
			'newline'   => "\n",
			'escape'    => '\\',
		),
		'export' => array(
			'delimiter' => ',',
			'enclosure' => '"',
			'newline'   => "\n",
			'escape'    => '\\',
		),
		'regex_newline'   => "\n",
		'enclose_numbers' => true,
	),
	'xml' => array(
		'basenode' => 'xml',
		'use_cdata' => false,
		'bool_representation' => null,
	),
	'json' => array(
		'encode' => array(
			'options' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
		),
	),
);
