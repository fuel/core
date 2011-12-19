<?php
/**
 * Part of the Fuel framework.
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
 * @link		http://docs.fuelphp.com/classes/lang.html
 */
class Lang
{

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
	 * @param   string|null  name of the language to load, null for the configurated language
	 */
	public static function load($file, $group = null, $language = null)
	{
		$languages = static::$fallback;
		array_unshift($languages, $language ?: \Config::get('language'));

		$lines = array();
		foreach ($languages as $lang)
		{
			if ($path = \Finder::search('lang/'.$lang, $file, '.php', true))
			{
				foreach ($path as $p)
				{
					$lines = \Arr::merge(\Fuel::load($p), $lines);
				}
				break;
			}
		}

		if ($group === null)
		{
			static::$lines = \Arr::merge($lines, static::$lines);
		}
		else
		{
			$group = ($group === true) ? $file : $group;
			if ( ! isset(static::$lines[$group]))
			{
				static::$lines[$group] = array();
			}
			static::$lines[$group] = \Arr::merge($lines, static::$lines[$group]);
		}
	}

	/**
	 * Get a line from the language
	 *
	 * @param   string  key for the line
	 * @param   array   array of params to str_replace
	 * @param   mixed   default value to return
	 * @return  bool|string  either the line or false when not found
	 */
	public static function get($line, array $params = array(), $default = null)
	{
		return \Str::tr(\Arr::get(static::$lines, $line, $default), $params);
	}

	/**
	 * Fetch a line from the language
	 *
	 * @param   string  key for the line
	 * @param   array   array of params to str_replace
	 * @return  bool|string  either the line or false when not found
	 * @depricated  Remove in v1.2
	 */
	public static function line($line, array $params = array())
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated. Please use Lang::get() instead.', __METHOD__);
		return \Str::tr(\Arr::get(static::$lines, $line, false), $params);
	}

	/**
	 * Set or replace a line in the language
	 *
	 * @param   string  key to the line
	 * @param   string  value for the key
	 * @param   string  group
	 * @return  void
	 */
	public static function set($line, $value, $group = null)
	{
		$key = ($group ? $group.'.' : '').$line;
		\Arr::set(static::$lines, $key, $value);
	}

}


