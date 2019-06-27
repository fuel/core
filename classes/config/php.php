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
 * PHP Config file parser
 */
class Config_Php extends \Config_File
{
	/**
	 * @var  bool  whether or not opcache is in use
	 */
    protected static $uses_opcache = false;

	/**
	 * @var  bool  whether or not APC is in use
	 */
    protected static $uses_apc = false;

	/**
	 * @var  bool  whether or not we need to flush the opcode cache after a save
	 */
    protected static $flush_needed = false;

	/**
	 * check the status of any opcache mechanism in use
	 */
	public static function _init()
	{
		// do we have Opcache active?
		static::$uses_opcache = (PHP_VERSION_ID >= 50500 and function_exists('opcache_invalidate'));

		// do we have APC active?
		static::$uses_apc = function_exists('apc_compile_file');

		// determine if we have an opcode cache active
		static::$flush_needed = static::$uses_opcache or static::$uses_apc;
	}

	/**
	 * @var  string  the extension used by this config file parser
	 */
	protected $ext = '.php';

	/**
	 * Formats the output and saved it to disk.
	 *
	 * @param   $contents  $contents    config array to save
	 * @return  bool       \File::update result
	 */
	public function save($contents)
	{
		// store the current filename
        $file = $this->file;

        // save it
        $return = parent::save($contents);

        // existing file? saved? and do we need to flush the opcode cache?
        if ($file == $this->file and $return and static::$flush_needed)
        {
			if ($this->file[0] !== '/' and ( ! isset($this->file[1]) or $this->file[1] !== ':'))
			{
				// locate the file
				$file = \Finder::search('config', $this->file, $this->ext);
			}

			// make sure we have a fallback
			$file or $file = APPPATH.'config'.DS.$this->file.$this->ext;

			// flush the opcode caches that are active
			static::$uses_opcache and opcache_invalidate($file, true);
			static::$uses_apc and apc_compile_file($file);
		}

		return $return;
	}

	/**
	 * Loads in the given file and parses it.
	 *
	 * @param   string  $file  File to load
	 * @return  array
	 */
	protected function load_file($file)
	{
		return \Fuel::load($file);
	}

	/**
	 * Returns the formatted config file contents.
	 *
	 * @param   array   $contents  config array
	 * @return  string  formatted config file contents
	 */
	protected function export_format($contents)
	{
		$output = <<<CONF
<?php

CONF;
		$output .= 'return '.str_replace(array('array ('.PHP_EOL, '\''.APPPATH, '\''.DOCROOT, '\''.COREPATH, '\''.PKGPATH), array('array('.PHP_EOL, 'APPPATH.\'', 'DOCROOT.\'', 'COREPATH.\'', 'PKGPATH.\''), var_export($contents, true)).";\n";
		return $output;
	}
}
