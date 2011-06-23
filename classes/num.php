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
 * Number helper class. Provides additional formatting methods for working with
 * numeric values.
 * 
 * Credit is left where credit is due.
 * 
 * Techniques and inspiration were taken from all over, including:
 *	Kohana Framework: kohanaframework.org
 *	Wordpress: wordpress.org
 * 
 * @package		Fuel
 * @category	Core
 * @author      Chase "Syntaqx" Hutchins
 */
class Num {

	/**
	 * Valid byte units => power of 2 that defines the unit's size
	 *
	 * @var		array
	 */
	public static $byte_units = array(
		'B'   => 0,
		'K'   => 10,
		'Ki'  => 10,
		'KB'  => 10,
		'KiB' => 10,
		'M'   => 20,
		'Mi'  => 20,
		'MB'  => 20,
		'MiB' => 20,
		'G'   => 30,
		'Gi'  => 30,
		'GB'  => 30,
		'GiB' => 30,
		'T'   => 40,
		'Ti'  => 40,
		'TB'  => 40,
		'TiB' => 40,
		'P'   => 50,
		'Pi'  => 50,
		'PB'  => 50,
		'PiB' => 50,
		'E'   => 60,
		'Ei'  => 60,
		'EB'  => 60,
		'EiB' => 60,
		'Z'   => 70,
		'Zi'  => 70,
		'ZB'  => 70,
		'ZiB' => 70,
		'Y'   => 80,
		'Yi'  => 80,
		'YB'  => 80,
		'YiB' => 80,
	);

	/**
	 * Add leading zeros to a number
	 *
	 * @link    http://core.svn.wordpress.org/trunk/wp-includes/formatting.php
	 * @param   integer
	 * @param   integer
	 * @return  string
	 */
	public function zeroise($number = 0, $threshold = 1)
	{
		return sprintf('%0'.$threshold.'s', $number);
	}

	/**
	 * Formats a number with a level of precision.
	 *
	 * @link    http://code.google.com/p/portaleconomiachaco/source/browse/sistemahorticola/views/helpers/number.php?spec=svn467&r=462
	 * @param   float     A floating point number.
	 * @param   integer   The precision of the returned number.
	 * @return  float
	 */
	public static function precision($number, $precision = 3)
	{
		return sprintf("%01.{$precision}f", $number);
	}

	/**
	 * Formats a number into a percentage string.
	 *
	 * @link    http://code.google.com/p/portaleconomiachaco/source/browse/sistemahorticola/views/helpers/number.php?spec=svn467&r=462
	 * @param   float     A floating point number
	 * @param   integer   The precision of the returned number
	 * @return  string    Percentage string
	 */
	public static function percentage($number, $precision = 2)
	{
		return static::precision($number, $precision).'%';
	}

	/**
	 * Determines the difference between two timestamps.
	 *
	 * The difference is returned in a human readable format such as "1 hour",
	 * "5 mins", "2 days".
	 *
	 * @link    http://core.svn.wordpress.org/trunk/wp-includes/formatting.php
	 * @param   integer
	 * @param   integer
	 * @return  string
	 */
	public static function human_time_diff($from = 0, $to = null)
	{
		if (empty($to))
		{
			$to = time();
		}

		$diff = (int) abs($to - $from);

		if ($diff <= 3600)
		{
			$mins = round($diff / 60);

			if ($mins <= 1)
			{
				$mins = 1;
			}

			if($mins == 1)
			{
				$since = sprintf('%s min', $mins);
			}
			else
			{
				$since = sprintf('%s mins', $mins);
			}
		}
		elseif (($diff <= 86400) && ($diff > 3600))
		{
			$hours = round($diff / 3600);
			
			if ($hours <= 1)
			{
				$hours = 1;
			}
			
			if($hours == 1)
			{
				$since = sprintf('%s hour', $hours);
			}
			else
			{
				$since = sprintf('%s hours', $hours);
			}
		}
		elseif ($diff >= 86400)
		{
			$days = round($diff / 86400);
			
			if ($days <= 1)
			{
				$days = 1;
			}
			
			if($days == 1)
			{
				$since = sprintf('%s day', $days);
			}
			else
			{
				$since = sprintf('%s days', $days);
			}
		}

		return $since;
	}
	
	/**
	 * Converts a file size number to a byte value. File sizes are defined in
	 * the format: SB, where S is the size (1, 8.5, 300, etc.) and B is the
	 * byte unit (K, MiB, GB, etc.). All valid byte units are defined in
	 * static::$byte_units
	 *
	 * Usage:
	 * <code>
	 * echo Num::bytes('200K');  // 204800
	 * echo static::bytes('5MiB');  // 5242880
	 * echo static::bytes('1000');  // 1000
	 * echo static::bytes('2.5GB'); // 2684354560
	 * </code>
	 *
	 * @author     Kohana Team
	 * @copyright  (c) 2009-2011 Kohana Team
	 * @license    http://kohanaframework.org/license
	 * @param      string   file size in SB format
	 * @return     float
	 */
	public static function bytes($size = 0)
	{
		// Prepare the size
		$size = trim((string) $size);

		// Construct an OR list of byte units for the regex
		$accepted = implode('|', array_keys(static::$byte_units));

		// Construct the regex pattern for verifying the size format
		$pattern = '/^([0-9]+(?:\.[0-9]+)?)('.$accepted.')?$/Di';

		// Verify the size format and store the matching parts
		if (!preg_match($pattern, $size, $matches))
		{
			throw new Exception('The byte unit size, "'.$size.'", is improperly formatted.');
		}

		// Find the float value of the size
		$size = (float) $matches[1];

		// Find the actual unit, assume B if no unit specified
		$unit = Arr::element($matches, 2, 'B');

		// Convert the size into bytes
		$bytes = $size * pow(2, static::$byte_units[$unit]);

		return $bytes;
	}

	/**
	 * Converts a number of bytes to a human readable number by taking the
	 * number of that unit that the bytes will go into it. Supports TB value.
	 *
	 * Note: Integers in PHP are limited to 32 bits, unless they are on 64 bit
	 * architectures, then they have 64 bit size. If you need to place the
	 * larger size then what the PHP integer type will hold, then use a string.
	 * It will be converted to a double, which should always have 64 bit length.
	 *
	 * @link    http://core.svn.wordpress.org/trunk/wp-includes/functions.php
	 * @param   integer
	 * @param   integer
	 * @return  boolean|string
	 */
	public static function format_bytes($bytes = 0, $decimals = 0)
	{
		$quant = array(
            'TB' => 1099511627776,  // pow( 1024, 4)
            'GB' => 1073741824,     // pow( 1024, 3)
            'MB' => 1048576,        // pow( 1024, 2)
            'kB' => 1024,           // pow( 1024, 1)
            'B ' => 1,              // pow( 1024, 0)
        );

        foreach ($quant as $unit => $mag )
		{
            if (doubleval($bytes) >= $mag)
			{
                return static::precision($bytes / $mag, $decimals).' '.$unit;
			}
		}

        return false;
	}

	/**
	 * Formats a number by injecting non-numeric characters in a specified
	 * format into the string in the positions they appear in the format.
	 * 
	 * Usage:
	 * <code>
	 * echo Num::format('1234567890', '(000) 000-0000'); // (123) 456-7890
	 * echo Num::format('1234567890', '000.000.0000'); // 123.456.7890
	 * </code>
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the string to format
	 * @param   string     the format to apply
	 * @return  string
	 */
	public static function format($string = '', $format = '')
	{
		if(empty($format) or empty($string))
		{
			return $string;
		}
		
		$result = '';
		$fpos = 0;
		$spos = 0;
		
		while ((strlen($format) - 1) >= $fpos)
		{
			if (static::is_alphanumeric(substr($format, $fpos, 1)))
			{
				$result .= substr($string, $spos, 1);
				$spos++;
			}
			else
			{
				$result .= substr($format, $fpos, 1);
			}

			$fpos++;
		}

		return $result;
	}

	/**
	 * Transforms a number by masking characters in a specified mask format, and
	 * ignoring characters that should be injected into the string without
	 * matching a character from the original string (defaults to space).
	 *
	 * Usage:
	 * <code>
	 * echo Num::mask_string('1234567812345678', '************0000'); ************5678
	 * echo Num::mask_string('1234567812345678', '**** **** **** 0000'); // **** **** **** 5678
	 * echo Num::mask_string('1234567812345678', '**** - **** - **** - 0000', ' -'); // **** - **** - **** - 5678
	 * </code>
	 * 
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the string to transform
	 * @param   string     the mask format
	 * @param   string     a string (defaults to a single space) containing characters to ignore in the format
	 * @return  string     the masked string
	 */
	public static function mask_string($string = '', $format = '', $ignore = ' ')
	{
		if(empty($format) or empty($string))
		{
			return $string;
		}

		$result = '';
		$fpos = 0;
		$spos = 0;

		while ((strlen($format) - 1) >= $fpos)
		{
			if (static::is_alphanumeric(substr($format, $fpos, 1)))
			{
				$result .= substr($string, $spos, 1);
				$spos++;
			}
			else
			{
				$result .= substr($format, $fpos, 1);

				if (strpos($ignore, substr($format, $fpos, 1)) === false)
				{
					++$spos;
				}
			}

			++$fpos;
		}

		return $result;
	}

	/**
	 * Formats a phone number.
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string the unformatted phone number to format
	 * @param   string the format to use, defaults to '(000) 000-0000'
	 * @return  string the formatted string
	 * @see     format
	 */
	public static function format_phone($string = '', $format = '(000) 000-0000')
	{
		return static::format($string, $format);
	}

	/**
	 * Formats a variable length phone number, using a standard format.
	 *
	 * Usage:
	 * <code>
	 * echo Num::smart_format_phone('1234567'); // 123-4567
	 * echo Num::smart_format_phone('1234567890'); // (123) 456-7890
	 * echo Num::smart_format_phone('91234567890'); // (123) 456-7890
	 * echo Num::smart_format_phone('123456'); // => 123456
	 * </code>
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the unformatted phone number to format
	 * @see     format
	 */
	public static function smart_format_phone($string)
	{
		switch (strlen($string))
		{
			case 7:
			{
				return static::format($string, '000-0000');
			}
			case 10:
			{
				return static::format($string, '(000) 000-0000');
			}
			case 11:
			{
				return static::format($string, '0 (000) 000-0000');
			}
			default:
			{
				return $string;
			}
		}
	}

	/**
	 * Formats a U.S. Social Security Number.
	 *
	 * Usage:
	 * <code>
	 * echo Num::format_ssn('123456789'); // 123-45-6789
	 * </code>
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the unformatted ssn to format
	 * @param   string     the format to use, defaults to '000-00-0000'
	 * @see     format
	 */
	public static function format_ssn($string, $format = '000-00-0000')
	{
		return static::format($string, $format);
	}

	/**
	 * Formats a credit card expiration string. Expects 4-digit string (MMYY).
	 *
	 * @param   string     the unformatted expiration string to format
	 * @param   string     the format to use, defaults to '00-00'
	 * @see     format
	 */
	public static function format_exp($string, $format = '00-00')
	{
		return static::format($string, $format);
	}
	
	/**
	 * Formats (masks) a credit card.
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the unformatted credit card number to format
	 * @param   string     the format to use, defaults to '**** **** **** 0000'
	 * @see     mask_string
	 */
	public static function mask_credit_card($string, $format = '**** **** **** 0000')
	{
		return static::mask_string($string, $format);
	}
	
	/**
	 * Formats a USD currency value with two decimal places and a dollar sign.
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the unformatted amount to format
	 * @param   string     the format to use, defaults to '$%0.2f'
	 * @see     sprintf
	 */
	public static function format_usd($money, $dollar = true, $format = '%0.2f')
	{
		return ($dollar ? '$' : '').sprintf($format, $money);
	}

	/**
	 * Determines if a string has only alpha/numeric characters.
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string     the string to check as alpha/numeric
	 * @return  boolean
	 * @see     is_numeric
	 * @see     preg_match
	 */
	public static function is_alphanumeric($string)
	{
		return ctype_alnum($string);
	}
}

/* End of file num.php */