<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2015 Fuel Development Team
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
