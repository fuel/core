<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.5
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Config class tests
 *
 * @group Core
 * @group Config
 */
class Test_Config extends TestCase
{

	public function provider_config()
	{
		return array(
			array(
				array(
					'foo' => 'bar',
					'fuel' => 'php',
					'multi' => array(
						'key' => 'value',
						'numbers' => array(1, 2),
					),
					'array' => array(1, 2, 3),
				),
			),
		);
	}

	/**
	 * Tests Config::load()
	 * 
	 * @test
	 * @dataProvider provider_config
	 */
	public function test_load($config)
	{
		\Config::load(array('test' => $config));

		$expected = $config;
		$this->assertEquals($expected, \Config::get('test'));
	}

	/**
	 * Tests Config::load()
	 * 
	 * @test
	 * @dataProvider provider_config
	 */
	public function test_load_group($config)
	{
		\Config::load(array('test' => $config), 'group');

		$expected = $config;
		$this->assertEquals($expected, \Config::get('group.test'));
	}

	/**
	 * Tests Config::load()
	 * 
	 * @test
	 * @dataProvider provider_config
	 */
	public function test_load_merge($config)
	{
		\Config::load(array('test' => $config));
		\Config::load(array(
			'test' => array(
				'foo' => 'boo',
				'multi' => array(
					'numbers' => array(),
				),
				'array' => array(1, 9),
			)
		));

		$expected = array(
			'foo' => 'boo',
			'fuel' => 'php',
			'multi' => array(
				'key' => 'value',
				'numbers' => array(),
			),
			'array' => array(1, 9),
		);
		$this->assertEquals($expected, \Config::get('test'));
	}

	/**
	 * Tests Config::load()
	 * 
	 * @test
	 * @dataProvider provider_config
	 */
	public function test_load_group_merge($config)
	{
		\Config::load(array('test' => $config), 'group');
		\Config::load(array(
			'test' => array(
				'foo' => 'boo',
				'multi' => array(
					'numbers' => array(),
				),
				'array' => array(1, 9),
			)
		), 'group');

		$expected = array(
			'foo' => 'boo',
			'fuel' => 'php',
			'multi' => array(
				'key' => 'value',
				'numbers' => array(),
			),
			'array' => array(1, 9),
		);
		$this->assertEquals($expected, \Config::get('group.test'));
	}

	/**
	 * Tests Config::set()
	 * 
	 * @test
	 * @dataProvider provider_config
	 */
	public function test_set($config)
	{
		\Config::load(array('test' => $config));
		\Config::set('test.multi.numbers', array());

		$expected = array();
		$this->assertEquals($expected, \Config::get('test.multi.numbers'));
	}

	/**
	 * Tests Config::delete()
	 * 
	 * @test
	 * @dataProvider provider_config
	 */
	public function test_delete($config)
	{
		\Config::load(array('test' => $config));
		\Config::delete('test.multi.key');

		$expected = null;
		$this->assertEquals($expected, \Config::get('test.multi.key'));
	}
}
