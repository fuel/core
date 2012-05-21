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

namespace Fuel\Core;

/**
 * Validation class tests
 *
 * @group Core
 * @group Validation
 */
class Test_Validation extends TestCase
{
 	public function test_foo() {}
 	  
 	public function test_validation_required_with()
 	{
 	  $input = array(
 	    'foo' => 'bar',
 	    'bar' => 'foo',
 	  );
 	  
 	  $val = Validation::forge('foo');
 	  $val->add_field('foo', 'Foo', 'valid_string');
 	  $val->add_field('bar', 'Bar', 'required_with[foo]');
 	  $output = $val->run($input);
 	  
 	  $expected = true;
 	  
 	  $this->assertEquals($output, $expected);
 	}
 	
 	public function test_validation_required_with_error()
 	{
 	  $input = array(
 	    'foo' => 'bar',
 	    'bar' => null,
 	  );
 	  
 	  $val = Validation::forge('bar');
 	  $val->add_field('foo', 'Foo', 'valid_string');
 	  $val->add_field('bar', 'Bar', 'required_with[foo]');
 	  $val->run($input);
 	  
 	  $output = $val->error('bar', false) ? true : false;
 	  
 	  $expected = true;
 	  
 	  $this->assertEquals($output, $expected);
 	}
}
