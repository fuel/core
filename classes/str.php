<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

/**
 * String handling with encoding support
 *
 * PHP needs to be compiled with --enable-mbstring
 * or a fallback without encoding support is used
 */
class Str
{
	/**
	 * Truncates a string to the given length.  It will optionally preserve
	 * HTML tags if $is_html is set to true.
	 *
	 * @param   string  $string        the string to truncate
	 * @param   int     $limit         the number of characters to truncate too
	 * @param   string  $continuation  the string to use to denote it was truncated
	 * @param   bool    $is_html       whether the string has HTML
	 * @return  string  the truncated string
	 */
	public static function truncate($string, $limit, $continuation = '...', $is_html = false)
	{
		static $self_closing_tags = array(
			'area', 'base', 'br', 'col', 'command', 'embed'
			, 'hr', 'img', 'input', 'keygen', 'link', 'meta'
			, 'param', 'source', 'track', 'wbr'
		);

		$offset = 0;
		$tags = array();
		if ($is_html)
		{
			// Handle special characters.
			preg_match_all('/&[a-z]+;/i', strip_tags($string), $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
			// fix preg_match_all broken multibyte support
			if (MBSTRING and strlen($string !== mb_strlen($string)))
			{
				$correction = 0;
				foreach ($matches as $index => $match)
				{
					$matches[$index][0][1] -= $correction;
					$correction += (strlen($match[0][0]) - mb_strlen($match[0][0]));
				}
			}
			foreach ($matches as $match)
			{
				if ($match[0][1] >= $limit)
				{
					break;
				}
				$limit += (static::length($match[0][0]) - 1);
			}

			// Handle all the html tags.
			preg_match_all('/<[^>]+>([^<]*)/', $string, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
			// fix preg_match_all broken multibyte support
			if (MBSTRING and strlen($string !== mb_strlen($string)))
			{
				$correction = 0;
				foreach ($matches as $index => $match)
				{
					$matches[$index][0][1] -= $correction;
					$matches[$index][1][1] -= $correction;
					$correction += (strlen($match[0][0]) - mb_strlen($match[0][0]));
				}
			}

			foreach ($matches as $match)
			{
				if($match[0][1] - $offset >= $limit)
				{
					break;
				}

				$tag = static::sub(strtok($match[0][0], " \t\n\r\0\x0B>"), 1);
				if ($tag[0] != '/')
				{
					if ( ! in_array($tag, $self_closing_tags))
					{
						$tags[] = $tag;
					}
				}
				elseif (end($tags) == static::sub($tag, 1))
				{
					array_pop($tags);
				}
				$offset += $match[1][1] - $match[0][1];
			}
		}

		$new_string = static::sub($string, 0, $limit = min(static::length($string),  $limit + $offset));
		$new_string .= (static::length($string) > $limit ? $continuation : '');
		$new_string .= (count($tags = array_reverse($tags)) ? '</'.implode('></', $tags).'>' : '');
		return $new_string;
	}

	/**
	 * Add's _1 to a string or increment the ending number to allow _2, _3, etc
	 *
	 * @param   string  $str        required
	 * @param   int     $first      number that is used to mean first
	 * @param   string  $separator  separtor between the name and the number
	 * @return  string
	 */
	public static function increment($str, $first = 1, $separator = '_')
	{
		preg_match('/(.+)'.$separator.'([0-9]+)$/', $str, $match);

		return isset($match[2]) ? $match[1].$separator.($match[2] + 1) : $str.$separator.$first;
	}

	/**
	 * Checks whether a string has a precific beginning.
	 *
	 * @param   string   $str          string to check
	 * @param   string   $start        beginning to check for
	 * @param   boolean  $ignore_case  whether to ignore the case
	 * @return  boolean  whether a string starts with a specified beginning
	 */
	public static function starts_with($str, $start, $ignore_case = false)
	{
		return (bool) preg_match('/^'.preg_quote($start, '/').'/m'.($ignore_case ? 'i' : ''), (string) $str);
	}

	/**
	 * Checks whether a string has a precific ending.
	 *
	 * @param   string   $str          string to check
	 * @param   string   $end          ending to check for
	 * @param   boolean  $ignore_case  whether to ignore the case
	 * @return  boolean  whether a string ends with a specified ending
	 */
	public static function ends_with($str, $end, $ignore_case = false)
	{
		return (bool) preg_match('/'.preg_quote($end, '/').'$/m'.($ignore_case ? 'i' : ''), $str);
	}

	/**
	  * Creates a random string of characters
	  *
	  * @param   string  $type    the type of string
	  * @param   int     $length  the number of characters
	  * @return  string  the random string
	  */
	public static function random($type = 'alnum', $length = 16)
	{
		switch($type)
		{
			case 'basic':
				return mt_rand();
				break;

			default:
			case 'alnum':
			case 'numeric':
			case 'nozero':
			case 'alpha':
			case 'distinct':
			case 'hexdec':
				switch ($type)
				{
					case 'alpha':
						$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;

					default:
					case 'alnum':
						$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;

					case 'numeric':
						$pool = '0123456789';
						break;

					case 'nozero':
						$pool = '123456789';
						break;

					case 'distinct':
						$pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
						break;

					case 'hexdec':
						$pool = '0123456789abcdef';
						break;
				}

				$str = '';
				for ($i=0; $i < $length; $i++)
				{
					$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
				}
				return $str;
				break;

			case 'unique':
				return md5(uniqid(mt_rand()));
				break;

			case 'sha1' :
				return sha1(uniqid(mt_rand(), true));
				break;

			case 'uuid':
			    $pool = array('8', '9', 'a', 'b');
				return sprintf('%s-%s-4%s-%s%s-%s',
					static::random('hexdec', 8),
					static::random('hexdec', 4),
					static::random('hexdec', 3),
					$pool[array_rand($pool)],
					static::random('hexdec', 3),
					static::random('hexdec', 12));
				break;
		}
	}

	/**
	 * Returns a closure that will alternate between the args which to return.
	 * If you call the closure with false as the arg it will return the value without
	 * alternating the next time.
	 *
	 * @return  Closure
	 */
	public static function alternator()
	{
		// the args are the values to alternate
		$args = func_get_args();

		return function ($next = true) use ($args)
		{
			static $i = 0;
			return $args[($next ? $i++ : $i) % count($args)];
		};
	}

	/**
	 * Parse the params from a string using strtr()
	 *
	 * @param   string  $string  string to parse
	 * @param   array   $array   params to str_replace
	 * @return  string
	 */
	public static function tr($string, $array = array())
	{
		if (is_string($string))
		{
			$tr_arr = array();

			foreach ($array as $from => $to)
			{
				substr($from, 0, 1) !== ':' and $from = ':'.$from;
				$tr_arr[$from] = $to;
			}
			unset($array);

			return strtr($string, $tr_arr);
		}
		else
		{
			return $string;
		}
	}

	/**
	 * Check if a string is json encoded
	 *
	 * @param  string $string string to check
	 * @return bool
	 */
	public static function is_json($string)
	{
		json_decode($string);
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Check if a string is a valid XML
	 *
	 * @param  string  $string  string to check
	 * @return bool
	 * @throws \FuelException
	 */
	public static function is_xml($string)
	{
		if ( ! defined('LIBXML_COMPACT'))
		{
			throw new \FuelException('libxml is required to use Str::is_xml()');
		}

		$internal_errors = libxml_use_internal_errors();
		libxml_use_internal_errors(true);
		$result = simplexml_load_string($string) !== false;
		libxml_use_internal_errors($internal_errors);

		return $result;
	}

	/**
	 * Check if a string is serialized
	 *
	 * @param  string  $string  string to check
	 * @return bool
	 */
	public static function is_serialized($string)
	{
		$array = @unserialize($string);
		return ! ($array === false and $string !== 'b:0;');
	}

	/**
	 * Check if a string is html
	 *
	 * @param  string $string string to check
	 * @return bool
	 */
	public static function is_html($string)
	{
		return strlen(strip_tags($string)) < strlen($string);
	}

	// multibyte functions

	/**
	 * strpos — Find the position of the first occurrence of a substring in a string
	 *
	 * @param  string $str        The string being measured for length.
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return int                The length of the string on success, and 0 if the string is empty.
	 */
	public static function strlen($str, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strlen($str, $encoding)
			: strlen($str);
	}

	/**
	 * strpos — Find position of first occurrence of string in a string
	 *
	 * @param  string $haystack   The string being checked
	 * @param  mixed  $needle     The string to find in haystack
	 * @param  int    $offset     The search offset
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed              Returns the position of where the needle exists relative to the beginning
	 *                            of the haystack string (independent of offset). Also note that string
	 *                            positions start at 0, and not 1.
	 *                            Returns FALSE if the needle was not found.
	 */
	public static function strpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strpos($haystack, $needle, $offset, $encoding)
			: strpos($haystack, $needle, $offset);
	}

	/**
	 * strrpos — Find position of last occurrence of a string in a string
	 *
	 * @param  string $haystack   The string being checked
	 * @param  mixed  $needle     The string to find in haystack
	 * @param  int    $offset     The search offset
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed              Returns the numeric position of the last occurrence of needle in the
	 *                            haystack string. If needle is not found, it returns FALSE.
	 */
	public static function strrpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strrpos($haystack, $needle, $offset, $encoding)
			: strrpos($haystack, $needle, $offset);
	}

	/*
	 * substr — Get part of string
	 *
	 * @param  string $str        The string to extract the substring from
	 * @param  int    $start      If start is non-negative, the returned string will start at the start'th
	 *                            position in str, counting from zero. If start is negative, the returned
	 *                            string will start at the start'th character from the end of str.
	 * @param  int    $length     Maximum number of characters to use from str. If omitted or NULL is passed,
	 *                            extract all characters to the end of the string.
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed             Returns the extracted part of string; or FALSE on failure, or an empty string.
	 */
	public static function substr($str, $start, $length = null, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		// substr functions don't parse null correctly if the string is multibyte
		$length = is_null($length)
			? (MBSTRING ? mb_strlen($str, $encoding)
			: strlen($str)) - $start : $length;

		return (MBSTRING and $encoding)
			? mb_substr($str, $start, $length, $encoding)
			: substr($str, $start, $length);
	}

	/**
	 * strtolower — Make a string lowercase
	 *
	 * @param  string $str        The string to convert to lowercase
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return  string            The lowercased string
	 */
	public static function strtolower($str, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strtolower($str, $encoding)
			: strtolower($str);
	}

	/**
	 * strtoupper — Make a string uppercase
	 *
	 * @param  string $str        The string to convert to uppercase
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return  string            The uppercased string
	 */
	public static function strtoupper($str, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strtoupper($str, $encoding)
			: strtoupper($str);
	}

	/**
	 * stripos — Find the position of the first occurrence of a case-insensitive substring in a string
	 *
	 * @param  string $haystack   The string from which to get the position of the last occurrence of needle
	 * @param  mixed  $needle     The string to find in haystack
	 * @param  int    $offset     The search offset
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed              Returns the position of where the needle exists relative to the beginning
	 *                            of the haystack string (independent of offset). Also note that string
	 *                            positions start at 0, and not 1.
	 *                            Returns FALSE if the needle was not found.
	 */
	public static function stripos($haystack, $needle, $offset = 0, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_stripos($haystack, $needle, $offset, $encoding)
			: stripos($haystack, $needle, $offset);
	}

	/**
	 * strripos — Finds position of last occurrence of a string within another, case insensitive
	 *
	 * @param  string $haystack   The string from which to get the position of the last occurrence of needle
	 * @param  mixed  $needle     The string to find in haystack
	 * @param  int    $offset     The search offset
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed              Returns the numeric position of the last occurrence of needle in the
	 *                            haystack string. If needle is not found, it returns FALSE.
	 */
	public static function strripos($haystack, $needle, $offset = 0, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strripos($haystack, $needle, $offset, $encoding)
			: strripos($haystack, $needle, $offset);
	}

	/**
	 * strstr — Finds first occurrence of a string within another
	 *
	 * @param  string $haystack       The string from which to get the position of the last occurrence of needle
	 * @param  mixed  $needle         The string to find in haystack
	 * @param  int    $before_needle  Determines which portion of haystack this function returns
	 * @param  string $encoding       Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed                  The portion of haystack, or FALSE if needle is not found
	 */
	public static function strstr($haystack, $needle, $before_needle = false, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strstr($haystack, $needle, $before_needle, $encoding)
			: strstr($haystack, $needle, $before_needle);
	}

	/**
	 * stristr — Finds first occurrence of a string within another, case-insensitive
	 *
	 * @param  string $haystack       The string from which to get the position of the last occurrence of needle
	 * @param  mixed  $needle         The string to find in haystack
	 * @param  int    $before_needle  Determines which portion of haystack this function returns
	 * @param  string $encoding       Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed                  The portion of haystack, or FALSE if needle is not found
	 */
	public static function stristr($haystack, $needle, $before_needle = false, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_stristr($haystack, $needle, $before_needle, $encoding)
			: stristr($haystack, $needle, $before_needle);
	}

	/**
	 * strrchr — Finds the last occurrence of a character in a string within another
	 *
	 * @param  string $haystack   The string from which to get the last occurrence of needle
	 * @param  mixed  $needle     The string to find in haystack
	 * @param  int    $part       Determines which portion of haystack this function returns
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return mixed              The portion of haystack, or FALSE if needle is not found
	 */
	public static function strrchr($haystack, $needle, $before_needle = false, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strrchr($haystack, $needle, $part, $encoding)
			: strrchr($haystack, $needle, $part);
	}

	/**
	 * substr_count — Count the number of substring occurrences
	 *
	 * @param  string $haystack   The string from which to get the position of the last occurrence of needle
	 * @param  mixed  $needle     The string to find in haystack
	 * @param  int    $offset     The search offset
	 * @param  string $encoding   Defaults to the setting in the config, which defaults to UTF-8
	 *
	 * @return int                The number of occurences found
	 */
	public static function substr_count($haystack, $needle, $offset = 0, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_substr_count($haystack, $needle, $offset, $encoding)
			: substr_count($haystack, $needle, $offset);
	}

	/**
	 * lcfirst
	 *
	 * Does not strtoupper first
	 *
	 * @param   string  $str       required
	 * @param   string  $encoding  default UTF-8
	 * @return  string
	 */
	public static function lcfirst($str, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strtolower(mb_substr($str, 0, 1, $encoding), $encoding).
				mb_substr($str, 1, mb_strlen($str, $encoding), $encoding)
			: lcfirst($str);
	}

	/**
	 * ucfirst
	 *
	 * Does not strtolower first
	 *
	 * @param   string $str       required
	 * @param   string $encoding  default UTF-8
	 * @return  string
	 */
	public static function ucfirst($str, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).
				mb_substr($str, 1, mb_strlen($str, $encoding), $encoding)
			: ucfirst($str);
	}

	/**
	 * ucwords
	 *
	 * First strtolower then ucwords
	 *
	 * ucwords normally doesn't strtolower first
	 * but MB_CASE_TITLE does, so ucwords now too
	 *
	 * @param   string   $str       required
	 * @param   string   $encoding  default UTF-8
	 * @return  string
	 */
	public static function ucwords($str, $encoding = null)
	{
		$encoding or $encoding = \Fuel::$encoding;

		return (MBSTRING and $encoding)
			? mb_convert_case($str, MB_CASE_TITLE, $encoding)
			: ucwords(strtolower($str));
	}

	// deprecated methods

	public static function length($str, $encoding = null)
	{
		return static::strlen($str, $encoding);
	}

	public static function sub($str, $start, $length = null, $encoding = null)
	{
		return static::substr($str, $start, $length, $encoding);
	}

	public static function lower($str, $encoding = null)
	{
		return static::strtolower($str, $encoding);
	}

	public static function upper($str, $encoding = null)
	{
		return static::strtoupper($str, $encoding);
	}
}
