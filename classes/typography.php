<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Jon Stuebe <http://jonstuebe.com>
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 */

namespace Fuel\Core;

class Typography {

	public static function format($str)
	{
		
		if($str == '')
		{
			return '';
		}
		
		// remove whitespace from string
		$str = trim($str);
		
		// convert html entites
		$str = htmlentities($str, ENT_QUOTES);
		
		// standardize new line characters
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		
		// Convert two consecutive newlines to paragraphs
		$str = str_replace("\n\n", "</p>\n\n<p>", $str);
		
		// convert multiple spacese to single spaces
		$str = preg_replace('!\s+!', " ", $str);
		$str = str_replace("<p> ","<p>",$str);

		// wrap the string in paragraphs
		$str =  '<p>'.$str.'</p>';

		// remove empty paragraph tags
		$str = preg_replace("/<p><\/p>(.*)/", "\\1", $str, 1);
		
		return $str;
		
	}

}

/* End of file typography.php */