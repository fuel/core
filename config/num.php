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

return array(

	/**
	 * Defaults used for formatting options
	 *
	 * @var   array
	 */
	'formatting' => array(
		// Num::format_phone()
		'phone' => '(000) 000-0000',
		// Num::smart_format_phone()
		'smart_phone' => array(
			7  => '000-0000',
			10 => '(000) 000-0000',
			11 => '0 (000) 000-0000',
		),
		// Num::format_exp()
		'exp' => '00-00',
		// Num::mask_credit_card()
		'credit_card' => '**** **** **** 0000',
	),

);

/* End of file config/num.php */
