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
 * Arr class tests
 *
 * @group Core
 * @group Arr
 */
class Test_Arr extends TestCase
{

	public static function person_provider()
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

	public static function collection_provider()
	{
		$object = new \stdClass;
		$object->id = 7;
		$object->name = 'Bert';
		$object->surname = 'Visser';

		return array(
			array(
				array(
					array(
						'id' => 2,
						'name' => 'Bill',
						'surname' => 'Cosby',
					),
					array(
						'id' => 5,
						'name' => 'Chris',
						'surname' => 'Rock',
					),
					$object,
				),
			),
		);
	}

	/**
	 * Test Arr::pluck()
	 *
	 * @test
	 * @dataProvider collection_provider
	 */
	public function test_pluck($collection)
	{
		$output = \Arr::pluck($collection, 'id');
		$expected = array(2, 5, 7);
		$this->assertEquals($expected, $output);
	}

	/**
	 * Test Arr::pluck()
	 *
	 * @test
	 * @dataProvider collection_provider
	 */
	public function test_pluck_with_index($collection)
	{
		$output = \Arr::pluck($collection, 'name', 'id');
		$expected = array(2 => 'Bill', 5 => 'Chris', 7 => 'Bert');
		$this->assertEquals($expected, $output);
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
	 * Tests Arr::keyval_to_assoc()
	 *
	 * @test
	 */
	public function test_keyval_to_assoc()
	{
		$keyval = array(
			'red' => 'Apple',
			'yellow' => 'Banana',
			'purple' => 'Grape',
			);

		$expected = array(
			array(
				'color' => 'red',
				'name' => 'Apple',
				),
			array(
				'color' => 'yellow',
				'name' => 'Banana',
				),
			array(
				'color' => 'purple',
				'name' => 'Grape',
				),
			);

		$output = Arr::keyval_to_assoc($keyval, 'color', 'name');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::key_exists()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_key_exists_with_key_found($person)
	{
		$expected = true;
		$output = Arr::key_exists($person, "name");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::key_exists()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_key_exists_with_key_not_found($person)
	{
		$expected = false;
		$output = Arr::key_exists($person, "unknown");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::key_exists()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_key_exists_with_dot_separated_key($person)
	{
		$expected = true;
		$output = Arr::key_exists($person, "location.city");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_with_element_found($person)
	{
		$expected = "Jack";
		$output = Arr::get($person, "name", "Unknown Name");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_with_element_not_found($person)
	{
		$expected = "Unknown job";
		$output = Arr::get($person, "job", "Unknown job");
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_with_dot_separated_key($person)
	{
		$expected = "Pittsburgh";
		$output = Arr::get($person, "location.city", "Unknown City");
		$this->assertEquals($expected, $output);

	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function test_get_throws_exception_when_array_is_not_an_array()
	{
		$output = Arr::get('Jack', 'name', 'Unknown Name');
	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_when_dot_notated_key_is_not_array($person)
	{
		$expected = "Unknown Name";
		$output = Arr::get($person, 'foo.first', 'Unknown Name');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_with_all_elements_found($person)
	{
		$expected = array(
			'name' => 'Jack',
			'weight' => 200,
		);
		$output = Arr::get($person, array('name', 'weight'), 'Unknown');
		$this->assertEquals($expected, $output);
	}


	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_with_all_elements_not_found($person)
	{
		$expected = array(
			'name' => 'Jack',
			'height' => 'Unknown',
		);
		$output = Arr::get($person, array('name', 'height'), 'Unknown');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::get()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_get_when_keys_is_not_an_array($person)
	{
		$expected = 'Jack';
		$output = Arr::get($person, 'name', 'Unknown');
		$this->assertEquals($expected, $output);
	}

	/**
	 * Tests Arr::flatten()
	 *
	 * @test
	 */
	public function test_flatten()
	{
		$indexed = array ( array('a'), array('b'), array('c') );

		$expected = array(
			"0_0" => "a",
			"1_0" => "b",
			"2_0" => "c",
		);

		$output = Arr::flatten($indexed, '_');
		$this->assertEquals($expected, $output);
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
	 * Tests Arr::merge_assoc()
	 *
	 * @test
	 */
	public function test_merge_assoc()
	{
		$arr1 = array(
			'one' => 1,
			2 => 2,
			3 => 3,
			4 => array(
				56
			),
			5=> 87
		);

		$arr2 = array(
			1 => 27,
			2 => 90,
			4 => array(
				'give_me' => 'bandwidth',
			),
			6 => '90',
			7 => 'php',
		);

		$expected = array(
			'one' => 1,
			2 => 90,
			3 => 3,
			4 => array(
				56,
				'give_me' => 'bandwidth',
			),
			5=> 87,
			1 => 27,
			6 => '90',
			7 => 'php',
		);

		$output = Arr::merge_assoc($arr1, $arr2);
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
	 * Tests Arr::filter_prefixed()
	 *
	 * @test
	 */
	public function test_filter_prefixed()
	{
		$arr = array('foo' => 'baz', 'prefix_bar' => 'yay');

		$output = Arr::filter_prefixed($arr, 'prefix_');
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
		$this->assertEquals($expected, Arr::sort($data, 'info.pet.type', 'asc'));
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
		$this->assertEquals($expected, Arr::sort($data, 'info.pet.type', 'desc'));
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
		$this->assertEquals($expected, Arr::sort($data, 'info.pet.type', 'downer'));
	}

	public function test_sort_empty()
	{
		$expected = array();
		$output = Arr::sort(array(), 'test', 'test');
		$this->assertEquals($expected, $output);
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
		$this->assertEquals($expected, Arr::filter_keys($data, $keys));
		$this->assertEquals($expected_remove, Arr::filter_keys($data, $keys, true));
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
	 * @expectedException BadMethodCallException
	 */
	public function test_to_assoc_with_odd_number_of_elements()
	{
		$arr = array('foo', 'bar', 'baz');
		Arr::to_assoc($arr);
	}

	/**
	 * Tests Arr::prepend()
	 *
	 * @test
	 */
	public function test_prepend()
	{
		$arr = array(
			'two' => 2,
			'three' => 3,
		);
		$expected = array(
			'one' => 1,
			'two' => 2,
			'three' => 3,
		);
		Arr::prepend($arr, 'one', 1);
		$this->assertEquals($expected, $arr);
	}

	/**
	 * Tests Arr::prepend()
	 *
	 * @test
	 */
	public function test_prepend_array()
	{
		$arr = array(
			'two' => 2,
			'three' => 3,
		);
		$expected = array(
			'one' => 1,
			'two' => 2,
			'three' => 3,
		);
		Arr::prepend($arr, array('one' => 1));
		$this->assertEquals($expected, $arr);
	}

	/**
	 * Tests Arr::is_multi()
	 *
	 * @test
	 */
	public function test_multidimensional_array()
	{
		// Single array
		$arr_single = array('one' => 1, 'two' => 2);
		$this->assertFalse(Arr::is_multi($arr_single));

		// Multi-dimensional array
		$arr_multi = array('one' => array('test' => 1), 'two' => array('test' => 2), 'three' => array('test' => 3));
		$this->assertTrue(Arr::is_multi($arr_multi));

		// Multi-dimensional array (not all elements are arrays)
		$arr_multi_strange = array('one' => array('test' => 1), 'two' => array('test' => 2), 'three' => 3);
		$this->assertTrue(Arr::is_multi($arr_multi_strange, false));
		$this->assertFalse(Arr::is_multi($arr_multi_strange, true));
	}

	/**
	 * Tests Arr::search()
	 *
	 * @test
	 */
	public function test_search_single_array()
	{
		// Single array
		$arr_single = array('one' => 1, 'two' => 2);
		$expected = 'one';
		$this->assertEquals($expected, Arr::search($arr_single, 1));

		// Default
		$expected = null;
		$this->assertEquals($expected, Arr::search($arr_single, 3));
		$expected = 'three';
		$this->assertEquals($expected, Arr::search($arr_single, 3, 'three'));

		// Single array (int key)
		$arr_single = array(0 => 'zero', 'one' => 1, 'two' => 2);
		$expected = 0;
		$this->assertEquals($expected, Arr::search($arr_single, 0));
	}

	/**
	 * Tests Arr::search()
	 *
	 * @test
	 */
	public function test_search_multi_array()
	{
		// Multi-dimensional array
		$arr_multi = array('one' => array('test' => 1), 'two' => array('test' => 2), 'three' => array('test' => array('a' => 'a', 'b' => 'b')));
		$expected = 'one';
		$this->assertEquals($expected, Arr::search($arr_multi, array('test' => 1), null, false));
		$expected = null;
		$this->assertEquals($expected, Arr::search($arr_multi, 1, null, false));

		// Multi-dimensional array (recursive)
		$expected = 'one.test';
		$this->assertEquals($expected, Arr::search($arr_multi, 1));

		$expected = 'three.test.b';
		$this->assertEquals($expected, Arr::search($arr_multi, 'b', null, true));
	}


	/**
	 * Tests Arr::sum()
	 *
	 * @test
	 */
	public function test_sum_multi_array()
	{
		$arr_multi = array(
			array(
				'name' => 'foo',
				'scores' => array(
					'sports' => 5,
					'math' => 20,
				),
			),
			array(
				'name' => 'bar',
				'scores' => array(
					'sports' => 7,
					'math' => 15,
				),
			),
			array(
				'name' => 'fuel',
				'scores' => array(
					'sports' => 8,
					'math' => 5,
				),
			),
			array(
				'name' => 'php',
				'scores' => array(
					'math' => 10,
				),
			),
		);

		$expected = 50;
		$test = \Arr::sum($arr_multi, 'scores.math');
		$this->assertEquals($expected, $test);

		$expected = 20;
		$test = \Arr::sum($arr_multi, 'scores.sports');
		$this->assertEquals($expected, $test);
	}

	/**
	 * Tests Arr::previous_by_key()
	 *
	 * @test
	 */
	public function test_previous_by_key()
	{
		// our test array
		$arr = array(2 => 'A', 4 => 'B', 6 => 'C');

		// test: key not found in array
		$expected = false;
		$test = \Arr::previous_by_key($arr, 1);
		$this->assertTrue($expected === $test);

		// test: no previous key
		$expected = null;
		$test = \Arr::previous_by_key($arr, 2);
		$this->assertTrue($expected === $test);

		// test: strict key comparison
		$expected = false;
		$test = \Arr::previous_by_key($arr, '2', false, true);
		$this->assertTrue($expected === $test);

		// test: get previous key
		$expected = 2;
		$test = \Arr::previous_by_key($arr, 4);
		$this->assertTrue($expected === $test);

		// test: get previous value
		$expected = 'A';
		$test = \Arr::previous_by_key($arr, 4, true);
		$this->assertTrue($expected === $test);
	}

	/**
	 * Tests Arr::next_by_key()
	 *
	 * @test
	 */
	public function test_next_by_key()
	{
		// our test array
		$arr = array(2 => 'A', 4 => 'B', 6 => 'C');

		// test: key not found in array
		$expected = false;
		$test = \Arr::next_by_key($arr, 1);
		$this->assertTrue($expected === $test);

		// test: no next key
		$expected = null;
		$test = \Arr::next_by_key($arr, 6);
		$this->assertTrue($expected === $test);

		// test: strict key comparison
		$expected = false;
		$test = \Arr::next_by_key($arr, '6', false, true);
		$this->assertTrue($expected === $test);

		// test: get next key
		$expected = 6;
		$test = \Arr::next_by_key($arr, 4);
		$this->assertTrue($expected === $test);

		// test: get next value
		$expected = 'C';
		$test = \Arr::next_by_key($arr, 4, true);
		$this->assertTrue($expected === $test);
	}

	/**
	 * Tests Arr::previous_by_value()
	 *
	 * @test
	 */
	public function test_previous_by_value()
	{
		// our test array
		$arr = array(2 => 'A', 4 => '2', 6 => 'C');

		// test: value not found in array
		$expected = false;
		$test = \Arr::previous_by_value($arr, 'Z');
		$this->assertTrue($expected === $test);

		// test: no previous value
		$expected = null;
		$test = \Arr::previous_by_value($arr, 'A');
		$this->assertTrue($expected === $test);

		// test: strict value comparison
		$expected = false;
		$test = \Arr::previous_by_value($arr, 2, true, true);
		$this->assertTrue($expected === $test);

		// test: get previous value
		$expected = 'A';
		$test = \Arr::previous_by_value($arr, '2');
		$this->assertTrue($expected === $test);

		// test: get previous key
		$expected = 4;
		$test = \Arr::previous_by_value($arr, 'C', false);
		$this->assertTrue($expected === $test);
	}

	/**
	 * Tests Arr::next_by_value()
	 *
	 * @test
	 */
	public function test_next_by_value()
	{
		// our test array
		$arr = array(2 => 'A', 4 => '2', 6 => 'C');

		// test: value not found in array
		$expected = false;
		$test = \Arr::next_by_value($arr, 'Z');
		$this->assertTrue($expected === $test);

		// test: no next value
		$expected = null;
		$test = \Arr::next_by_value($arr, 'C');
		$this->assertTrue($expected === $test);

		// test: strict value comparison
		$expected = false;
		$test = \Arr::next_by_value($arr, 2, true, true);
		$this->assertTrue($expected === $test);

		// test: get next value
		$expected = 'C';
		$test = \Arr::next_by_value($arr, '2');
		$this->assertTrue($expected === $test);

		// test: get next key
		$expected = 4;
		$test = \Arr::next_by_value($arr, 'A', false);
		$this->assertTrue($expected === $test);
	}

	/**
	 * Tests Arr::subset()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_subset_basic_usage($person)
	{
		$expected = array(
			"name" => "Jack",
			"location" => array(
				"city" => "Pittsburgh",
				"state" => "PA",
				"country" => "US"
			),
		);

		$got = \Arr::subset($person, array("name", "location"));
		$this->assertEquals($expected, $got);

		$expected = array(
			"name" => "Jack",
			"location" => array(
				"country" => "US"
			),
		);

		$got = \Arr::subset($person, array("name", "location.country"));
		$this->assertEquals($expected, $got);
	}

	/**
	 * Tests Arr::subset()
	 *
	 * @test
	 * @dataProvider person_provider
	 */
	public function test_subset_missing_items($person)
	{
		$expected = array(
			"name" => "Jack",
			"location" => array(
				"street" => null,
				"country" => "US"
			),
			"occupation" => null
		);

		$got = \Arr::subset($person, array("name", "location.street", "location.country", "occupation"));
		$this->assertEquals($expected, $got);

		$expected = array(
			"name" => "Jack",
			"location" => array(
				"street" => "Unknown",
				"country" => "US"
			),
			"occupation" => "Unknown"
		);

		$got = \Arr::subset($person, array("name", "location.street", "location.country", "occupation"), "Unknown");
		$this->assertEquals($expected, $got);
	}
}
