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

namespace Fuel\Core;

/**
 * Arr class tests
 *
 * @group Core
 * @group Arr
 */
class Tests_Arr extends TestCase {

	public function person_provider()
	{
		return array(
			array(
				array(
					"name" => "Jack",
					"age" => "21",
					"weight" => 200,
					"location" => array(
						"city" => "Pittsburgh",
						"state" => "PA",
						"country" => "US"
					),
				),
			),
		);
	}

	/**
	 * Tests Arr::assoc_to_keyval()
	 *
	 * @test
	 */
	public function test_assoc_to_keyval()
	{
		$assoc = array(
			array(
				'color' => 'red',
				'rank' => 4,
				'name' => 'Apple',
				),
			array(
				'color' => 'yellow',
				'rank' => 3,
				'name' => 'Banana',
				),
			array(
				'color' => 'purple',
				'rank' => 2,
				'name' => 'Grape',
				),
			);
		
		$expected = array(
			'red' => 'Apple',
			'yellow' => 'Banana',
			'purple' => 'Grape',
			);
		$output = Arr::assoc_to_keyval($assoc, 'color', 'name');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::element()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_element_with_element_found($person)
	{
		$expected = "Jack";
		$output = Arr::element($person, "name", "Unknown Name");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::element()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_element_with_element_not_found($person)
	{
		$expected = "Unknown job";
		$output = Arr::element($person, "job", "Unknown job");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::element()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_element_with_dot_separated_key($person)
	{
		$expected = "Pittsburgh";
		$output = Arr::element($person, "location.city", "Unknown City");
		$this->assertEquals($expected, $output);

	}

	/**
	 * Tests Arr::element()
	 *
	 * @test
	 */
	public function test_element_when_array_is_not_an_array()
	{
		$expected = "Unknown Name";
		$output = Arr::element('Jack', 'name', 'Unknown Name');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::element()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_element_when_dot_notated_key_is_not_array($person)
	{
		$expected = "Unknown Name";
		$output = Arr::element($person, 'foo.first', 'Unknown Name');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::elements()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_elements_with_all_elements_found($person)
	{
		$expected = array(
			'name' => 'Jack',
			'weight' => 200,
		);
		$output = Arr::elements($person, array('name', 'weight'), 'Unknown');
		$this->assertEquals($expected, $output);
	}


	/**
	 * Tests Arr::elements()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_elements_with_all_elements_not_found($person)
	{
		$expected = array(
			'name' => 'Jack',
			'height' => 'Unknown',
		);
		$output = Arr::elements($person, array('name', 'height'), 'Unknown');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::elements()
	 *
	 * @test
	 * @dataProvider person_provider
	 * @expectedException InvalidArgumentException
	 */
	public function test_elements_throws_exception_when_keys_is_not_an_array($person)
	{
		$output = Arr::elements($person, 'name', 'Unknown');
	}

	/**
	 * Tests Arr::flatten_assoc()
	 *
	 * @test
	 */
	public function test_flatten_assoc()
	{
		$people = array(
			array(
				"name" => "Jack",
				"age" => 21
			),
			array(
				"name" => "Jill",
				"age" => 23
			)
		);

		$expected = array(
			"0:name" => "Jack",
			"0:age" => 21,
			"1:name" => "Jill",
			"1:age" => 23
		);

		$output = Arr::flatten_assoc($people);
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::insert()
	 *
	 * @test
	 */
	public function test_insert()
	{
		$people = array("Jack", "Jill");

		$expected = array("Humpty", "Jack", "Jill");
		$output = Arr::insert($people, "Humpty", 0);

		$this->assertEquals(true, $output);
		$this->assertEquals($expected, $people);
	}

	/**
	 * Tests Arr::insert()
	 *
	 * @test
	 */
	public function test_insert_with_index_out_of_range()
	{
		$people = array("Jack", "Jill");

		$output = Arr::insert($people, "Humpty", 4);

		$this->assertFalse($output);
	}

	/**
	 * Tests Arr::insert_after_key()
	 *
	 * @test
	 */
	public function test_insert_after_key_that_exists()
	{
		$people = array("Jack", "Jill");

		$expected = array("Jack", "Jill", "Humpty");
		$output = Arr::insert_after_key($people, "Humpty", 1);

		$this->assertTrue($output);
		$this->assertEquals($expected, $people);
	}

	/**
	 * Tests Arr::insert_after_key()
	 *
	 * @test
	 */
	public function test_insert_after_key_that_does_not_exist()
	{
		$people = array("Jack", "Jill");
		$output = Arr::insert_after_key($people, "Humpty", 6);
		$this->assertFalse($output);
	}

	/**
	 * Tests Arr::insert_after_value()
	 *
	 * @test
	 */
	public function test_insert_after_value_that_exists()
	{
		$people = array("Jack", "Jill");
		$expected = array("Jack", "Humpty", "Jill");
		$output = Arr::insert_after_value($people, "Humpty", "Jack");
		$this->assertTrue($output);
		$this->assertEquals($expected, $people);
	}

	/**
	 * Tests Arr::insert_after_value()
	 *
	 * @test
	 */
	public function test_insert_after_value_that_does_not_exists()
	{
		$people = array("Jack", "Jill");
		$output = Arr::insert_after_value($people, "Humpty", "Joe");
		$this->assertFalse($output);
	}

	/**
	 * Tests Arr::average()
	 *
	 * @test
	 */
	public function test_average()
	{
		$arr = array(13, 8, 6);
		$this->assertEquals(9, Arr::average($arr));
	}

	/**
	 * Tests Arr::average()
	 *
	 * @test
	 */
	public function test_average_of_empty_array()
	{
		$arr = array();
		$this->assertEquals(0, Arr::average($arr));
	}

	/**
	 * Tests Arr::element()
	 *
	 * @test
	 */
	public function test_filter_prefixed()
	{
		$arr = array('foo' => 'baz', 'prefix_bar' => 'yay');

		$output = Arr::filter_prefixed($arr);
		$this->assertEquals(array('bar' => 'yay'), $output);
	}

	/**
	 * Tests Arr::sort()
	 *
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function test_sort_of_non_array()
	{
		$sorted = Arr::sort('not an array', 'foo.key');
	}

	public function sort_provider()
	{
		return array(
			array(
				// Unsorted Array
				array(
					array(
						'info' => array(
							'pet' => array(
								'type' => 'dog'
							)
						),
					),
					array(
						'info' => array(
							'pet' => array(
								'type' => 'fish'
							)
						),
					),
					array(
						'info' => array(
							'pet' => array(
								'type' => 'cat'
							)
						),
					),
				),

				// Sorted Array
				array(
					array(
						'info' => array(
							'pet' => array(
								'type' => 'cat'
							)
						),
					),
					array(
						'info' => array(
							'pet' => array(
								'type' => 'dog'
							)
						),
					),
					array(
						'info' => array(
							'pet' => array(
								'type' => 'fish'
							)
						),
					),
				)
			)
		);
	}

	/**
	 * Tests Arr::sort()
	 *
	 * @test
	 * @dataProvider sort_provider
	 */
	public function test_sort_asc($data, $expected)
	{
		$this->assertEquals(Arr::sort($data, 'info.pet.type', 'asc'), $expected);
	}

	/**
	 * Tests Arr::sort()
	 *
	 * @test
	 * @dataProvider sort_provider
	 */
	public function test_sort_desc($data, $expected)
	{
		$expected = array_reverse($expected);
		$this->assertEquals(Arr::sort($data, 'info.pet.type', 'desc'), $expected);
	}

	/**
	 * Tests Arr::sort()
	 *
	 * @test
	 * @dataProvider sort_provider
	 * @expectedException InvalidArgumentException
	 */
	public function test_sort_invalid_direction($data, $expected)
	{
		$this->assertEquals(Arr::sort($data, 'info.pet.type', 'downer'), $expected);
	}

	/**
	 * Tests Arr::filter_keys()
	 * 
	 * @test
	 */
	public function test_filter_keys()
	{
		$data = array(
			'epic' => 'win',
			'weak' => 'sauce',
			'foo' => 'bar'
		);
		$expected = array(
			'epic' => 'win',
			'foo' => 'bar'
		);
		$expected_remove = array(
			'weak' => 'sauce',
		);
		$keys = array('epic', 'foo');
		$this->assertEquals(Arr::filter_keys($data, $keys), $expected);
		$this->assertEquals(Arr::filter_keys($data, $keys, true), $expected_remove);
	}

	/**
	 * Tests Arr::to_assoc()
	 *
	 * @test
	 */
	public function test_to_assoc_with_even_number_of_elements()
	{
		$arr = array('foo', 'bar', 'baz', 'yay');
		$expected = array('foo' => 'bar', 'baz' => 'yay');
		$this->assertEquals($expected, Arr::to_assoc($arr));
	}

	/**
	 * Tests Arr::to_assoc()
	 *
	 * @test
	 */
	public function test_to_assoc_with_odd_number_of_elements()
	{
		$arr = array('foo', 'bar', 'baz');
		$expected = null;
		$this->assertEquals($expected, Arr::to_assoc($arr));
	}
}

/* End of file arr.php */
