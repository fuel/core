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
 * Debug class tests
 *
 * @group Core
 * @group Debug
 */
class Test_Debug extends TestCase
{
    public function setUp()
    {
        // Remember old value, and set to browser mode.
        $this->old_is_cli = \Fuel::$is_cli;
        \Fuel::$is_cli = false;
    }

    public function tearDown()
    {
        // Restore original value
        \Fuel::$is_cli = $this->old_is_cli;
    }

 	public function test_debug_dump_normally()
 	{
		$expected = '	<script type="text/javascript">function fuel_debug_toggle(a){if(document.getElementById){if(document.getElementById(a).style.display=="none"){document.getElementById(a).style.display="block"}else{document.getElementById(a).style.display="none"}}else{if(document.layers){if(document.id.display=="none"){document.id.display="block"}else{document.id.display="none"}}else{if(document.all.id.style.display=="none"){document.all.id.style.display="block"}else{document.all.id.style.display="none"}}}};</script><div class="fuelphp-dump" style="font-size: 13px;background: #EEE !important; border:1px solid #666; color: #000 !important; padding:10px;"><h1 style="border-bottom: 1px solid #CCC; padding: 0 0 5px 0; margin: 0 0 5px 0; font: bold 120% sans-serif;">COREPATH/tests/debug.php @ line: 41</h1><pre style="overflow:auto;font-size:100%;"><strong>Variable #1:</strong>'."\n".'<i></i> <strong></strong> (Integer): 1'."\n\n\n".'<strong>Variable #2:</strong>'."\n".'<i></i> <strong></strong> (Integer): 2'."\n\n\n".'<strong>Variable #3:</strong>'."\n".'<i></i> <strong></strong> (Integer): 3'."\n\n\n".'</pre></div>';

		ob_start();
 		\Debug::dump(1, 2, 3);
 		$output = ob_get_contents();
 		ob_end_clean();

		$this->assertEquals($expected, $output);
 	}

  	public function test_debug_dump_by_call_user_func_array()
 	{
		$expected = '<div class="fuelphp-dump" style="font-size: 13px;background: #EEE !important; border:1px solid #666; color: #000 !important; padding:10px;"><h1 style="border-bottom: 1px solid #CCC; padding: 0 0 5px 0; margin: 0 0 5px 0; font: bold 120% sans-serif;">COREPATH/tests/debug.php @ line: 53</h1><pre style="overflow:auto;font-size:100%;"><strong>Variable #1:</strong>'."\n".'<i></i> <strong></strong> (Integer): 1'."\n\n\n".'<strong>Variable #2:</strong>'."\n".'<i></i> <strong></strong> (Integer): 2'."\n\n\n".'<strong>Variable #3:</strong>'."\n".'<i></i> <strong></strong> (Integer): 3'."\n\n\n".'</pre></div>';

		ob_start();
 		call_user_func_array('\\Debug::dump', array(1, 2, 3));
 		$output = ob_get_contents();
 		ob_end_clean();

		$this->assertEquals($expected, $output);
 	}

  	public function test_debug_dump_by_call_fuel_func_array()
 	{
		$expected = '<div class="fuelphp-dump" style="font-size: 13px;background: #EEE !important; border:1px solid #666; color: #000 !important; padding:10px;"><h1 style="border-bottom: 1px solid #CCC; padding: 0 0 5px 0; margin: 0 0 5px 0; font: bold 120% sans-serif;">COREPATH/base.php @ line: %d</h1><pre style="overflow:auto;font-size:100%;"><strong>Variable #1:</strong>
<i></i> <strong></strong> (Integer): 1


<strong>Variable #2:</strong>
<i></i> <strong></strong> (Integer): 2


<strong>Variable #3:</strong>
<i></i> <strong></strong> (Integer): 3


</pre></div>';

		if (PHP_VERSION_ID >= 50600)
		{
			$expected = str_replace('base.php', 'base56.php', $expected);
		}

		ob_start();
 		call_fuel_func_array('\\Debug::dump', array(1, 2, 3));
 		$output = ob_get_contents();
 		ob_end_clean();

		$this->assertStringMatchesFormat($expected, $output);
 	}
}
