<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

/**
 * DB lang data parser
 */
class Lang_Db implements Lang_Interface
{
	protected $identifier;

	protected $ext = '.db';

	protected $languages = array();

	protected $vars = array();

	protected $database;

	protected $table;

	/**
	 * Sets up the file to be parsed and variables
	 *
	 * @param   string  $identifier  Lang identifier name
	 * @param   array   $languages  Languages to scan for the lang file
	 * @param   array   $vars  Variables to parse in the data retrieved
	 */
	public function __construct($identifier = null, $languages = array(), $vars = array())
	{
		$this->identifier = $identifier;

		// we need the highest priority language last in the list
		$this->languages = array_reverse($languages);

		$this->vars = array(
			'APPPATH' => APPPATH,
			'COREPATH' => COREPATH,
			'PKGPATH' => PKGPATH,
			'DOCROOT' => DOCROOT,
		) + $vars;

		$this->database = \Config::get('lang.database', null);
		$this->table = \Config::get('lang.table_name', 'lang');
	}

	/**
	 * Loads the language file(s).
	 *
	 * @param   bool  $overwrite  Whether to overwrite existing values
	 * @return  array  the language array
	 * @throws  \Database_Exception
	 */
	public function load($overwrite = false)
	{
		$lang = array();

		foreach ($this->languages as $language)
		{
			// try to retrieve the config from the database
			try
			{
				$result = \DB::select('lang')->from($this->table)->where('identifier', '=', $this->identifier)->where('language', '=', $language)->execute($this->database);
			}
			catch (Database_Exception $e)
			{
				// strip the actual query from the message
				$msg = $e->getMessage();
				$msg = substr($msg, 0, strlen($msg)  - strlen(strrchr($msg, ':')));

				// and rethrow it
				throw new \Database_Exception($msg, $e->getCode(), $e, $e->getDbCode());
			}

			// did we succeed?
			if ($result->count())
			{
				if ( ! empty($result[0]['lang']))
				{
					$lang = $overwrite ?
						array_merge($lang, unserialize($this->parse_vars($result[0]['lang']))) :
						\Arr::merge($lang, unserialize($this->parse_vars($result[0]['lang'])));
				}
			}
		}

		return $lang;
	}

	/**
	 * Gets the default group name.
	 *
	 * @return  string
	 */
	public function group()
	{
		return $this->identifier;
	}

	/**
	 * Parses a string using all of the previously set variables.  Allows you to
	 * use something like %APPPATH% in non-PHP files.
	 *
	 * @param   string  $string  String to parse
	 * @return  string
	 */
	protected function parse_vars($string)
	{
		foreach ($this->vars as $var => $val)
		{
			$string = str_replace("%$var%", $val, $string);
		}

		return $string;
	}

	/**
	 * Replaces FuelPHP's path constants to their string counterparts.
	 *
	 * @param   array  $array  array to be prepped
	 * @return  array  prepped array
	 */
	protected function prep_vars(&$array)
	{
		static $replacements = false;

		if ($replacements === false)
		{
			foreach ($this->vars as $i => $v)
			{
				$replacements['#^('.preg_quote($v).'){1}(.*)?#'] = "%".$i."%$2";
			}
		}

		foreach ($array as $i => $value)
		{
			if (is_string($value))
			{
				$array[$i] = preg_replace(array_keys($replacements), array_values($replacements), $value);
			}
			elseif(is_array($value))
			{
				$this->prep_vars($array[$i]);
			}
		}
	}

	/**
	 * Formats the output and saved it to the database.
	 *
	 * @param   string     $identifier  filename
	 * @param   $contents  $contents    language array to save
	 * @return  bool       DB result
	 */
	public function save($identifier, $contents)
	{
		// get the language and the identifier
		list($language, $identifier) = explode(DS, $identifier, 2);
		$identifier = basename($identifier, '.db');

		// prep the contents
		$this->prep_vars($contents);
		$contents = serialize($contents);

		// update the config in the database
		$result = \DB::update($this->table)->set(array('lang' => $contents, 'hash' => uniqid()))->where('identifier', '=', $identifier)->where('language', '=', $language)->execute($this->database);

		// if there wasn't an update, do an insert
		if ($result === 0)
		{
			list($notused, $result) = \DB::insert($this->table)->set(array('identifier' => $identifier, 'language' => $language, 'lang' => $contents, 'hash' => uniqid()))->execute($this->database);
		}

		return $result === 1;
	}
}
