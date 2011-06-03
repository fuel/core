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



class File_Area {

	/**
	 * @var	string	path to basedir restriction, null for no restriction
	 */
	protected $basedir = null;

	/**
	 * @var	array	array of allowed extensions, null for all
	 */
	protected $extensions = null;

	/**
	 * @var	string	base url for files, null for not available
	 */
	protected $url = null;

	/**
	 * @var	bool	whether or not to use file locks when doing file operations
	 */
	protected $use_locks = false;

	/**
	 * @var	array	contains file handler per file extension
	 */
	protected $file_handlers = array();

	protected function __construct(Array $config = array())
	{
		foreach ($config as $key => $value)
		{
			if (property_exists($this, $key))
			{
				$this->{$key} = $value;
			}
		}

		if ( ! empty($this->basedir))
		{
			$this->basedir = realpath($this->basedir) ?: $this->basedir;
		}
	}

	/**
	 * Factory for area objects
	 *
	 * @param	array
	 * @return	File_Area
	 */
	public static function factory(Array $config = array())
	{
		return new static($config);
	}

	/**
	 * Handler factory for given path
	 *
	 * @param	string				path to file or directory
	 * @param	array				optional config
	 * @return	File_Handler_File
	 * @throws	FileAccessException		when outside basedir restriction or disallowed file extension
	 */
	public function get_handler($path, Array $config = array(), $content = array())
	{
		$path = $this->get_path($path);

		if (is_file($path))
		{
			$info = pathinfo($path);

			// check file extension
			$info = pathinfo($path);
			if ( ! empty($this->extensions) && ! in_array($info['extension'], $this->extensions))
			{
				throw new \FileAccessException('File operation not allowed: disallowed file extension.');
			}

			// create specific handler when available
			if (array_key_exists($info['extension'], $this->file_handlers))
			{
				$class = '\\'.ltrim($this->file_handlers[$info['extension']], '\\');
				return $class::factory($path, $config, $this);
			}

			return \File_Handler_File::factory($path, $config, $this);
		}
		elseif (is_dir($path))
		{
			return \File_Handler_Directory::factory($path, $config, $this, $content);
		}

		// still here? path is invalid
		throw new \FileAccessException('Invalid path for file or directory.');
	}

	/**
	 * Does this area use file locks?
	 *
	 * @return	bool
	 */
	public function use_locks()
	{
		return $this->use_locks;
	}

	/**
	 * Are the shown extensions limited, and if so to which?
	 *
	 * @return	array
	 */
	public function extensions()
	{
		return $this->extensions;
	}

	/**
	 * Translate relative path to real path, throws error when operation is not allowed
	 *
	 * @param	string
	 * @return	string
	 * @throws	FileAccessException	when outside basedir restriction or disallowed file extension
	 */
	public function get_path($path)
	{
		// attempt to get the realpath(), otherwise just use path with any double dots taken out when basedir is set (for security)
		$path = ( ! empty($this->basedir) ? realpath($this->basedir.DS.$path) : realpath($path) )
				?: ( ! empty($this->basedir) ? $this->basedir.DS.str_replace('..', '', $path) : $path);

		// basedir prefix is required when it is set (may cause unexpected errors when realpath doesn't work)
		if ( ! empty($this->basedir) && substr($path, 0, strlen($this->basedir)) != $this->basedir)
		{
			throw new \OutsideAreaException('File operation not allowed: given path is outside the basedir for this area.');
		}

		// check file extension
		$info = pathinfo($path);
		if ( ! empty(static::$extensions) && array_key_exists($info['extension'], static::$extensions))
		{
			throw new \FileAccessException('File operation not allowed: disallowed file extension.');
		}

		return $path;
	}
	
	/**
	 * Translate relative path to accessible path, throws error when operation is not allowed
	 *
	 * @param	string
	 * @return	string
	 * @throws	LogicException	when no url is set or no basedir is set and file is outside DOCROOT
	 */
	public function get_url($path)
	{
		if(empty($this->url))
		{
			throw new \LogicException('File operation now allowed: cannot create a file url without an area url.');
		}
		
		$path = $this->get_path($path);
		
		$basedir = $this->basedir;
		empty($basedir) and $basedir = DOCROOT;
		
		if(stripos($path, $basedir) !== 0)
		{
			throw new \LogicException('File operation not allowed: cannot create file url whithout a basedir and file outside DOCROOT.');
		}

		return rtrim($this->url, '/').'/'.ltrim(substr($path, strlen($basedir)),'/');
	}

	/* -------------------------------------------------------------------------------------
	 * Allow all File methods to be used from an area directly
	 * ------------------------------------------------------------------------------------- */

	public function create($basepath, $name, $contents = null)
	{
		return \File::create($basepath, $name, $contents, $this);
	}

	public function create_dir($basepath, $name, $chmod = 0777)
	{
		return \File::create_dir($basepath, $name, $chmod, $this);
	}

	public function read($path, $as_string = false)
	{
		return \File::read($path, $as_string, $this);
	}

	public function read_dir($path, $depth = 0, $filter = null)
	{
		$content = \File::read_dir($path, $depth, $filter, $this);
		return $this->get_handler($path, array(), $content);
	}

	public function rename($path, $new_path)
	{
		return \File::rename($path, $new_path, $this);
	}

	public function rename_dir($path, $new_path)
	{
		return \File::rename_dir($path, $new_path, $this);
	}

	public function copy($path, $new_path)
	{
		return \File::copy($path, $new_path, $this);
	}

	public function copy_dir($path, $new_path)
	{
		return \File::copy_dir($path, $new_path, $this);
	}

	public function delete($path)
	{
		return \File::delete($path, $this);
	}

	public function delete_dir($path, $recursive = true, $delete_top = true)
	{
		return \File::delete($path, $recursive, $delete_top, $this);
	}
	
	public function update($basepath, $name, $new_content)
	{
		return \File::update($basepath, $name, $new_content, $this);
	}
	
	public function get_permissions($path)
	{
		return \File::get_permissions($path, $this);
	}
	
	public function get_time($path, $type)
	{
		return \File::get_time($path, $type, $this);
	}
	
	public function get_size($path)
	{
		return \File::get_size($path, $this);
	}
}

/* End of file area.php */
