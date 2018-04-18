<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Cache_Storage_File extends \Cache_Storage_Driver
{
	/**
	 * @const  string  Tag used for opening & closing cache properties
	 */
	const PROPS_TAG = 'Fuel_Cache_Properties';

	/**
	 * @var  string  File caching basepath
	 */
	protected static $path = '';

	/**
	 * @var  array  driver specific configuration
	 */
	protected $config = array();

	// ---------------------------------------------------------------------

	public static function _init()
	{
		\Config::load('file', true);

		// make sure the configured chmod values are octal
		$chmod = \Config::get('file.chmod.folders', 0777);
		is_string($chmod) and \Config::set('file.chmod.folders', octdec($chmod));
		$chmod = \Config::get('file.chmod.files', 0666);
		is_string($chmod) and \Config::set('file.chmod.files', octdec($chmod));
	}

	// ---------------------------------------------------------------------

	public function __construct($identifier, $config)
	{
		parent::__construct($identifier, $config);

		$this->config = isset($config['file']) ? $config['file'] : array();

		// check for an expiration override
		$this->expiration = $this->_validate_config('expiration', isset($this->config['expiration'])
			? $this->config['expiration'] : $this->expiration);

		// determine the file cache path
		static::$path = !empty($this->config['path'])
			? $this->config['path'] : \Config::get('cache_dir', APPPATH.'cache'.DS);

		if ( ! is_dir(static::$path) || ! is_writable(static::$path))
		{
			throw new \FuelException('Cache directory does not exist or is not writable.');
		}
	}

	/**
	 * Check if other caches or files have been changed since cache creation
	 *
	 * @param   array
	 * @return  bool
	 */
	public function check_dependencies(array $dependencies)
	{
		foreach($dependencies as $dep)
		{
			if (is_file($file = static::$path.str_replace('.', DS, $dep).'.cache'))
			{
				$filemtime = filemtime($file);
				if ($filemtime === false || $filemtime > $this->created)
				{
					return false;
				}
			}
			elseif (is_file($dep))
			{
				$filemtime = filemtime($dep);
				if ($filemtime === false || $filemtime > $this->created)
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete Cache
	 */
	public function delete()
	{
		if (is_file($file = static::$path.$this->identifier_to_path($this->identifier).'.cache'))
		{
			unlink($file);
			$this->reset();
		}
	}

	/**
	 * Purge all caches
	 *
	 * @param   string  $section  limit purge to subsection
	 * @return  bool
	 */
	public function delete_all($section)
	{
		// get the cache root path and prep the requested section
		$path = rtrim(static::$path, '\\/').DS;
		$section = $section === null ? '' : static::identifier_to_path($section).DS;

		// if the path does not exist, bail out immediately
		if ( ! is_dir($path.$section))
		{
			return true;
		}

		// get all files in this section
		$files = \File::read_dir($path.$section, -1, array('\.cache$' => 'file'));

		// closure to recusively delete the files
		$delete = function($folder, $files) use(&$delete, $path)
		{
			$folder = rtrim($folder, '\\/').DS;

			foreach ($files as $dir => $file)
			{
				if (is_numeric($dir))
				{
					if ( ! $result = \File::delete($folder.$file))
					{
						return $result;
					}
				}
				else
				{
					if ( ! $result = ($delete($folder.$dir, $file)))
					{
						return $result;
					}
				}
			}

			// if we're processing a sub directory
			if ($folder != $path)
			{
				// remove the folder if no more files are left
				$files = \File::read_dir($folder);
				empty($files) and rmdir($folder);
			}

			return true;
		};

		return $delete($path.$section, $files);
	}

	// ---------------------------------------------------------------------

	/**
	 * Translates a given identifier to a valid path
	 *
	 * @param   string
	 * @return  string
	 */
	protected function identifier_to_path($identifier)
	{
		// replace dots with dashes
		$identifier = str_replace('.', DS, $identifier);

		return $identifier;
	}

	/**
	 * Prepend the cache properties
	 *
	 * @return  string
	 */
	protected function prep_contents()
	{
		$properties = array(
			'created'          => $this->created,
			'expiration'       => $this->expiration,
			'dependencies'     => $this->dependencies,
			'content_handler'  => $this->content_handler,
		);
		$properties = '{{'.self::PROPS_TAG.'}}'.json_encode($properties).'{{/'.self::PROPS_TAG.'}}';

		return $properties.$this->contents;
	}

	/**
	 * Remove the prepended cache properties and save them in class properties
	 *
	 * @param   string
	 * @throws \UnexpectedValueException
	 */
	protected function unprep_contents($payload)
	{
		$properties_end = strpos($payload, '{{/'.self::PROPS_TAG.'}}');
		if ($properties_end === false)
		{
			throw new \UnexpectedValueException('Cache has bad formatting');
		}

		$this->contents = substr($payload, $properties_end + strlen('{{/'.self::PROPS_TAG.'}}'));
		$props = substr(substr($payload, 0, $properties_end), strlen('{{'.self::PROPS_TAG.'}}'));
		$props = json_decode($props, true);
		if ($props === null)
		{
			throw new \UnexpectedValueException('Cache properties retrieval failed');
		}

		$this->created          = $props['created'];
		$this->expiration       = is_null($props['expiration']) ? null : (int) ($props['expiration'] - time());
		$this->dependencies     = $props['dependencies'];
		$this->content_handler  = $props['content_handler'];
	}

	/**
	 * Save a cache, this does the generic pre-processing
	 *
	 * @return  bool  success
	 */
	protected function _set()
	{
		$payload = $this->prep_contents();
		$id_path = $this->identifier_to_path($this->identifier);

		// create directory if necessary
		$subdirs = explode(DS, $id_path);
		if (count($subdirs) > 1)
		{
			array_pop($subdirs);

			// check if specified subdir exists
			if ( ! @is_dir(static::$path.implode(DS, $subdirs)))
			{
				// recursively create the directory. we can't use mkdir permissions or recursive
				// due to the fact that mkdir is restricted by the current users umask
				$basepath = rtrim(static::$path, DS);
				$chmod = \Config::get('file.chmod.folders', 0775);
				foreach ($subdirs as $dir)
				{
					$basepath .= DS.$dir;
					if ( ! is_dir($basepath))
					{
						try
						{
							if ( ! mkdir($basepath))
							{
								return false;
							}
							chmod($basepath, $chmod);
						}
						catch (\PHPErrorException $e)
						{
							return false;
						}
					}
				}
			}
		}

		// write the cache
		$file = static::$path.$id_path.'.cache';

		$handle = fopen($file, 'c');

		if ( ! $handle)
		{
			return false;
		}

		// wait for a lock
		while ( ! flock($handle, LOCK_EX));

		// truncate the file
		ftruncate($handle, 0);

		// write the cache data
		fwrite($handle, $payload);

		// flush any pending output
		fflush($handle);

		//release the lock
		flock($handle, LOCK_UN);

		// close the file
		fclose($handle);

		// set the correct rights on the file
		chmod($file, \Config::get('file.chmod.files', 0666));

		return true;
	}

	/**
	 * Load a cache, this does the generic post-processing
	 *
	 * @return  bool  success
	 */
	protected function _get()
	{
		$payload = false;

		$id_path = $this->identifier_to_path( $this->identifier );
		$file = static::$path.$id_path.'.cache';

		// normalize the file
		$file = realpath($file);

		// make sure it exists
		if (is_file($file))
		{
			$handle = fopen($file, 'r');
			if ($handle)
			{
				// wait for a lock
				while( ! flock($handle, LOCK_SH));

				// read the cache data
				$payload = file_get_contents($file);

				//release the lock
				flock($handle, LOCK_UN);

				// close the file
				fclose($handle);

			}
		}

		try
		{
			$this->unprep_contents($payload);
		}
		catch (\UnexpectedValueException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * validate a driver config value
	 *
	 * @param   string  $name  name of the config variable to validate
	 * @param   mixed   $value
	 * @return  mixed
	 */
	protected function _validate_config($name, $value)
	{
		switch ($name)
		{
			case 'cache_id':
				if (empty($value) or ! is_string($value))
				{
					$value = 'fuel';
				}
			break;

			case 'expiration':
				if (empty($value) or ! is_numeric($value))
				{
					$value = null;
				}
			break;
		}

		return $value;
	}
}
