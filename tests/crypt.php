<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
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
	public function setup_config()
	{
		\Config::load('crypt', true);
		$this->config_backup = \Config::get('crypt');
		\Config::set('crypt.crypto_iv', 'tzcPXg2LEnB8vysdKw_Tsjo4');
		\Config::set('crypt.crypto_hmac', 'jX4p30_hYm7U-a85vov_M0P4');
		\Crypt::_init();
	}

	public function restore_config()
	{
		foreach ($this->config_backup as $key => $val)
		{
			\Config::set($key, $val);
		}
		\Crypt::_init();
	}

	public function test_encode_decode()
	{
		$this->setup_config();

		$encoded = 'QE4AhW52POclh6PgPIVW5Xk3azVreHFfQjVmLXdVamNjbkZyZlE4YVQwQ1Fmc0Z3NE1fZlM0WDR4X00';
		$clear_text = 'some string';
		$test = \Crypt::encode($clear_text, 'this_is_key');
		$this->assertEquals($encoded, $test);

		$test = \Crypt::decode($encoded, 'this_is_key');
		$this->assertEquals($clear_text, $test);

		$this->restore_config();
	}
}
