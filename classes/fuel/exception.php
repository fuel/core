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
 * Default exception, mainly used for general errors. All Fuel specific
 * exceptions extend this exception
 *
 * @author     Chase "Syntaqx" Hutchins <syntaqx@gmail.com>
 * @version    1.0
 * @package    Fuel
 */
class Fuel_Exception extends \Exception {

	public function __construct()
	{
		if(($count = \func_num_args()) === 0)
		{
			parent::__construct('Unknown error');
		}
		else
		{
			$args = \func_get_args();

			if($count == 2 and is_array($args[1]))
			{
				$line = \Lang::line($args[0], $args[1]);

				if($line !== false and $line != $args[1])
				{
					$args = array($line);
				}
			}

			parent::__construct(\call_user_func_array('\sprintf', $args));
		}
	}
}

/* End of file exception.php */