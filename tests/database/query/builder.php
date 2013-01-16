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
 * Database_Query_Builder class tests
 *
 * @group Core
 * @group Database
 */
class Test_Database_Query_Builder extends TestCase
{
	protected function get_accessible_method($class_name, $method_name)
	{
		$refl = new \ReflectionClass($class_name);
		$method = $refl->getMethod($method_name);
		$method->setAccessible(true);
		return $method;
	}

	protected function get_db_mock()
	{
		$db =  $this->getMockBuilder('Database_Connection')
			->disableOriginalConstructor()
			->getMock();

		$db->expects($this->any())
			->method('quote_identifier')
			->will($this->returnCallback(function ($str) { return '`'.$str.'`'; })); 

		$db->expects($this->any())
			->method('quote')
			->will($this->returnCallback(function ($str) { return '\''.$str.'\''; }));

		return $db;
	}

	public function test_foo() {}

	public function test_where_in_with_integer_list() 
	{
		$db = $this->get_db_mock();

		$tester = new Database_Query_Builder_Tester("");
		$method = $this->get_accessible_method('\\Fuel\\Core\\Database_Query_Builder_Tester', '_compile_conditions');

		$sql = $method->invokeArgs($tester, array(
			$db, array(
				array(
					'AND' => array('id', 'in', array(1,2,3))
				)
			)));

		$expected = "`id` IN (1,2,3)";

		$this->assertEquals($expected, $sql);
	}

	public function test_where_in_with_string_list()
	{
		$db = $this->get_db_mock();

		$tester = new Database_Query_Builder_Tester("");
		$method = $this->get_accessible_method('\\Fuel\\Core\\Database_Query_Builder_Tester', '_compile_conditions');

		$sql = $method->invokeArgs($tester, array(
			$db, array(
				array(
					'AND' => array('field', 'in', array('test1','test2'))
				)
			)));
		$expected = "`field` IN ('test1','test2')";
		$this->assertEquals($expected, $sql);

		$sql = $method->invokeArgs($tester, array(
			$db, array(
				array(
					'AND' => array('field', 'in', 'test1')
				)
			)));
		$expected = "`field` IN ('test1')";
		$this->assertEquals($expected, $sql);
	}

	public function test_where_in_with_mixed_list()
	{
		$db = $this->get_db_mock();

		$tester = new Database_Query_Builder_Tester("");
		$method = $this->get_accessible_method('\\Fuel\\Core\\Database_Query_Builder_Tester', '_compile_conditions');

		$sql = $method->invokeArgs($tester, array(
			$db, array(
				array(
					'AND' => array('field', 'in', array(null, true, false, '2013-01-01', 'test', 13, '1.2.3.4', '3.1415'))
				)
			)));

		$expected = "`field` IN (NULL,TRUE,FALSE,'2013-01-01','test',13,'1.2.3.4','3.1415')";
		$this->assertEquals($expected, $sql);
	}
}

class Database_Query_Builder_Tester extends \Fuel\Core\Database_Query_Builder
{
	public function reset () {}
}
