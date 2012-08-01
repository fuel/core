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

class File_Handler_Directory implements \Iterator, \ArrayAccess, \Countable
{

	/**
	 * @var	string	path to the file
	 */
	protected $path;

	/**
	 * @var	File_Area
	 */
	protected $area;

	/**
	 * @var	array	listing of files and directories within this directory
	 */
	protected $content = array();

	protected function __construct($path, array &$config, File_Area $area, $content = array())
	{
		$this->path	= rtrim($path, '\\/').DS;
		$this->area	= $area;

		foreach ($content as $key => $value)
		{
			if ( ! is_int($key))
			{
				$key = trim($key, '\\/');
				$this->content[$key] = $value === false ? false : $area->get_handler($path.DS.$key, $config, $value);
			}
			else
			{
				$this->content[$key] = $area->get_handler($path.DS.$value, $config);
			}
		}
	}

	public static function forge($path, array $config = array(), File_Area $area = null, $content = array())
	{
		return new static($path, $config, $area, $content);
	}

	/**
	 * Returns filtered content of the directory
	 *
	 * @return array
	 */
	public function get_content()
	{
		return $this->content;
	}

	/**
	 * Read directory
	 *
	 * @param	$dept		whether or not to read recursive
	 * @param	$filters	whether or not to read recursive
	 * @return	array
	 */
	public function read($depth = 0, $filters = null)
	{
		return $this->area->read_dir($this->path, $depth, $filters, $this->area);
	}

	/**
	 * Rename file, only within current directory
	 *
	 * @param	string	new directory name
	 * @return	bool
	 */
	public function rename($new_name)
	{
		$info = pathinfo($this->path);

		$new_name = str_replace(array('..', '/', '\\'), array('', '', ''), $new_name);

		$new_path = $info['dirname'].DS.$new_name;

		$return =  $this->area->rename_dir($this->path, $new_path);
		$return and $this->path = $new_path;

		return $return;
	}

	/**
	 * Move directory to new parent directory
	 *
	 * @param	string	path to new parent directory, must be valid
	 * @return	bool
	 */
	public function move($new_path)
	{
		$info = pathinfo($this->path);
		$new_path = $this->area->get_path($new_path);

		$new_path = rtrim($new_path, '\\/').DS.$info['basename'];

		$return =  $this->area->rename_dir($this->path, $new_path);
		$return and $this->path = $new_path;

		return $return;
	}

	/**
	 * Copy directory
	 *
	 * @param	string	path to parent directory, must be valid
	 * @return	bool
	 */
	public function copy($new_path)
	{
		$info = pathinfo($this->path);
		$new_path = $this->area->get_path($new_path);

		$new_path = rtrim($new_path, '\\/').DS.$info['basename'];

		return $this->area->copy_dir($this->path, $new_path);
	}

	/**
	 * Update contents
	 *
	 * @param	mixed	new file contents
	 * @return	bool
	 */
	public function update()
	{
		throw new \BadMethodCallException('Update method is unavailable on directories.');
	}

	/**
	 * Delete directory
	 *
	 * @return	bool
	 */
	public function delete($recursive = true, $delete_top = true)
	{
		// should also destroy object but not possible in PHP right?
		return $this->area->delete_dir($this->path, $recursive, $delete_top);
	}

	/**
	 * Get the path.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return $this->path;
	}

	/**
	 * Get the pathinfo of the path.
	 *
	 * @return object
	 */
	public function get_pathinfo()
	{
		return (object) pathinfo($this->path);
	}

	/**
	 * Get the url.
	 *
	 * @return	bool
	 */
	public function get_url()
	{
		throw new \BadMethodCallException('Get_url method is unavailable on directories.');
	}

	/**
	 * Get the directory permissions.
	 *
	 * @return	string	file permissions
	 */
	public function get_permissions()
	{
		return $this->area->get_permissions($this->path);
	}

	/**
	 * Get directory's the created or modified timestamp.
	 *
	 * @param	string	$type	modified or created
	 * @return	int		Unix Timestamp
	 */
	public function get_time($type = 'modified')
	{
		return $this->area->get_time($this->path, $type);
	}

	/**
	 * Get the size.
	 *
	 * @return	bool
	 */
	public function get_size()
	{
		throw new \BadMethodCallException('Get_size method is unavailable on directories.');
	}

	/**
	 * Return the current element.
	 *
	 * @return mixed Can return any type
	 */
	public function current()
	{
		return current($this->content);
	}

	/**
	 * Move forward to next element.
	 *
	 * @return void Any returned value is ignored
	 */
	public function next()
	{
		next($this->content);
	}

	/**
	 * Return the key of the current element.
	 *
	 * @return scalar scalar on success, or null on failure
	 */
	public function key()
	{
		return key($this->content);
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean Returns true on success or false on failure
	 */
	public function valid()
	{
		$key = key($this->content);
		return isset($key);
	}

	/**
	 * Rewind the Iterator to the first element.
	 *
	 * @return void Any returned value is ignored
	 */
	public function rewind()
	{
		reset($this->content);
	}

	/**
	 * Whether a offset exists.
	 *
	 * @param mixed $offset An offset to check for
	 * @return boolean true on success or false on failure
	 */
	public function offsetExists($offset)
	{
		// in fact we can't have null as value, so...
		return $this->offsetGet($offset) !== null;
	}

	/**
	 * Offset to retrieve.
	 *
	 * @param mixed $offset The offset to retrieve
	 * @return mixed Can return all value types
	 */
	public function offsetGet($offset)
	{
		if (isset($this->content[$offset])) {
			return $this->content[$offset];
		}

		// ok, but files are indexed as int, not as name, so try to find it
		foreach ($this->content as $file)
		{
			if ($file->get_pathinfo()->basename === $offset or $file->get_pathinfo()->filename === $offset)
			{
				return $file;
			}
		}

		// hmm, I think it's better to return null than error
		// additionaly, we can't have null as value in $content array
		return null;
	}

	/**
	 * Offset to set.
	 *
	 * @param mixed $offset The offset to assign the value to
	 * @param mixed $value The value to set
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException('offsetSet method is unavailable on directories.');
	}

	/**
	 * Offset to unset.
	 *
	 * @param mixed $offset The offset to unset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException('offsetUnset method is unavailable on directories.');
	}

	/**
	 * Count elements.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->content);
	}
}


