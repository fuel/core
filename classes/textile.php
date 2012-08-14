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

use Textile_Parser;

/**
 * This is a small wrapper around the Textile class.
 *
 * @package     Fuel
 * @subpackage  Core
 */
class Textile
{
	/**
	 * @var  Textile_Parser  The TX parser instance
	 */
	protected static $parser = null;

	/**
	 * Load Markdown and get it setup.
	 *
	 * @return  void
	 */
	public static function _init()
	{
		if ( ! class_exists('Textile_Parser', false))
		{
			include COREPATH.'vendor'.DS.'textile'.DS.'textile.php';
		}

		static::$parser = new Textile_Parser();
	}

	/**
	 * Runs the given text through the Markdown parser.
	 *
	 * @param   string  Text to parse
	 * @return  string
	 */
	public static function parse($text)
	{
		return static::$parser->TextileThis($text);
	}
}
