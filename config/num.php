<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  [!] NOTICE
 * -----------------------------------------------------------------------------
 *
 *  If you need to make modifications to the default configuration,
 *  copy this file to your 'app/config' folder, and make them in there.
 *
 *  This will allow you to upgrade FuelPHP without losing your custom config.
 *
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Format
	 * -------------------------------------------------------------------------
	 *
	 *  Defaults used for formatting options.
	 *
	 *  See how to use it here (https://fuelphp.com/docs/classes/num.html)
	 *
	 */

	'formatting' => array(
		'phone' => '(000) 000-0000',

		'smart_phone' => array(
			7  => '000-0000',
			10 => '(000) 000-0000',
			11 => '0 (000) 000-0000',
		),

		'credit_card' => '**** **** **** 0000',

		'exp' => '00-00',
	),
);
