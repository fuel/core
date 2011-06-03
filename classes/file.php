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


class FileAccessException extends Fuel_Exception {}
class OutsideAreaException extends \OutOfBoundsException {}
class InvalidPathException extends FileAccessException {}

// ------------------------------------------------------------------------

/**
 * File Class
 *
 * @package		Fuel
 * @subpackage	Core
 * @category	Core
 * @author		Jelmer Schreuder
 */
class File {

	/**
	 * @var	File_Area	points to the base area
	 */
	protected static $base_area = null;

	/**
	 * @var	array	loaded area's
	 */
	protected static $areas = array();

	public static function _init()
	{
		\Config::load('file', true);
	
		static::$base_area = \File_Area::factory(\Config::get('file.base_config', array()));
		foreach (\Config::get('file.areas', array()) as $name => $config)
		{
			static::$areas[$name] = \File_Area::factory($config) + static::$base_area;
		}
	}

	public static function factory(Array $config = array())
	{
		return \File_Area::factory($config);
	}

	/**
	 * Instance
	 *
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	File_Area
	 */
	public static function instance($area = null)
	{
		if ($area instanceof File_Area)
		{
			return $area;
		}
		elseif ($area === null)
		{
			return static::$base_area;
		}

		return array_key_exists($area, static::$areas) ? static::$areas[$area] : false;
	}

	/**
	 * File & directory objects factory
	 *
	 * @param	string					path to the file or directory
	 * @param	array					configuration items
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	File_Handler_File
	 */
	public static function get($path, Array $config = array(), $area = null)
	{
		return static::instance($area)->get_handler($path, $config);
	}
	
	/**
	 * Get the url.
	 *
	 * @return	bool
	 */
	public static function get_url($path, Array $config = array(), $area = null)
	{
		return static::get($path, $config, $area)->get_url();
	}

	/**
	 * Create an empty file
	 *
	 * @param	string					directory where to create file
	 * @param	string					filename
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	bool
	 */
	public static function create($basepath, $name, $contents = null, $area = null)
	{
		$basepath	= rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_file	= static::instance($area)->get_path($basepath.$name);

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new \InvalidPathException('Invalid basepath, cannot create file at this location.');
		}
		elseif (file_exists($new_file))
		{
			throw new \FileAccessException('File exists already, cannot be created.');
		}

		$file = static::open_file(@fopen($new_file, 'c'), true, $area);
		fwrite($file, $contents);
		static::close_file($file, $area);

		return true;
	}

	/**
	 * Create an empty directory
	 *
	 * @param	string					directory where to create new dir
	 * @param	string					dirname
	 * @param	int						(octal) file permissions
	 * @param	string|File_Area|null	file area name, object or null for non-specific
	 * @return	bool
	 */
	public static function create_dir($basepath, $name, $chmod = 0777, $area = null)
	{
		$basepath	= rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_dir	= static::instance($area)->get_path($basepath.$name);

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new \InvalidPathException('Invalid basepath, cannot create directory at this location.');
		}
		elseif (file_exists($new_dir))
		{
			throw new \FileAccessException('Directory exists already, cannot be created.');
		}

		$recursive = (strpos($name, '/') !== false or strpos($name, '\\') !== false);

		return mkdir($new_dir, $chmod, $recursive);
	}

	/**
	 * Read file
	 *
	 * @param	string					file to read
	 * @param	bool					whether to use readfile() or file_get_contents()
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	IO|string				file contents
	 */
	public static function read($path, $as_string = false, $area = null)
	{
		$path = static::instance($area)->get_path($path);
		
		if( ! file_exists($path) or ! is_file($path))
		{
			throw new \InvalidPathException('Cannot read file, file does not exists.');
		}

		$file = static::open_file(@fopen($path, 'r'), LOCK_SH, $area);
		$return = $as_string ? file_get_contents($path) : readfile($path);
		static::close_file($file, $area);

		return $return;
	}

	/**
	 * Read directory
	 * (based on CodeIgniter's directory_map())
	 *
	 * @param	string					directory to read
	 * @param	int						depth to recurse directory, 1 is only current and 0 or smaller is unlimited
	 * @param	array|null				array of partial regexes or non-array for default
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	array					directory contents in an array
	 */
	public static function read_dir($path, $depth = 0, $filter = null, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/').DS;

		if ( ! is_dir($path))
		{
			throw new \InvalidPathException('Invalid path, directory cannot be read.');
		}

		if ( ! $fp = @opendir($path))
		{
			throw new \FileAccessException('Could not open directory for reading.');
		}

		// Use default when not set
		if ( ! is_array($filter))
		{
			$filter = array('!^\.');
			if ($extensions = static::instance($area)->extensions())
			{
				foreach($extensions as $ext)
				{
					$filter[] = '\.'.$ext.'$';
				}
			}
		}

		$files		= array();
		$dirs		= array();
		$new_depth	= $depth - 1;

		while (false !== ($file = readdir($fp)))
		{
			// Remove '.', '..'
			if (in_array($file, array('.', '..')))
			{
				continue;
			}
			// use filters when given
			elseif ( ! empty($filter))
			{
				$continue = false; // whether or not to continue
				$matched  = false; // whether any positive pattern matched
				$positive = false; // whether positive filters are present
				foreach($filter as $f)
				{
					$not = substr($f, 0, 1) == '!'; // whether it's a negative condition
					$f = $not ? substr($f, 1) : $f;
					// on negative condition a match leads to a continue
					if (($match = preg_match('/'.$f.'/uiD', $file) > 0) and $not)
					{
						$continue = true;
					}

					$positive = $positive ?: ! $not;			// whether a positive condition was encountered
					$matched  = $matched ?: ($match and ! $not);	// whether one of the filters has matched
				}

				// continue when negative matched or when positive filters and nothing matched
				if ($continue or $positive and ! $matched)
				{
					continue;
				}
			}

			if (@is_dir($path.$file))
			{
				// Use recursion when depth not depleted or not limited...
				if ($depth < 1 or $new_depth > 0)
				{
					$dirs[$file] = static::read_dir($path.$file.DS, $new_depth, $filter, $area);
				}
				// ... or set dir to false when not read
				else
				{
					$dirs[$file] = false;
				}
			}
			else
			{
				$files[] = $file;
			}
		}

		closedir($fp);

		// sort dirs & files naturally and return array with dirs on top and files
		uksort($dirs, 'strnatcasecmp');
		natcasesort($files);
		return array_merge($dirs, $files);
	}

	/**
	 * Update a file
	 *
	 * @param	string					directory where to write the file
	 * @param	string					filename
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	bool
	 */
	public static function update($basepath, $name, $contents = null, $area = null)
	{
		$basepath	= rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_file	= static::instance($area)->get_path($basepath.$name);

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new \InvalidPathException('Invalid basepath, cannot update a file at this location.');
		}

		if ( ! $file = static::open_file(@fopen($new_file, 'w'), true, $area) )
		{
			throw new \FileAccessException('No write access, cannot update a file.');
		}
		fwrite($file, $contents);
		static::close_file($file, $area);

		return true;
	}

	/**
	 * Append to a file
	 *
	 * @param	string					directory where to write the file
	 * @param	string					filename
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	bool
	 */
	public static function append($basepath, $name, $contents = null, $area = null)
	{
		$basepath	= rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_file	= static::instance($area)->get_path($basepath.$name);

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new \InvalidPathException('Invalid basepath, cannot append to a file at this location.');
		}
		elseif ( ! file_exists($new_file))
		{
			throw new \FileAccessException('File does not exist, cannot be appended.');
		}

		$file = static::open_file(@fopen($new_file, 'a'), true, $area);
		fwrite($file, $contents);
		static::close_file($file, $area);

		return true;
	}
	
	/**
	 * Get the octal permissions for a file or directory
	 *
	 * @param	string	$path	path to the file or directory
	 * @param	mixed	$area	file area name, object or null for base area
	 * $return	string	octal file permissions
	 */
	public static function get_permissions($path, $area = null)
	{
		$path = static::instance($area)->get_path($path);
		
		if ( ! file_exists($path))
		{
			throw new \InvalidPathException('Path is not a directory or a file, cannot get permissions.');
		}
		
		return substr(sprintf('%o', fileperms($path)), -4);

	}
	
	/**
	 * Get a file's or directory's created or modified timestamp.
	 *
	 * @param	string	$path	path to the file or directory
	 * @param	string	$type	modified or created
	 * @param	mixed	$area	file area name, object or null for base area
	 * @return	int		Unix Timestamp
	 */
	public static function get_time($path, $type = 'modified', $area = null)
	{
		$path = static::instance($area)->get_path($path);
		
		if ( ! file_exists($path))
		{
			throw new \InvalidPathException('Path is not a directory or a file, cannot get creation timestamp.');
		}
		
		if($type === 'modified')
		{
			return filemtime($path);
		}
		elseif($type === 'created')
		{
			return filectime($path);
		}
		else
		{
			throw new \UnexpectedValueException('File::time $type must be "modified" or "created".');
		}
	}
	
	/**
	 * Get a file's size.
	 *
	 * @param	string	$path	path to the file or directory
	 * @param	mixed	$area	file area name, object or null for base area
	 * @return	int		the file's size in bytes
	 */
	public static function get_size($path, $area = null)
	{
		$path = static::instance($area)->get_path($path);
		
		if ( ! file_exists($path))
		{
			throw new \InvalidPathException('Path is not a directory or a file, cannot get size.');
		}
		
		return filesize($path);
	}

	/**
	 * Rename directory or file
	 *
	 * @param	string					path to file or directory to rename
	 * @param	string					new path (full path, can also cause move)
	 * @param	string|File_Area|null	file area name, object or null for non-specific
	 * @return	bool
	 */
	public static function rename($path, $new_path, $area = null)
	{
		$path = static::instance($area)->get_path($path);
		$new_path = static::instance($area)->get_path($new_path);

		return rename($path, $new_path);
	}

	/**
	 * Alias for rename(), not needed but consistent with other methods
	 */
	public static function rename_dir($path, $new_path, $area = null)
	{
		return static::rename($path, $new_path, $area);
	}

	/**
	 * Copy file
	 *
	 * @param	string					path to file to copy
	 * @param	string					new base directory (full path)
	 * @param	string|File_Area|null	file area name, object or null for non-specific
	 * @return	bool
	 */
	public static function copy($path, $new_path, $area = null)
	{
		$path = static::instance($area)->get_path($path);
		$new_path = static::instance($area)->get_path($new_path);

		if ( ! is_file($path))
		{
			throw new \InvalidPathException('Cannot copy file: given path is not a file.');
		}
		elseif (file_exists($new_path))
		{
			throw new \FileAccessException('Cannot copy file: new path already exists.');
		}
		$return = copy($path, $new_path);

		return $return;
	}

	/**
	 * Copy directory
	 *
	 * @param	string					path to directory to copy
	 * @param	string					new base directory (full path)
	 * @param	string|File_Area|null	file area name, object or null for non-specific
	 * @return	bool
	 * @throws	FileAccessException			when something went wrong
	 */
	public static function copy_dir($path, $new_path, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/').DS;
		$new_path = rtrim(static::instance($area)->get_path($new_path), '\\/').DS;

		if ( ! is_dir($path))
		{
			throw new \InvalidPathException('Cannot copy directory: given path is not a directory.');
		}
		elseif (file_exists($new_path))
		{
			throw new \FileAccessException('Cannot copy directory: new path already exists.');
		}

		$files = static::read_dir($path, -1, array(), $area);
		foreach ($files as $file)
		{
			if (is_array($file))
			{
				$check = static::create_dir($new_path.path.DS, $file, fileperms($path.$file.DS) ?: 0777, $area);
				$check and static::copy_dir($path.$file.DS, $new_path.$file.DS, $area);
			}
			else
			{
				$check = static::copy($path.$file, $new_path.$file, $area);
			}

			// abort if something went wrong
			if ($check)
			{
				throw new \FileAccessException('Directory copy aborted prematurely, part of the operation failed.');
			}
		}
	}

	/**
	 * Delete file
	 *
	 * @param	string					path to file to delete
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	bool
	 */
	public static function delete($path, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/');

		if ( ! is_file($path))
		{
			throw new \InvalidPathException('Cannot delete file: given path "'.$path.'" is not a file.');
		}

		return unlink($path);
	}

	/**
	 * Delete directory
	 *
	 * @param	string					path to directory to delete
	 * @param	bool					whether to also delete contents of subdirectories
	 * @param	bool					whether to delete the parent dir itself when empty
	 * @param	string|File_Area|null	file area name, object or null for base area
	 * @return	bool
	 */
	public static function delete_dir($path, $recursive = true, $delete_top = true, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/').DS;
		if ( ! is_dir($path))
		{
			throw new \InvalidPathException('Cannot delete directory: given path is not a directory.');
		}

		$files = static::read_dir($path, -1, array(), $area);

		$not_empty = false;
		$check = true;
		foreach ($files as $dir => $file)
		{
			if (is_array($file))
			{
				if ($recursive)
				{
					$check = static::delete_dir($path.$dir, $area);
				}
				else
				{
					$not_empty = true;
				}
			}
			else
			{
				$check = static::delete($path.$file, $area);
			}

			// abort if something went wrong
			if ( ! $check)
			{
				throw new \FileAccessException('Directory deletion aborted prematurely, part of the operation failed.');
			}
		}

		if ( ! $not_empty and $delete_top)
		{
			return rmdir($path);
		}
		return true;
	}

	/**
	 * Open and lock file
	 *
	 * @param	resource|string			file resource or path
	 * @param	constant				either valid lock constant or true=LOCK_EX / false=LOCK_UN
	 * @param	string|File_Area|null	file area name, object or null for base area
	 */
	public static function open_file($path, $lock = true, $area = null)
	{
		if (is_string($path))
		{
			$path = static::instance($area)->get_path($path);
			$resource = fopen($path, 'r+');
		}
		else
		{
			$resource = $path;
		}

		// Make sure the parameter is a valid resource
		if ( ! is_resource($resource))
		{
			return false;
		}

		// If locks aren't used, don't lock
		if ( ! static::instance($area)->use_locks())
		{
			return $resource;
		}

		// Accept valid lock constant or set to LOCK_EX
		$lock = in_array($lock, array(LOCK_SH, LOCK_EX, LOCK_NB)) ? $lock : LOCK_EX;

		// Try to get a lock, timeout after 5 seconds
		$lock_mtime = microtime(true);
		while ( ! flock($resource, $lock))
		{
			if (microtime(true) - $lock_mtime > 5)
			{
				throw new \FileAccessException('Could not secure file lock, timed out after 5 seconds.');
			}
		}

		return $resource;
	}

	/**
	 * Close file resource & unlock
	 *
	 * @param	resource				open file resource
	 * @param	string|File_Area|null	file area name, object or null for base area
	 */
	public static function close_file($resource, $area = null)
	{
		fclose($resource);

		// If locks aren't used, don't unlock
		if ( ! static::instance($area)->use_locks())
		{
			return;
		}

		flock($resource, LOCK_UN);
	}
}

/* End of file file.php */
