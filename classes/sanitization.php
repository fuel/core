<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

interface Sanitization
{
	/**
	 * Enable sanitization mode in the object
	 *
	 * @return  $this
	 */
	public function sanitize();

	/**
	 * Disable sanitization mode in the object
	 *
	 * @return  $this
	 */
	public function unsanitize();

	/**
	 * Returns the current sanitization state of the object
	 *
	 * @return  bool
	 */
	public function sanitized();
}
