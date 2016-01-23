<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class FileAccessException extends \FuelException {}
class OutsideAreaException extends \OutOfBoundsException {}
class InvalidPathException extends \FileAccessException {}

// ------------------------------------------------------------------------

/**
 * File Class
 *
 * @package     Fuel
 * @subpackage  Core
 * @category    Core
 */
class File
{
	/**
	 * @var  array  loaded area's
	 */
	protected static $areas = array();

	public static function _init()
	{
		\Config::load('file', true);

		// make sure the configured chmod values are octal
		$chmod = \Config::get('file.chmod.folders', 0777);
		is_string($chmod) and \Config::set('file.chmod.folders', octdec($chmod));
		$chmod = \Config::get('file.chmod.files', 0666);
		is_string($chmod) and \Config::set('file.chmod.files', octdec($chmod));

		static::$areas[null] = \File_Area::forge(\Config::get('file.base_config', array()));

		foreach (\Config::get('file.areas', array()) as $name => $config)
		{
			static::$areas[$name] = \File_Area::forge($config);
		}
	}

	public static function forge(array $config = array())
	{
		return \File_Area::forge($config);
	}

	/**
	 * Instance
	 *
	 * @param   string|File_Area|null  $area  file area name, object or null for base area
	 * @return  File_Area
	 */
	public static function instance($area = null)
	{
		if ($area instanceof File_Area)
		{
			return $area;
		}

		$instance = array_key_exists($area, static::$areas) ? static::$areas[$area] : false;

		if ($instance === false)
		{
			throw new \InvalidArgumentException('There is no file instance named "'.$area.'".');
		}

		return $instance;
	}

	/**
	 * File & directory objects factory
	 *
	 * @param   string                 $path    path to the file or directory
	 * @param   array                  $config  configuration items
	 * @param   string|File_Area|null  $area    file area name, object or null for base area
	 * @return  File_Handler_File
	 */
	public static function get($path, array $config = array(), $area = null)
	{
		return static::instance($area)->get_handler($path, $config);
	}

	/**
	 * Get the url.
	 *
	 * @param  string  $path
	 * @param  array   $config
	 * @param  null    $area
	 * @return bool
	 */
	public static function get_url($path, array $config = array(), $area = null)
	{
		return static::get($path, $config, $area)->get_url();
	}

	/**
	 * Check for file existence
	 *
	 * @param   string                 $path  path to file to check
	 * @param   string|File_Area|null  $area  file area name, object or null for base area
	 * @return  bool
	 */
	public static function exists($path, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/');

		// resolve symlinks
		while ($path and is_link($path))
		{
			$path = readlink($path);
		}

		return is_file($path);
	}

	/**
	 * Create a file
	 *
	 * @param  string                 $basepath  directory where to create file
	 * @param  string                 $name      filename
	 * @param  null                   $contents  contents of file
	 * @param  string|File_Area|null  $area      file area name, object or null for base area
	 * @return bool
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function create($basepath, $name, $contents = null, $area = null)
	{
		$basepath	= rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_file	= static::instance($area)->get_path($basepath.$name);

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new \InvalidPathException('Invalid basepath: "'.$basepath.'", cannot create file at this location.');
		}
		elseif (is_file($new_file))
		{
			throw new \FileAccessException('File: "'.$new_file.'" already exists, cannot be created.');
		}

		$file = static::open_file(@fopen($new_file, 'c'), true, $area);
		fwrite($file, $contents);
		static::close_file($file, $area);

		return true;
	}

	/**
	 * Create an empty directory
	 *
	 * @param  string                 directory where to create new dir
	 * @param  string                 dirname
	 * @param  int                    (octal) file permissions
	 * @param  string|File_Area|null  file area name, object or null for non-specific
	 * @return bool
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function create_dir($basepath, $name, $chmod = null, $area = null)
	{
		$basepath	= rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_dir	= static::instance($area)->get_path($basepath.trim($name, '\\/'));
		is_null($chmod) and $chmod = \Config::get('file.chmod.folders', 0777);

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new \InvalidPathException('Invalid basepath: "'.$basepath.'", cannot create directory at this location.');
		}
		elseif (is_dir($new_dir))
		{
			throw new \FileAccessException('Directory: "'.$new_dir.'" exists already, cannot be created.');
		}

		// unify the path separators, and get the part we need to add to the basepath
		$new_dir = str_replace(array('\\', '/'), DS, $new_dir);

		// recursively create the directory. we can't use mkdir permissions or recursive
		// due to the fact that mkdir is restricted by the current users umask
		$path = '';
		foreach (explode(DS, $new_dir) as $dir)
		{
			// some security checking
			if ($dir == '.' or $dir == '..')
			{
				throw new \FileAccessException('Directory to be created contains illegal segments.');
			}

			$path .= DS.$dir;
			if ( ! is_dir($path))
			{
				try
				{
					if ( ! mkdir($path))
					{
						return false;
					}
					chmod($path, $chmod);
				}
				catch (\PHPErrorException $e)
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Read file
	 *
	 * @param  string                 $path       file to read
	 * @param  bool                   $as_string  whether to use readfile() or file_get_contents()
	 * @param  string|File_Area|null  $area       file area name, object or null for base area
	 * @return IO|string  file contents
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function read($path, $as_string = false, $area = null)
	{
		$path = static::instance($area)->get_path($path);

		if ( ! is_file($path))
		{
			throw new \InvalidPathException('Cannot read file: "'.$path.'", file does not exists.');
		}

		$file = static::open_file(@fopen($path, 'r'), LOCK_SH, $area);
		$return = $as_string ? file_get_contents($path) : readfile($path);
		static::close_file($file, $area);

		return $return;
	}

	/**
	 * Read directory
	 *
	 * @param  string                 $path    directory to read
	 * @param  int                    $depth   depth to recurse directory, 1 is only current and 0 or smaller is unlimited
	 * @param  Array|null             $filter  array of partial regexps or non-array for default
	 * @param  string|File_Area|null  $area    file area name, object or null for base area
	 * @return array
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function read_dir($path, $depth = 0, $filter = null, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/').DS;

		if ( ! is_dir($path))
		{
			throw new \InvalidPathException('Invalid path: "'.$path.'", directory cannot be read.');
		}

		if ( ! $fp = @opendir($path))
		{
			throw new \FileAccessException('Could not open directory: "'.$path.'" for reading.');
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

		$files      = array();
		$dirs       = array();
		$new_depth  = $depth - 1;

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
				$continue = false;  // whether or not to continue
				$matched  = false;  // whether any positive pattern matched
				$positive = false;  // whether positive filters are present
				foreach($filter as $f => $type)
				{
					if (is_numeric($f))
					{
						// generic rule
						$f = $type;
					}
					else
					{
						// type specific rule
						$is_file = is_file($path.$file);
						if (($type === 'file' and ! $is_file) or ($type !== 'file' and $is_file))
						{
							continue;
						}
					}

					$not = substr($f, 0, 1) === '!';  // whether it's a negative condition
					$f = $not ? substr($f, 1) : $f;
					// on negative condition a match leads to a continue
					if (($match = preg_match('/'.$f.'/uiD', $file) > 0) and $not)
					{
						$continue = true;
					}

					$positive = $positive ?: ! $not;  // whether a positive condition was encountered
					$matched  = $matched ?: ($match and ! $not);  // whether one of the filters has matched
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
					$dirs[$file.DS] = static::read_dir($path.$file.DS, $new_depth, $filter, $area);
				}
				// ... or set dir to false when not read
				else
				{
					$dirs[$file.DS] = false;
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
	 * @param   string                 $basepath  directory where to write the file
	 * @param   string                 $name      filename
	 * @param   string                 $contents  contents of file
	 * @param   string|File_Area|null  $area      file area name, object or null for base area
	 * @return  bool
	 * @throws  \InvalidPathException
	 * @throws  \FileAccessException
	 * @throws  \OutsideAreaException
	 */
	public static function update($basepath, $name, $contents = null, $area = null)
	{
		$basepath  = rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_file  = static::instance($area)->get_path($basepath.$name);

		if ( ! $file = static::open_file(@fopen($new_file, 'w'), true, $area))
		{
			if ( ! is_dir($basepath) or ! is_writable($basepath))
			{
				throw new \InvalidPathException('Invalid basepath: "'.$basepath.'", cannot update a file at this location.');
			}

			throw new \FileAccessException('No write access to: "'.$basepath.'", cannot update a file.');
		}

		fwrite($file, $contents);
		static::close_file($file, $area);

		return true;
	}

	/**
	 * Append to a file
	 *
	 * @param   string                 $basepath  directory where to write the file
	 * @param   string                 $name      filename
	 * @param   string                 $contents  contents of file
	 * @param   string|File_Area|null  $area      file area name, object or null for base area
	 * @return  bool
	 * @throws  \InvalidPathException
	 * @throws  \FileAccessException
	 * @throws  \OutsideAreaException
	 */
	public static function append($basepath, $name, $contents = null, $area = null)
	{
		$basepath  = rtrim(static::instance($area)->get_path($basepath), '\\/').DS;
		$new_file  = static::instance($area)->get_path($basepath.$name);

		if ( ! is_file($new_file))
		{
			throw new \FileAccessException('File: "'.$new_file.'" does not exist, cannot be appended.');
		}

		if ( ! $file = static::open_file(@fopen($new_file, 'a'), true, $area))
		{
			if ( ! is_dir($basepath) or ! is_writable($basepath))
			{
				throw new \InvalidPathException('Invalid basepath: "'.$basepath.'", cannot append to a file at this location.');
			}

			throw new \FileAccessException('No write access, cannot append to the file: "'.$file.'".');
		}

		fwrite($file, $contents);
		static::close_file($file, $area);

		return true;
	}

	/**
	 * Get the octal permissions for a file or directory
	 *
	 * @param   string                 $path  path to the file or directory
	 * @param   string|File_Area|null  $area  file area name, object or null for base area
	 * @return  string  octal file permissions
	 * @throws  \FileAccessException
	 * @throws  \InvalidPathException
	 * @throws  \OutsideAreaException
	 */
	public static function get_permissions($path, $area = null)
	{
		$path = static::instance($area)->get_path($path);

		if ( ! file_exists($path))
		{
			throw new \InvalidPathException('Path: "'.$path.'" is not a directory or a file, cannot get permissions.');
		}

		return substr(sprintf('%o', fileperms($path)), -4);

	}

	/**
	 * Get a file's or directory's created or modified timestamp.
	 *
	 * @param   string                 $path  path to the file or directory
	 * @param   string                 $type  modified or created
	 * @param   string|File_Area|null  $area  file area name, object or null for base area
	 * @return  int  Unix Timestamp
	 * @throws  \FileAccessException
	 * @throws  \InvalidPathException
	 * @throws  \OutsideAreaException
	 */
	public static function get_time($path, $type = 'modified', $area = null)
	{
		$path = static::instance($area)->get_path($path);

		if ( ! file_exists($path))
		{
			throw new \InvalidPathException('Path: "'.$path.'" is not a directory or a file, cannot get creation timestamp.');
		}

		if ($type === 'modified')
		{
			return filemtime($path);
		}
		elseif ($type === 'created')
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
	 * @param   string  $path  path to the file or directory
	 * @param   mixed   $area  file area name, object or null for base area
	 * @return  int     the file's size in bytes
	 * @throws  \FileAccessException
	 * @throws  \InvalidPathException
	 * @throws  \OutsideAreaException
	 */
	public static function get_size($path, $area = null)
	{
		$path = static::instance($area)->get_path($path);

		if ( ! file_exists($path))
		{
			throw new \InvalidPathException('Path: "'.$path.'" is not a directory or a file, cannot get size.');
		}

		return filesize($path);
	}

	/**
	 * Rename directory or file
	 *
	 * @param   string                 $path         path to file or directory to rename
	 * @param   string                 $new_path     new path (full path, can also cause move)
	 * @param   string|File_Area|null  $source_area  source path file area name, object or null for non-specific
	 * @param   string|File_Area|null  $target_area  target path file area name, object or null for non-specific. Defaults to source_area if not set.
	 * @return  bool
	 * @throws  \FileAccessException
	 * @throws  \OutsideAreaException
	 */
	public static function rename($path, $new_path, $source_area = null, $target_area = null)
	{
		$path = static::instance($source_area)->get_path($path);
		$new_path = static::instance($target_area ?: $source_area)->get_path($new_path);

		return rename($path, $new_path);
	}

	/**
	 * Alias for rename(), not needed but consistent with other methods
	 *
	 * @param string                $path         path to directory to rename
	 * @param string                $new_path     new path (full path, can also cause move)
	 * @param string|File_Area|null $source_area  source path file area name, object or null for non-specific
	 * @param string|File_Area|null $target_area  target path file area name, object or null for non-specific. Defaults to source_area if not set.
	 * @return bool
	 * @throws  \FileAccessException
	 * @throws  \OutsideAreaException
	 */
	public static function rename_dir($path, $new_path, $source_area = null, $target_area = null)
	{
		return static::rename($path, $new_path, $source_area, $target_area);
	}

	/**
	 * Copy file
	 *
	 * @param   string                 path         path to file to copy
	 * @param   string                 new_path     new base directory (full path)
	 * @param   string|File_Area|null  source_area  source path file area name, object or null for non-specific
	 * @param   string|File_Area|null  target_area  target path file area name, object or null for non-specific. Defaults to source_area if not set.
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 * @return  bool
	 */
	public static function copy($path, $new_path, $source_area = null, $target_area = null)
	{
		$path      = static::instance($source_area)->get_path($path);
		$new_path  = static::instance($target_area ?: $source_area)->get_path($new_path);

		if ( ! is_file($path))
		{
			throw new \InvalidPathException('Cannot copy file: given path: "'.$path.'" is not a file.');
		}
		elseif (file_exists($new_path))
		{
			throw new \FileAccessException('Cannot copy file: new path: "'.$new_path.'" already exists.');
		}
		return copy($path, $new_path);
	}

	/**
	 * Copy directory
	 *
	 * @param  string                 $path         path to directory which contents will be copied
	 * @param  string                 $new_path     new base directory (full path)
	 * @param  string|File_Area|null  $source_area  source path file area name, object or null for non-specific
	 * @param  string|File_Area|null  $target_area  target path file area name, object or null for non-specific. Defaults to source_area if not set.
	 * @throws \FileAccessException   when something went wrong
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function copy_dir($path, $new_path, $source_area = null, $target_area = null)
	{
		$target_area = $target_area ?: $source_area;

		$path      = rtrim(static::instance($source_area)->get_path($path), '\\/').DS;
		$new_path  = rtrim(static::instance($target_area)->get_path($new_path), '\\/').DS;

		if ( ! is_dir($path))
		{
			throw new \InvalidPathException('Cannot copy directory: given path: "'.$path.'" is not a directory: '.$path);
		}
		elseif ( ! file_exists($new_path))
		{
			$newpath_dirname = pathinfo($new_path, PATHINFO_DIRNAME);
			static::create_dir($newpath_dirname, pathinfo($new_path, PATHINFO_BASENAME), fileperms($newpath_dirname) ?: 0777, $target_area);
		}

		$files = static::read_dir($path, -1, array(), $source_area);
		foreach ($files as $dir => $file)
		{
			if (is_array($file))
			{
				$check = static::create_dir($new_path.DS, substr($dir, 0, -1), fileperms($path.$dir) ?: 0777, $target_area);
				$check and static::copy_dir($path.$dir.DS, $new_path.$dir, $source_area, $target_area);
			}
			else
			{
				$check = static::copy($path.$file, $new_path.$file, $source_area, $target_area);
			}

			// abort if something went wrong
			if ( ! $check)
			{
				throw new \FileAccessException('Directory copy aborted prematurely, part of the operation failed during copying: '.(is_array($file) ? $dir : $file));
			}
		}
	}

	/**
	 * Create a new symlink
	 *
	 * @param   string                 $path       target of symlink
	 * @param   string                 $link_path  destination of symlink
	 * @param   bool                   $is_file    true for file, false for directory
	 * @param   string|File_Area|null  $area       file area name, object or null for base area
	 * @return  bool
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function symlink($path, $link_path, $is_file = true, $area = null)
	{
		$path      = rtrim(static::instance($area)->get_path($path), '\\/');
		$link_path = rtrim(static::instance($area)->get_path($link_path), '\\/');

		if ($is_file and ! is_file($path))
		{
			throw new \InvalidPathException('Cannot symlink: given file: "'.$path.'" does not exist.');
		}
		elseif ( ! $is_file and ! is_dir($path))
		{
			throw new \InvalidPathException('Cannot symlink: given directory: "'.$path.'" does not exist.');
		}
		elseif (file_exists($link_path))
		{
			throw new \FileAccessException('Cannot symlink: link: "'.$link_path.'" already exists.');
		}

		return symlink($path, $link_path);
	}

	/**
	 * Delete file
	 *
	 * @param string                $path  path to file to delete
	 * @param string|File_Area|null $area  file area name, object or null for base area
	 * @return bool
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function delete($path, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/');

		if ( ! is_file($path) and ! is_link($path))
		{
			throw new \InvalidPathException('Cannot delete file: given path "'.$path.'" is not a file.');
		}

		return unlink($path);
	}

	/**
	 * Delete directory
	 *
	 * @param   string                 $path        path to directory to delete
	 * @param   bool                   $recursive   whether to also delete contents of subdirectories
	 * @param   bool                   $delete_top  whether to delete the parent dir itself when empty
	 * @param   string|File_Area|null  $area        file area name, object or null for base area
	 * @return  bool
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function delete_dir($path, $recursive = true, $delete_top = true, $area = null)
	{
		$path = rtrim(static::instance($area)->get_path($path), '\\/').DS;
		if ( ! is_dir($path))
		{
			throw new \InvalidPathException('Cannot delete directory: given path: "'.$path.'" is not a directory.');
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
					$check = static::delete_dir($path.$dir, true, true, $area);
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
	 * @param  resource|string        $path  file resource or path
	 * @param  constant|bool          $lock  either valid lock constant or true=LOCK_EX / false=LOCK_UN
	 * @param  string|File_Area|null  $area  file area name, object or null for base area
	 * @return bool|resource
	 * @throws \FileAccessException
	 * @throws \OutsideAreaException
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
	 * @param resource               $resource  open file resource
	 * @param string|File_Area|null  $area      file area name, object or null for base area
	 */
	public static function close_file($resource, $area = null)
	{
		// If locks aren't used, don't unlock
		if ( static::instance($area)->use_locks())
		{
			flock($resource, LOCK_UN);
		}

		fclose($resource);
	}

	/**
	 * Get detailed information about a file
	 *
	 * @param  string                 $path  file path
	 * @param  string|File_Area|null  $area  file area name, object or null for base area
	 * @return array
	 * @throws \FileAccessException
	 * @throws \InvalidPathException
	 * @throws \OutsideAreaException
	 */
	public static function file_info($path, $area = null)
	{
		$info = array(
			'original' => $path,
			'realpath' => '',
			'dirname' => '',
			'basename' => '',
			'filename' => '',
			'extension' => '',
			'mimetype' => '',
			'charset' => '',
			'size' => 0,
			'permissions' => '',
			'time_created' => '',
			'time_modified' => '',
		);

		if ( ! $info['realpath'] = static::instance($area)->get_path($path) or ! is_file($info['realpath']))
		{
			throw new \InvalidPathException('Filename given is not a valid file.');
		}

		$info = array_merge($info, pathinfo($info['realpath']));

		if ( ! $fileinfo = new \finfo(FILEINFO_MIME, \Config::get('file.magic_file', null)))
		{
			throw new \InvalidArgumentException('Can not retrieve information about this file.');
		}

		$fileinfo = explode(';', $fileinfo->file($info['realpath']));

		$info['mimetype'] = isset($fileinfo[0]) ? $fileinfo[0] : 'application/octet-stream';

		if (isset($fileinfo[1]))
		{
			$fileinfo = explode('=', $fileinfo[1]);
			$info['charset'] = isset($fileinfo[1]) ? $fileinfo[1] : '';
		}

		$info['size'] = static::get_size($info['realpath'], $area);
		$info['permissions'] = static::get_permissions($info['realpath'], $area);
		$info['time_created'] = static::get_time($info['realpath'], 'created', $area);
		$info['time_modified'] = static::get_time($info['realpath'], 'modified', $area);

		return $info;
	}

	/**
	 * Download a file
	 *
	 * @param  string                 $path         file path
	 * @param  string|null            $name         custom name for the file to be downloaded
	 * @param  string|null            $mime         custom mime type or null for file mime type
	 * @param  string|File_Area|null  $area         file area name, object or null for base area
	 * @param  bool                   $delete       delete the file after download when true
	 * @param  string                 $disposition  disposition, must be 'attachment' or 'inline'
	 */
	public static function download($path, $name = null, $mime = null, $area = null, $delete = false, $disposition = 'attachment')
	{
		$info = static::file_info($path, $area);
		$class = get_called_class();
		empty($mime) or $info['mimetype'] = $mime;
		empty($name) or $info['basename'] = $name;
		in_array($disposition, array('inline', 'attachment')) or $disposition = 'attachment';

		\Event::register('fuel-shutdown', function () use($info, $area, $class, $delete, $disposition) {

			if ( ! $file = call_user_func(array($class, 'open_file'), @fopen($info['realpath'], 'rb'), LOCK_SH, $area))
			{
				throw new \FileAccessException('Filename given could not be opened for download.');
			}

			while (ob_get_level() > 0)
			{
				ob_end_clean();
			}

			ini_get('zlib.output_compression') and ini_set('zlib.output_compression', 0);
			! ini_get('safe_mode') and set_time_limit(0);

			header('Content-Type: '.$info['mimetype']);
			header('Content-Disposition: '.$disposition.'; filename="'.$info['basename'].'"');
			$disposition === 'attachment' and header('Content-Description: File Transfer');
			header('Content-Length: '.$info['size']);
			header('Content-Transfer-Encoding: binary');
			$disposition === 'attachment' and header('Expires: 0');
			$disposition === 'attachment' and header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

			while( ! feof($file))
			{
				echo fread($file, 2048);
			}

			call_user_func(array($class, 'close_file'), $file, $area);

			if ($delete)
			{
				call_user_func(array($class, 'delete'), $info['realpath'], $area);
			}
		});

		exit;
	}

}
