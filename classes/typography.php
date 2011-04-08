<?php

namespace Fuel\Core;

class Typography {

	public static function auto($str)
	{
		View::$auto_encode = false;
		
		// remove tags
		$str = strip_tags($str);
		
		$str = static::html_entities($str);
		$str = static::format($str);
		
		return $str;
		
	}

	public static function format($str)
	{
		View::$auto_encode = false;
		
		if($str == '')
		{
			return '';
		}
		
		// remove whitespace from string
		$str = trim($str);
		
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
	
	public static function html_entities($str)
	{
		View::$auto_encode = false;
		
		if($str == '')
		{
			return '';
		}
		
		// reserved characters
		$ent_before = array('"', "'", "&", "<", ">");
		$ent_after = array("&#34;", "&#39;", "&#38;", "&#60;", "&#62;");
		
		// iso 8859-1 symbols
		array_push($ent_before, "¡", "¢", "£", "¤", "¥", "¦", "§", "¨", "©", "ª", "«", "¬", "­", "®", "¯", "°", "±", "²", "³", "´", "µ", "¶", "·", "¸", "¹", "º", "»", "¼", "½", "¾", "¿", "×", "÷");
		array_push($ent_after, "&#161;", "&#162;", "&#163;", "&#164;", "&#165;", "&#166;", "&#167;", "&#168;", "&#169;", "&#170;", "&#171;", "&#172;", "&#173;", "&#174;", "&#175;", "&#176;", "&#177;", "&#178;", "&#179;", "&#180;", "&#181;", "&#182;", "&#183;", "&#184;", "&#185;", "&#186;", "&#187;", "&#188;", "&#189;", "&#190;", "&#191;", "&#215;", "&#247;");
		
		// iso 8859-1 characters
		array_push($ent_before, "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "Þ", "ß", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "þ", "ÿ");
		array_push($ent_after, "&#192;", "&#193;", "&#194;", "&#195;", "&#196;", "&#197;", "&#198;", "&#199;", "&#200;", "&#201;", "&#202;", "&#203;", "&#204;", "&#205;", "&#206;", "&#207;", "&#208;", "&#209;", "&#210;", "&#211;", "&#212;", "&#213;", "&#214;", "&#216;", "&#217;", "&#218;", "&#219;", "&#220;", "&#221;", "&#222;", "&#223;", "&#224;", "&#225;", "&#226;", "&#227;", "&#228;", "&#229;", "&#230;", "&#231;", "&#232;", "&#233;", "&#234;", "&#235;", "&#236;", "&#237;", "&#238;", "&#239;", "&#240;", "&#241;", "&#242;", "&#243;", "&#244;", "&#245;", "&#246;", "&#248;", "&#249;", "&#250;", "&#251;", "&#252;", "&#253;", "&#254;", "&#255;");
		
		$str = str_replace($ent_before, $ent_after, $str);
		
		return $str;
		
	}

}

/* End of file typography.php */