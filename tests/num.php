<?php
/**
 * Fuel
 *
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
 * Numeric helper tests
 *
 * @package		Fuel
 * @category	Core
 * @author      Chase "Syntaqx" Hutchins
 */
class Tests_Num extends TestCase {

	/**
	 * @see     Num::bytes
	 */
	public static function test_bytes($size = 0)
	{
		$output = Num::bytes('200K');
		$expected = '204800';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::format_bytes
	 */
	public static function test_format_bytes($bytes = 0, $decimals = 0)
	{
		$output = Num::format_bytes('204800');
		$expected = '200 kB';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::quantity
	 */
	public static function test_quantity($num = null, $decimals = 0)
	{
		$output = Num::quantity('7500');
		$expected = '8K';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::format
	 */
	public static function test_format($string = '', $format = '')
	{
		$output = Num::format('1234567890', '(000) 000-0000');
		$expected = '(123) 456-7890';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::mask_string
	 */
	public static function test_mask_string($string = '', $format = '', $ignore = ' ')
	{
		$output = Num::mask_string('1234567812345678', '**** - **** - **** - 0000', ' -');
		$expected = '**** - **** - **** - 5678';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::format_phone
	 */
	public static function test_format_phone($string = '', $format = '(000) 000-0000')
	{
		$output = Num::format_phone('1234567890');
		$expected = '(123) 456-7890';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::smart_format_phone
	 */
	public static function test_smart_format_phone($string)
	{
		$output = Num::smart_phone_format('1234567');
		$expected = '123-4567';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::format_exp
	 */
	public static function test_format_exp($string, $format = '00-00')
	{
		$output = Num::format_exp('1234');
		$expected = '12-34';

		$this->assertEquals($expected, $output);
	}
	
	/**
	 * @see     Num::mask_credit_card
	 */
	public static function test_mask_credit_card($string, $format = '**** **** **** 0000')
	{
		$output = Num::mask_credit_card('1234567812345678');
		$expected = '**** **** **** 5678';

		$this->assertEquals($expected, $output);
	}

	/**
	 * @see     Num::is_alphanumeric
	 */
	public static function test_is_alphanumeric($string)
	{
		$output = Num::is_alphanumeric('abc123');
		$expected = true;

		$this->assertEquals($expected, $output);
	}
}

/* End of file num.php */