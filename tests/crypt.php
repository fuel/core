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
 * Crypt class tests
 *
 * @group Core
 * @group Crypt
 */
class Test_Crypt extends TestCase
{
	private static $config_backup;
	
	public static function setUpBeforeClass()
	{
		\Config::load('crypt', true);
		static::$config_backup = \Config::get('crypt');
		\Config::set('crypt.crypto_key', 'H9Eq4sGEwi7slEcWikRWE8xU');
		\Config::set('crypt.crypto_iv', 'tzcPXg2LEnB8vysdKw_Tsjo4');
		\Config::set('crypt.crypto_hmac', 'jX4p30_hYm7U-a85vov_M0P4');
		\Crypt::_init();
	}

	public static function tearDownAfterClass()
	{
		foreach (static::$config_backup as $key => $val)
		{
			\Config::set($key, $val);
		}
		\Crypt::_init();
	}

	public function test_encode_decode()
	{
		$encoded = 'LmdFdwMgA9QB8FFCmEQx1094YVRUa3JxXzl6OWxDUUN1d3RLcHB1Ykp6MGp1T21wNVNJUS1ZM3J3MDA';
		$clear_text = 'some string';
		$test = \Crypt::encode($clear_text);
		$this->assertEquals($encoded, $test);

		$test = \Crypt::decode($encoded);
		$this->assertEquals($clear_text, $test);
	}

	public function test_encode_decode_with_key()
	{
		$encoded = 'QE4AhW52POclh6PgPIVW5Xk3azVreHFfQjVmLXdVamNjbkZyZlE4YVQwQ1Fmc0Z3NE1fZlM0WDR4X00';
		$clear_text = 'some string';
		$test = \Crypt::encode($clear_text, 'this_is_key');
		$this->assertEquals($encoded, $test);

		$test = \Crypt::decode($encoded, 'this_is_key');
		$this->assertEquals($clear_text, $test);
	}

	public function test_encode_decode_large_data()
	{
		$bigstr = str_repeat("this is a crypto test of 200k or so of data", 5000);
		$bigstrhash = '391828747971d26de68550d935abaffa25f043795359417199ca39c09095dd11';
		$this->assertEquals($bigstrhash, hash('sha256', $bigstr));

		// Encrypt it without a key
		$test = \Crypt::encode($bigstr);
		$testhash = '26c14e2093adb93798bb1eabcae1c5bb0d1e3dca800bf7c546d1e79317979996';
		$this->assertEquals($testhash, hash('sha256', $test));

		// Decode it
		$output= \Crypt::decode($test);
		$this->assertEquals($bigstr, $output);
	}
}
