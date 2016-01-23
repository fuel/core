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

interface Cache_Handler_Driver {
	/**
	 * Should make the contents readable
	 *
	 * @param   mixed
	 * @return  mixed
	 */
	public function readable($contents);

	/**
	 * Should make the contents writable
	 *
	 * @param   mixed
	 * @return  mixed
	 */
	public function writable($contents);
}
