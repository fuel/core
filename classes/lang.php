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

class LangException extends \FuelException { }

class Lang
{
	/**
	 * @var    array    $loaded_files    array of loaded files
	 */
	public static $loaded_files = array();

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
	 * Loads a language file.
	 *
	 * @param    mixed        $file        string file | language array | Lang_Interface instance
	 * @param    mixed       $group        null for no group, true for group is filename, false for not storing in the master lang
	 * @param    string|null $language     name of the language to load, null for the configurated language
	 * @param    bool        $overwrite    true for array_merge, false for \Arr::merge
	 * @return   array                     the (loaded) language array
	 */
	public static function load($file, $group = null, $language = null, $overwrite = false)
	{
		$languages = static::$fallback;
		array_unshift($languages, $language ?: \Config::get('language'));

		if ( ! is_array($file) and
		     ! is_object($file) and
		    array_key_exists($file, static::$loaded_files))
		{
			return false;
		}

		$lang = array();
		if (is_array($file))
		{
			$lang = $file;
		}
		elseif (is_string($file))
		{
			$info = pathinfo($file);
			$type = 'php';
			if (isset($info['extension']))
			{
				$type = $info['extension'];
				// Keep extension when it's an absolute path, because the finder won't add it
				if ($file[0] !== '/' and $file[1] !== ':')
				{
					$file = substr($file, 0, -(strlen($type) + 1));
				}
			}
			$class = '\\Lang_'.ucfirst($type);

			if (class_exists($class))
			{
				static::$loaded_files[$file] = true;
				$file = new $class($file, $languages);
			}
			else
			{
				throw new \FuelException(sprintf('Invalid lang type "%s".', $type));
			}
		}

		if ($file instanceof Lang_Interface)
		{
			try
			{
				$lang = $file->load($overwrite);
			}
			catch (\LangException $e)
			{
				$lang = array();
			}
			$group = $group === true ? $file->group() : $group;
		}

		if ($group === null)
		{
			static::$lines = $overwrite ? array_merge(static::$lines, $lang) : \Arr::merge(static::$lines, $lang);
		}
		else
		{
			$group = ($group === true) ? $file : $group;
			if ( ! isset(static::$lines[$group]))
			{
				static::$lines[$group] = array();
			}
			static::$lines[$group] = $overwrite ? array_merge(static::$lines[$group],$lang) : \Arr::merge(static::$lines[$group],$lang);
		}

		return $lang;
	}

	/**
	 * Save a language array to disk.
	 *
	 * @param   string          $file      desired file name
	 * @param   string|array    $lang    master language array key or language array
	 * @return  bool                       false when language is empty or invalid else \File::update result
	 */
	public static function save($file, $lang, $language = null)
	{
		if ($language === null)
		{
			$languages = static::$fallback;
			array_unshift($languages, $language ?: \Config::get('language'));
			$language = reset($languages);
		}

		is_null($language) or $file = $language.DS.$file;

		if ( ! is_array($lang))
		{
			if ( ! isset(static::$lines[$lang]))
			{
				return false;
			}
			$lang = static::$lines[$lang];
		}

		$type = pathinfo($file, PATHINFO_EXTENSION);
		if( ! $type)
		{
			$type = 'php';
			$file .= '.'.$type;
		}

		$class = '\\Lang_'.ucfirst($type);

		if( ! class_exists($class, true))
		{
			throw new \LangException('Cannot save a language file of type: '.$type);
		}

		$driver = new $class;
		return $driver->save($file, $lang);
	}

	/**
	 * Returns a (dot notated) language string
	 *
	 * @param   string  key for the line
	 * @param   array   array of params to str_replace
	 * @param   mixed   default value to return
	 * @return  mixed   either the line or default when not found
	 */
	public static function get($line, array $params = array(), $default = null)
	{
		return \Str::tr(\Fuel::value(\Arr::get(static::$lines, $line, $default)), $params);
	}

	/**
	 * Sets a (dot notated) language string
	 *
	 * @param    string    a (dot notated) language key
	 * @param    mixed     the language string
	 * @param    string    group
	 * @return   void      the \Arr::set result
	 */
	public static function set($line, $value, $group = null)
	{
		$group === null or $line = $group.'.'.$line;

		return \Arr::set(static::$lines, $line, \Fuel::value($value));
	}

	/**
	 * Deletes a (dot notated) language string
	 *
	 * @param    string       a (dot notated) language key
	 * @param    string       group
	 * @return   array|bool   the \Arr::delete result, success boolean or array of success booleans
	 */
	public static function delete($item, $group = null)
	{
		$group === null or $line = $group.'.'.$line;

		return \Arr::delete(static::$lines, $item);
	}
}
