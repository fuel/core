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
 * Date Class
 *
 * DateTime replacement that supports internationalization and does correction to GMT
 * when your webserver isn't configured correctly.
 *
 * @package     Fuel
 * @subpackage  Core
 * @category    Core
 * @link        http://docs.fuelphp.com/classes/date.html
 *
 * Notes:
 * - Always returns Date objects, will accept both Date objects and UNIX timestamps
 * - create_time() uses strptime and has currently a very bad hack to use strtotime for windows servers
 * - Uses strftime formatting for dates www.php.net/manual/en/function.strftime.php
 */
class Date
{
	/**
	 * Time constants (and only those that are constant, thus not MONTH/YEAR)
	 */
	const WEEK   = 604800;
	const DAY    = 86400;
	const HOUR   = 3600;
	const MINUTE = 60;

	/**
	 * @var int server's time() offset from gmt in seconds
	 */
	protected static $server_gmt_offset = 0;

	/**
	 * Date/time translation table from PHP date() to strftime()
	 *
	 */
	protected static $replacements = array(
		'%e'  => 'j', 			// Day of the month without leading zeros
		'%j'  => 'z', 			// Day of the year, 3 digits with leading zeros
		'%U'  => '_', 			// Week number of the given year, starting with the first Sunday as the first week (not implemented)
		'%h'  => 'M', 			// Abbreviated month name
		'%C'  => '_', 			// Two digit representation of the century (year divided by 100, truncated to an integer) (not implemented)
		'%g'  => 'y', 			// Two digit representation of the year going by ISO-8601:1988 standards (see %V)
		'%G'  => 'Y', 			// 4 digit year
		'%k'  => 'G', 			// Hour in 24-hour format
		'%l'  => 'g', 			// Hour in 12-hour format
		'%r'  => 'h:i:s A', 	// Example: 09:34:17 PM
		'%R'  => 'G:i', 		// Example: 00:35 for 12:35 AM
		'%T'  => 'G:i:s', 		// Example: 21:34:17 for 09:34:17 PM
		'%X'  => 'G:i:s', 		// Preferred time representation based on locale, without the date, Example: 03:59:16 or 15:59:16
		'%Z'  => 'T', 			// The time zone abbreviation. Example: EST for Eastern Time
		'%c'  => 'Y-m-d H:i:s', // Preferred date and time stamp based on locale
		'%D'  => 'm/d/y',		// Example: 02/05/09 for February 5, 2009
		'%F'  => 'Y-m-d',		// Example: 2009-02-05 for February 5, 2009
		'%n'  => '\\n',			// newline
		'%t'  => '\\t',			// tab
		'%%'  => '%', 			// literal percent
		'%A'  => 'l', 			// Name of day, long			Monday
		'%a'  => 'd',			// Name of day, short			Mon
		'%B'  => 'F',			// Name of month, long			April
		'%b'  => 'M',			// Name of month, short			Apr
		'%-d' => 'j',			// Day without leading zeros		1
		'%d'  => 'd',			// Day with leading zeros		01
		'%-m' => 'n',			// Month without leading zeros		4
		'%m'  => 'm',			// Month with leading zeros		04
		'%y'  => 'y',			// Year 2 character			12
		'%Y'  => 'Y',			// Year 4 character			2012
		'%u'  => 'N',			// Day of the week (1-7)		1
		'%w'  => 'w',			// Zero-based day of week (0-6)		0
		'%-j' => 'z',			// Day of the year (0-365)		123
		'%W'  => 'W',			// Week # of the year			42
		'%V'  => 'o',			// ISO-8601 week number			42
		'%P'  => 'a',			// am or pm				am
		'%p'  => 'A',			// AM or PM				AM
		'%-I' => 'g',			// 12-hour format, no leading zeros	5
		'%I'  => 'h',			// 12-hour format, leading zeros	05
		'%-H' => 'G',			// 24-hour format, no leading zeros	5
		'%H'  => 'H',			// 24-hour format, leading zeros	05
		'%M'  => 'i',			// Minutes				09
		'%S'  => 's', 			// Seconds				59
		'%s'  => 'U',			// Unix timestamp			123344556
	);

	/**
	 * @var string the timezone to be used to output formatted data
	 */
	public static $display_timezone = null;

	public static function _init()
	{
		static::$server_gmt_offset	= \Config::get('server_gmt_offset', 0);

		static::$display_timezone = \Config::get('default_timezone') ?: date_default_timezone_get();
	}

	/**
	 * Create Date object from timestamp, timezone is optional
	 *
	 * @param   int     $timestamp  UNIX timestamp from current server
	 * @param   string  $timezone   valid PHP timezone from www.php.net/timezones
	 * @return  Date
	 */
	public static function forge($timestamp = null, $timezone = null)
	{
		return new static($timestamp, $timezone);
	}

	/**
	 * Returns the current time with offset
	 *
	 * @param   string  $timezone   valid PHP timezone from www.php.net/timezones
	 * @return  Date
	 */
	public static function time($timezone = null)
	{
		return static::forge(null, $timezone);
	}

	/**
	 * Returns the current time with offset
	 *
	 * @param   string  $timezone   valid PHP timezone from www.php.net/timezones
	 * @return  string
	 */
	public static function display_timezone($timezone = null)
	{
		is_string($timezone) and static::$display_timezone = $timezone;

		return static::$display_timezone;
	}

	/**
	 * Uses the date config file to translate string input to timestamp
	 *
	 * @param   string  $input        date/time input
	 * @param   string  $pattern_key  key name of pattern in config file
	 * @return  Date
	 */
	public static function create_from_string($input, $pattern_key = 'local')
	{
		\Config::load('date', 'date');

		$pattern = \Config::get('date.patterns.'.$pattern_key, null);
		empty($pattern) and $pattern = $pattern_key;

		$time = static::strptime($input, $pattern);
		if ($time === false)
		{
			throw new \UnexpectedValueException('Input was not recognized by pattern.');
		}

		// make sure we don't go before the epoch, as that causes weird things to happen
		$time['tm_year'] <= 0 and $time['tm_year'] = 100;

		// convert it into a timestamp
		$timestamp = mktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'],
						$time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);

		if ($timestamp === false)
		{
			throw new \OutOfBoundsException('Input was invalid.'.(PHP_INT_SIZE == 4 ? ' A 32-bit system only supports dates between 1901 and 2038.' : ''));
		}

		return static::forge($timestamp);
	}

	/**
	 * Fetches an array of Date objects per interval within a range
	 *
	 * @param   int|Date    $start     start of the range
	 * @param   int|Date    $end       end of the range
	 * @param   int|string  $interval  Length of the interval in seconds or valid strtotime time difference
	 * @return   array      array of Date objects
	 */
	public static function range_to_array($start, $end, $interval = '+1 Day')
	{
		// make sure start and end are date objects
		$start = ( ! $start instanceof Date) ? static::forge($start) : $start;
		$end   = ( ! $end instanceof Date) ? static::forge($end) : $end;

		$range = array();

		// if end > start, the range is empty
		if ($end->get_timestamp() >= $start->get_timestamp())
		{
			$current = $start;
			$increment = $interval;

			do
			{
				$range[] = $current;

				if ( ! is_int($interval))
				{
					$increment = strtotime($interval, $current->get_timestamp()) - $current->get_timestamp();
					if ($increment <= 0)
					{
						throw new \UnexpectedValueException('Input was not recognized by pattern.');
					}
				}

				$current = static::forge($current->get_timestamp() + $increment);
			}
			while ($current->get_timestamp() <= $end->get_timestamp());
		}

		return $range;
	}

	/**
	 * Returns the number of days in the requested month
	 *
	 * @param   int  $month  month as a number (1-12)
	 * @param   int  $year   the year, leave empty for current
	 * @return  int  the number of days in the month
	 */
	public static function days_in_month($month, $year = null)
	{
		$year  = ! empty($year) ? (int) $year : (int) date('Y');
		$month = (int) $month;

		if ($month < 1 or $month > 12)
		{
			throw new \UnexpectedValueException('Invalid input for month given.');
		}
		elseif ($month == 2)
		{
			if ($year % 400 == 0 or ($year % 4 == 0 and $year % 100 != 0))
			{
				return 29;
			}
		}

		$days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		return $days_in_month[$month-1];
	}

	/**
	 * Returns the time ago
	 *
	 * @param	int		$timestamp       UNIX timestamp from current server
	 * @param	int		$from_timestamp  UNIX timestamp to compare against. Default to the current time
	 * @param	string	$unit            Unit to return the result in
	 * @return	string	Time ago
	 */
	public static function time_ago($timestamp, $from_timestamp = null, $unit = null)
	{
		if ($timestamp === null)
		{
			return '';
		}

		! is_numeric($timestamp) and $timestamp = static::create_from_string($timestamp)->get_timestamp();

		$from_timestamp == null and $from_timestamp = time();

		\Lang::load('date', true);

		$difference = $from_timestamp - $timestamp;
		$periods    = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
		$lengths    = array(60, 60, 24, 7, 4.35, 12, 10);

		for ($j = 0; isset($lengths[$j]) and $difference >= $lengths[$j] and (empty($unit) or $unit != $periods[$j]); $j++)
		{
			$difference /= $lengths[$j];
		}

        $difference = round($difference);

		if ($difference != 1)
		{
			$periods[$j] = \Inflector::pluralize($periods[$j]);
		}

		$text = \Lang::get('date.text', array(
			'time' => \Lang::get('date.'.$periods[$j], array('t' => $difference)),
		));

		return $text;
	}

	/**
	 * validate datetime according to a given pattern
	 *
	 * @param	string	$datetime        String containing some date/datetime/time
	 * @param	int		$format          Format to check against (see https://www.php.net/manual/en/datetime.createfromformat.php)
	 * @return	bool
	 */
	public static function is_valid($datetime, $format = 'Y-m-d H:i:s')
	{
		$d = \DateTime::createFromFormat($format, $datetime);
		return $d and $d->format($format) == $datetime;
	}

	/**
	 * strptime replacement for OS independency and PHP 8.1+ support
	 *
	 * This really is some fugly code, but someone at PHP HQ decided strptime should
	 * output this awful array instead of a timestamp LIKE EVERYONE ELSE DOES!!!
	 *
	 * @param	string	$input        String containing some date/datetime/time
	 * @param	string	$format       Format to check against (see https://www.php.net/manual/en/datetime.createfromformat.php)
	 * @return	bool
	 */

	public static function strptime($input, $format)
	{
		if (version_compare(PHP_VERSION, '8.1.0', '<'))
		{
			return strptime($input, $format);
		}

		// convert the format string from glibc to date format (where possible)
		$new_format = static::_strtr($format);

		// parse the input
		$parsed = date_parse_from_format($new_format, $input);

		// check for invalid dates
		if (isset($parsed['warnings'][10]))
		{
			return false;
		}

		// parse succesful?
		if (is_array($parsed) and empty($parsed['errors']))
		{
			return array(
				'tm_year' => $parsed['year'] - 1900,
				'tm_mon'  => $parsed['month'] - 1,
				'tm_mday' => $parsed['day'],
				'tm_hour' => $parsed['hour'] ?: 0,
				'tm_min'  => $parsed['minute'] ?: 0,
				'tm_sec'  => $parsed['second'] ?: 0,
			);
		}
		else
		{
			// workaround supporting only the usual suspects
			$masks = array(
				'%d' => '(?P<d>[0-9]{2})',
				'%m' => '(?P<m>[0-9]{2})',
				'%Y' => '(?P<Y>[0-9]{4})',
				'%H' => '(?P<H>[0-9]{2})',
				'%M' => '(?P<M>[0-9]{2})',
				'%S' => '(?P<S>[0-9]{2})',
			);

			$rexep = "#" . strtr(preg_quote($format), $masks) . "#";

			if ( ! preg_match($rexep, $input, $result))
			{
				return false;
			}

			return array(
				"tm_sec"  => isset($result['S']) ? (int) $result['S'] : 0,
				"tm_min"  => isset($result['M']) ? (int) $result['M'] : 0,
				"tm_hour" => isset($result['H']) ? (int) $result['H'] : 0,
				"tm_mday" => isset($result['d']) ? (int) $result['d'] : 0,
				"tm_mon"  => isset($result['m']) ? ($result['m'] ? $result['m'] - 1 : 0) : 0,
				"tm_year" => isset($result['Y']) ? ($result['Y'] > 1900 ? $result['Y'] - 1900 : 0) : 0,
			);
		}
	}

	/**
	 * strftime replacement for OS independency and PHP 8.1+ support
	 *
	 * @param	string	$format       Format to check against (see https://www.php.net/manual/en/datetime.createfromformat.php)
	 * @param	int	    $timestamp    Unix timestamp
	 * @return	string
	 * @thanks  https://gist.github.com/bohwaz/42fc223031e2b2dd2585aab159a20f30
 	 */
	public static function strftime($format, $timestamp)
	{
		if (version_compare(PHP_VERSION, '8.1.0', '<'))
		{
			return strftime($format, $timestamp);
		}

		if (is_null($timestamp))
		{
			$timestamp = new \DateTime;
		}
		elseif (is_numeric($timestamp))
		{
			$timestamp = date_create('@' . $timestamp);

			if ($timestamp)
			{
				$timestamp->setTimezone(new \DateTimezone(date_default_timezone_get()));
			}
		}
		elseif (is_string($timestamp))
		{
			$timestamp = date_create($timestamp);
		}

		if ( ! $timestamp instanceof \DateTimeInterface)
		{
			throw new \InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.');
		}

		$intl_formats = array(
			'%a' => 'EEE',	// An abbreviated textual representation of the day	Sun through Sat
			'%A' => 'EEEE',	// A full textual representation of the day	Sunday through Saturday
			'%b' => 'MMM',	// Abbreviated month name, based on the locale	Jan through Dec
			'%B' => 'MMMM',	// Full month name, based on the locale	January through December
			'%h' => 'MMM',	// Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
		);

		$intl_formatter = function (\DateTimeInterface $timestamp, $format) use ($intl_formats) {
			$tz = $timestamp->getTimezone();
			$date_type = \IntlDateFormatter::FULL;
			$time_type = \IntlDateFormatter::FULL;
			$pattern = '';

			// %c = Preferred date and time stamp based on locale
			// Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
			if ($format == '%c')
			{
				$date_type = \IntlDateFormatter::LONG;
				$time_type = \IntlDateFormatter::SHORT;
			}
			// %x = Preferred date representation based on locale, without the time
			// Example: 02/05/09 for February 5, 2009
			elseif ($format == '%x')
			{
				$date_type = \IntlDateFormatter::SHORT;
				$time_type = \IntlDateFormatter::NONE;
			}
			// Localized time format
			elseif ($format == '%X')
			{
				$date_type = \IntlDateFormatter::NONE;
				$time_type = \IntlDateFormatter::MEDIUM;
			}
			else
			{
				$pattern = $intl_formats[$format];
			}

			return (new \IntlDateFormatter(null, $date_type, $time_type, $tz, null, $pattern))->format($timestamp);
		};

		// Same order as https://www.php.net/manual/en/function.strftime.php
		$translation_table = array(
			// Day
			'%a' => $intl_formatter,
			'%A' => $intl_formatter,
			'%d' => 'd',
			'%e' => function ($timestamp) {
				return sprintf('% 2u', $timestamp->format('j'));
			},
			'%j' => function ($timestamp) {
				// Day number in year, 001 to 366
				return sprintf('%03d', $timestamp->format('z')+1);
			},
			'%u' => 'N',
			'%w' => 'w',

			// Week
			'%U' => function ($timestamp) {
				// Number of weeks between date and first Sunday of year
				$day = new \DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
				return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
			},
			'%V' => 'W',
			'%W' => function ($timestamp) {
				// Number of weeks between date and first Monday of year
				$day = new \DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
				return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
			},

			// Month
			'%b' => $intl_formatter,
			'%B' => $intl_formatter,
			'%h' => $intl_formatter,
			'%m' => 'm',

			// Year
			'%C' => function ($timestamp) {
				// Century (-1): 19 for 20th century
				return floor($timestamp->format('Y') / 100);
			},
			'%g' => function ($timestamp) {
				return substr($timestamp->format('o'), -2);
			},
			'%G' => 'o',
			'%y' => 'y',
			'%Y' => 'Y',

			// Time
			'%H' => 'H',
			'%k' => function ($timestamp) {
				return sprintf('% 2u', $timestamp->format('G'));
			},
			'%I' => 'h',
			'%l' => function ($timestamp) {
				return sprintf('% 2u', $timestamp->format('g'));
			},
			'%M' => 'i',
			'%p' => 'A', // AM PM (this is reversed on purpose!)
			'%P' => 'a', // am pm
			'%r' => 'h:i:s A', // %I:%M:%S %p
			'%R' => 'H:i', // %H:%M
			'%S' => 's',
			'%T' => 'H:i:s', // %H:%M:%S
			'%X' => $intl_formatter, // Preferred time representation based on locale, without the date

			// Timezone
			'%z' => 'O',
			'%Z' => 'T',

			// Time and Date Stamps
			'%c' => $intl_formatter,
			'%D' => 'm/d/Y',
			'%F' => 'Y-m-d',
			'%s' => 'U',
			'%x' => $intl_formatter,
		);

		$out = preg_replace_callback('/(?<!%)(%[a-zA-Z])/', function ($match) use ($translation_table, $timestamp) {
			if ($match[1] == '%n')
			{
				return "\n";
			}
			elseif ($match[1] == '%t')
			{
				return "\t";
			}

			if ( ! isset($translation_table[$match[1]]))
			{
				throw new \InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $match[1]));
			}

			$replace = $translation_table[$match[1]];

			if (is_string($replace))
			{
				return $timestamp->format($replace);
			}
			else
			{
				return $replace($timestamp, $match[1]);
			}
		}, $format);

		$out = str_replace('%%', '%', $out);
		return $out;
	}

	/*
	 *
	 */
	protected static function _strtr($format)
	{
		$new_format = "";
		while ($format != "")
		{
			$match = false;
			foreach (static::$replacements as $old => $new)
			{
				if (strpos($format, $old) === 0)
				{
					$new_format .= $new;
					$format = substr($format, strlen($old));
					$match = true;
					break;
				}
			}
			if ( ! $match)
			{
				$char = substr($format, 0, 1);
				$new_format .= ctype_alpha($char) ? "\\".$char : $char;
				$format = substr($format, 1);
			}
		}
		return $new_format;

	}

	/**
	 * @var  int  instance timestamp
	 */
	protected $timestamp;

	/**
	 * @var  string  output timezone
	 */
	protected $timezone;

	public function __construct($timestamp = null, $timezone = null)
	{
		is_null($timestamp) and $timestamp = time() + static::$server_gmt_offset;
		! $timezone and $timezone = \Fuel::$timezone;

		$this->timestamp = $timestamp;
		$this->set_timezone($timezone);
	}

	/**
	 * Returns the date formatted according to the current locale
	 *
	 * @param   string	$pattern_key  either a named pattern from date config file or a pattern, defaults to 'local'
	 * @param   mixed 	$timezone     vald timezone, or if true, output the time in local time instead of system time
	 * @return  string
	 */
	public function format($pattern_key = 'local', $timezone = null)
	{
		\Config::load('date', 'date');

		$pattern = \Config::get('date.patterns.'.$pattern_key, $pattern_key);

		// determine the timezone to switch to
		$timezone === true and $timezone = static::$display_timezone;
		is_string($timezone) or $timezone = $this->timezone;

		// Temporarily change timezone when different from default
		if (\Fuel::$timezone != $timezone)
		{
			date_default_timezone_set($timezone);
		}

		// Create output
		$output = static::strftime($pattern, $this->timestamp);

		// Change timezone back to default if changed previously
		if (\Fuel::$timezone != $timezone)
		{
			date_default_timezone_set(\Fuel::$timezone);
		}

		return $output;
	}

	/**
	 * Returns the internal timestamp
	 *
	 * @return  int
	 */
	public function get_timestamp()
	{
		return $this->timestamp;
	}

	/**
	 * Returns the internal timezone
	 *
	 * @return  string
	 */
	public function get_timezone()
	{
		return $this->timezone;
	}

	/**
	 * Returns the internal timezone or the display timezone abbreviation
	 *
	 * @param boolean $display_timezone
	 *
	 * @return  string
	 */
	public function get_timezone_abbr($display_timezone = false)
	{
		// determine the timezone to switch to
		$display_timezone and $timezone = static::$display_timezone;
		empty($timezone) and $timezone = $this->timezone;

		// Temporarily change timezone when different from default
		if (\Fuel::$timezone != $timezone)
		{
			date_default_timezone_set($timezone);
		}

		// Create output
		$output = date('T');

		// Change timezone back to default if changed previously
		if (\Fuel::$timezone != $timezone)
		{
			date_default_timezone_set(\Fuel::$timezone);
		}

		return $output;
	}

	/**
	 * Change the timezone
	 *
	 * @param   string  $timezone  timezone from www.php.net/timezones
	 * @return  Date
	 */
	public function set_timezone($timezone)
	{
		$this->timezone = $timezone;

		return $this;
	}

	/**
	 * Allows you to just put the object in a string and get it inserted in the default pattern
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->format();
	}
}
