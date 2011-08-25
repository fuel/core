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
 * Lang Class
 *
 * @package		Fuel
 * @category	Core
 * @author		Phil Sturgeon
 * @link		http://fuelphp.com/docs/classes/lang.html
 */
class Lang {

	/**
	 * @var  array  language lines
	 */
	public static $lines = array();

	/**
	 * @var  array  language(s) to fall back on when loading a file from the current lang fails
	 */
	public static $fallback;

	public static function _init()
	{
		static::$fallback = (array) \Config::get('language_fallback', 'en');
	}

	/**
	 * Load a language file
	 *
	 * @param   string
	 * @param   string|null  name of the group to load to, null for global
	 */
	public static function load($file, $group = null, $language = null)
	{
		$languages = static::$fallback;
		array_push($languages, $language ?: \Config::get('language'));

		$lines = array();
		foreach ($languages as $lang)
		{
			if ($path = \Fuel::find_file('lang/'.$lang, $file, '.php', true))
			{
				foreach ($path as $p)
				{
					$lines = $lines + \Fuel::load($p);
				}
				break;
			}
		}

		if ($group === null)
		{
			static::$lines = static::$lines + $lines;
		}
		else
		{
			$group = ($group === true) ? $file : $group;
			if ( ! isset(static::$lines[$group]))
			{
				static::$lines[$group] = array();
			}
			static::$lines[$group] = static::$lines[$group] + $lines;
		}
	}

	/**
	 * Fetch a line from the language
	 *
	 * @param   string  key for the line
	 * @param   array   array of params to str_replace
	 * @return  bool|string  either the line or false when not found
	 */
	public static function line($line, array $params = array())
	{
		if (strpos($line, '.') !== false)
		{
			$parts = explode('.', $line);

			$return = false;
			foreach ($parts as $part)
			{
				if ($return === false and isset(static::$lines[$part]))
				{
					$return = static::$lines[$part];
				}
				elseif (isset($return[$part]))
				{
					$return = $return[$part];
				}
				else
				{
					return false;
				}
			}
			return  static::_parse_params($return, $params);
		}

		isset(static::$lines[$line]) and $line = static::$lines[$line];

		return static::_parse_params($line, $params);
	}

	/**
	 * Set or replace a line in the language
	 *
	 * @param   string  key to the line
	 * @param   string  value for the key
	 * @param   string  group
	 * @return  bool    success, fails on non-existing group
	 */
	public static function set($line, $value, $group = null)
	{
		$value = ($value instanceof \Closure) ? $value() : $value;

		if ($group === null)
		{
			static::$lines[$line] = $value;
			return true;
		}
		elseif (isset(static::$lines[$group][$line]))
		{
			static::$lines[$group][$line] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Parse the params in the language line
	 *
	 * @param   string  language line to parse
	 * @param   array   params to str_replace
	 * @return  string
	 */
	protected static function _parse_params($string, $array = array())
	{
		if (is_string($string))
		{
			$tr_arr = array();

			foreach ($array as $from => $to)
			{
				$tr_arr[':'.$from] = $to;
			}
			unset($array);

			return strtr($string, $tr_arr);
		}
		else
		{
			return $string;
		}
	}
}


