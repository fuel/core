<?php

namespace Fuel\Core;

/**
 * PHP Lang file parser
 */
class Lang_Php extends \Lang_File
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
	 * Formats the output and saved it to disc.
	 *
	 * @param   string     $identifier  filename
	 * @param   $contents  $contents    language array to save
	 * @return  bool       \File::update result
	 */
	public function save($identifier, $contents)
	{
		// store the current filename
        $file = $this->file;

        // save it
        $return = parent::save($identifier, $contents);

        // existing file? saved? and do we need to flush the opcode cache?
        if ($file == $this->file and $return and static::$flush_needed)
        {
			if ($this->file[0] !== '/' and ( ! isset($this->file[1]) or $this->file[1] !== ':'))
			{
				// locate the file
				if ($pos = strripos($identifier, '::'))
				{
					// get the namespace path
					if ($file = \Autoloader::namespace_path('\\'.ucfirst(substr($identifier, 0, $pos))))
					{
						// strip the namespace from the filename
						$identifier = substr($identifier, $pos+2);

						// strip the classes directory as we need the module root
						$file = substr($file,0, -8).'lang'.DS.$identifier;
					}
					else
					{
						// invalid namespace requested
						return false;
					}
				}
				else
				{
					$file = \Finder::search('lang', $identifier);
				}
			}

			// make sure we have a fallback
			$file or $file = APPPATH.'lang'.DS.$identifier;

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
	 * Returns the formatted language file contents.
	 *
	 * @param   array   $content  config array
	 * @return  string  formatted config file contents
	 */
	protected function export_format($contents)
	{
		$output = <<<CONF
<?php

CONF;
		$output .= 'return '.str_replace(array('  ', 'array (', '\''.APPPATH, '\''.DOCROOT, '\''.COREPATH, '\''.PKGPATH), array("\t", 'array(', 'APPPATH.\'', 'DOCROOT.\'', 'COREPATH.\'', 'PKGPATH.\''), var_export($contents, true)).";\n";
		return $output;
	}
}
