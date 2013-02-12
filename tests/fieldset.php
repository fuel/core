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
 * Fieldset class tests
 *
 * @group Core
 * @group Fieldset
 */
class Test_Fieldset extends TestCase
{
	public function setUp()
	{
		// fake the uri for this request
		isset($_SERVER['PATH_INFO']) and $this->pathinfo = $_SERVER['PATH_INFO'];
		$_SERVER['PATH_INFO'] = '/welcome/index';

		// set Request::$main
		$request = \Request::forge('welcome/index');
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, $request);
	}

	public function tearDown()
	{
		// remove the fake uri
		if (property_exists($this, 'pathinfo'))
		{
			$_SERVER['PATH_INFO'] = $this->pathinfo;
		}
		else
		{
			unset($_SERVER['PATH_INFO']);
		}

		// reset Request::$main
		$request = \Request::forge();
		$rp = new \ReflectionProperty($request, 'main');
		$rp->setAccessible(true);
		$rp->setValue($request, false);
	}

	/**
	 * Test of "for" attribute in label tag
	 */
	public function test_for_in_label()
	{
		$form = Fieldset::forge(__METHOD__);
		$ops = array('male', 'female');
		$form->add('gender', '', array(
			'options' => $ops, 'type' => 'radio', 'value' => 1
		));

		$output = $form->build();
		$output = str_replace(array("\n", "\t"), "", $output);
		$expected = '<form action="welcome/index" accept-charset="utf-8" method="post"><table><tr><td class=""></td><td class=""><input type="radio" value="0" id="form_gender_0" name="gender" /> <label for="form_gender_0">male</label><br /><input type="radio" value="1" id="form_gender_1" name="gender" checked="checked" /> <label for="form_gender_1">female</label><br /><span></span></td></tr></table></form>';
		$this->assertEquals($expected, $output);
	}
}
