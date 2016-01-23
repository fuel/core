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
 * ViewModel, alias for Presenter, for BC purposes
 *
 * @package	    Fuel
 * @subpackage  Core
 * @category    Core
 */
abstract class Viewmodel extends \Presenter
{
	// namespace prefix
	protected static $ns_prefix = 'View_';
}
