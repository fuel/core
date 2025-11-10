<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Tasks;

/**
 * Translate task
 *
 * Assist in managing your apps translation files
 */
class Translate
{
	/**
	 * @string  source language
	 */
	protected $source = null;

	/**
	 * @string  target language
	 */
	protected $target = null;

	/**
	 * @bool  whether or not to create the target
	 */
	protected $create = false;

	/**
	 * @bool  whether or not to check both source and target
	 */
	protected $bidirectional = false;

	/**
	 * @string  Answer yes to all prompte
	 */
	protected $yes = false;

	/**
	 * @string  translation service API key
	 */
	protected $apikey = null;

	/**
	 * @string  output log file
	 */
	protected $log = null;

	/**
	 * @string  last curl request constructed
	 */
	protected $lastreq = null;

	/**
	 * @string  request delay in seconds
	 */
	protected $delay = 1;

	/**
	 * class constructor, sets the properties by grabbing Cli options
	 */
	public function __construct()
	{
		// get Cli options
		$this->source = \Cli::option('source', \Cli::option('s'));
		$this->target = \Cli::option('target', \Cli::option('t'));
		$this->create = \Cli::option('create') ? true : false;
		$this->bidirectional = \Cli::option('bidirectional') ? true : false;
		$this->yes = \Cli::option('yes') ? true : false;
		$this->apikey = \Cli::option('apikey');
		$this->log = \Cli::option('log');

		// if logging is requested, open the logfile
		if ($this->log)
		{
			try
			{
				$this->log = fopen($this->log, 'a+');
			}
			catch (\PHPErrorException $e)
			{
				\Cli::write(sprintf('Fatal error: %s', $e->getMessage()), 'red');
				die ();
			}
		}
	}

	/**
	 * class destructor, clean up after ourselfs
	 */
	public function __destruct()
	{
		// make sure we close the log file before terminating
		if ($this->log)
		{
			fclose($this->log);
		}
	}

	/**
	 * Main task entry point. This will only verify the given options,
	 * and display any help information if needed.
	 */
	public function run()
	{
		// verify the options given
		if (is_null($this->source) and is_null($this->target))
		{
			$this->help();
		}
		else
		{
			if ( ! $this->verify_options())
			{
				\Cli::write('');
				$this->help();
			}
			else
			{
				\Cli::write('Source and target language codes are valid', 'green');
			}
		}
	}

	/**
	 * Enumerate source and target language files, and determines if any
	 * are missing from the target.
	 */
	public function diff()
	{
		// make sure we have all required options
		if ($this->verify_options(true))
		{
			// enumarate all paths with language files
			$paths = $this->enumerate_paths();

			// framework paths are all in the "global" namespace
			$translations = array();
			foreach ($paths['framework'] as $name => $path)
			{
				// so merge them all
				$translations = \Arr::merge($translations, $this->enumerate_langs($path));
			}

			// generate a diff for the translations found
			$this->write(sprintf('Generating a diff for translations in the application:'), 'white');
			$this->generate_diff($translations);

			// for every module path found, enumerate the language files
			foreach ($paths['modules'] as $path)
			{
				$this->write(sprintf('Generating a diff for translations in %s:', $path), 'white');

				// generate a diff for this path
				$this->generate_diff($this->enumerate_langs($path));
			}
		}
	}

	/**
	 * Manual translation of missing translation strings
	 */
	public function manual()
	{
		$this->translate('manual');
	}

	/**
	 * Automated translation of missing translation strings using Deepl
	 */
	public function deepl()
	{
		if (empty($this->apikey))
		{
			\Cli::write('Missing Deepl API key (--apikey)', 'red');
		}
		elseif ( ! is_uuid(explode(":", $this->apikey)[0]))
		{
			\Cli::write('Deepl API key must be in UUID format (--apikey)', 'red');
		}
		else
		{
			$this->translate('deepl');
		}
	}

	/**
	 * Shows basic help instructions for using migrate in oil
	 */
	public function help()
	{
		echo <<<HELP
Description:
    The translate task can assist in creating and mantaining language translations for your application.

    Language translations in fuel core, the app, modules and packages will be merged as they are all
    defined in the "global" language namespace and allow for overloading. As the framework and its
    packages are considered read-only for applications, new translations are created in the app lang
    directory. It uses the paths defined in the app config file to find installed modules and packages.

Usage:
    php oil refine translate[:command] --source=<lang> --target=<lang>

Task commands:
    help       Shows this text
    diff       Generate a diff between source and target language, to see what exactly is missing in the target
    manual     Scans for missing translations, and prompts for each one so you can enter the required translation
    deepl      Scans for missing translations, and uses Deepl to automatically translate

Task options:
    -s=<lang>, [--source=<lang>]     The language code to use as the source language
    -t=<lang>, [--target=<lang>]     The language code to use as the target language
    --bidirectional                  Checks both source and destination for missing translations
    --create                         Create the target files, if they don't exist
    --yes                            Answer "yes" to all prompts, allows for unattended translation runs
    --log=<file>                     Write the output to a file instead of to the console

Examples:
    php oil r translate                              Shows this help
    php oil r translate:help                         Shows this help
    php oil r translate --source=en -t=nl            Verifies source and target are defined in the app as valid language codes
    php oil r translate:diff --source=en -t=nl       Check if all "en" translation strings have an equivalent in the "nl" translations
    php oil r translate:manual -s=en --target=nl     Manually translate all missing "en" translation strings into an "nl" translation
HELP;
	}

	// =============[ internal methods ]=============

	/**
	 * Verify the input options that source and target language strings are given, and they are valid
	 */
	protected function verify_options($silent = false)
	{
		// storage for the result, assume all is well
		$result = true;

		// check if we have a valid source language code
		if ( ! is_string($this->source) or empty($this->source))
		{
			\Cli::write('Missing source language code (--source)', 'red');
			$result = false;
		}
		else
		{
			// language codes are lowercase
			$this->source = strtolower($this->source);

			// and must be defined in the app, at least
			if ( ! \is_dir(APPPATH.'lang'.DS.$this->source))
			{
				\Cli::write(sprintf('There is no "%s" language defined in this Fuel app!', $this->source), 'red');
				$result = false;
			}
		}

		// check if we have a valid target language code
		if ( ! is_string($this->target) or empty($this->target))
		{
			\Cli::write('Missing source language code (--target)', 'red');
			$result = false;
		}
		else
		{
			// language codes are lowercase
			$this->target = strtolower($this->target);

			// and must be defined in the app, at least
			if ( ! \is_dir(APPPATH.'lang'.DS.$this->target))
			{
				if ($this->create)
				{
					$silent or \Cli::write(sprintf('Language translations for "%s" will be added to this app', $this->target), 'yellow');
				}
				else
				{
					\Cli::write(sprintf('There is no "%s" language defined in this Fuel app!', $this->target), 'red');
					$result = false;
				}
			}
		}

		// source and target may not be the same
		if ($result and $this->source == $this->target)
		{
			\Cli::write('Source language code (--source) may not be equal to the target code (--target)', 'red');
			$result = false;
		}

		// if a logfile is given, check if we can write to it
		if ( ! is_null($this->log))
		{
			if ( ! is_string($this->log) or empty($this->log))
			{
				\Cli::write('Missing log filename (--log)', 'red');
				$result = false;
			}
			elseif ( ! is_writable(dirname($this->log)))
			{
				\Cli::write(sprintf('No permissions to write to the filename %s (--log)', $this->log), 'red');
				$result = false;
			}
			elseif ( file_exists($this->log) and ! is_writable($this->log))
			{
				\Cli::write(sprintf('No permissions to write to the filename %s (--log)', $this->log), 'red');
				$result = false;
			}
		}

		// return the verify result
		return $result;
	}

	/**
	 * Enumerate all paths that may contain language files
	 *
	 * @returns   array of paths, or false if an enumeration error was detected
	 */
	protected function enumerate_paths()
	{
		// check the core first, should have a lang directory
		if ( ! is_dir($coredir = COREPATH.'lang'))
		{
			\Cli::write(sprintf('No "lang" directory has been found in the fuel core directory!'), 'red');
			return false;
		}

		// storage for the paths found
		$paths = array(
			'framework' => array(
				'_core_' => $coredir,
			),
			'modules' => array()
		);

		// iterate over all possible package locations
		foreach (\Config::get('package_paths', array()) as $path)
		{
			// get all directories in the path
			$entries = \File::read_dir($path, 2, array('!^\.', '!.*' => 'file'));

			foreach ($entries as $name => $contents)
			{
				if (array_key_exists('lang'.DS, $contents))
				{
					$paths['framework'][rtrim($name, DS)] = realpath(rtrim($path, DS).DS.$name.'lang');
				}
			}
		}

		// check the app first, should have a lang directory
		if ( ! is_dir($appdir = APPPATH.'lang'))
		{
			\Cli::write(sprintf('No "lang" directory has been found in the app directory!'), 'red');
			return false;
		}
		$paths['framework']['_app_'] = $appdir;

		// iterate over all possible package locations
		foreach (\Config::get('module_paths', array()) as $path)
		{
			// get all directories in the path
			$entries = \File::read_dir($path, 2, array('!^\.', '!.*' => 'file'));

			foreach ($entries as $name => $contents)
			{
				if (array_key_exists('lang'.DS, $contents))
				{
					$paths['modules'][rtrim($name, DS)] = realpath(rtrim($path, DS).DS.$name.'lang');
				}
			}
		}

		return $paths;
	}

	/**
	 * Enumerate all language translations, source and target, from a given path
	 *
	 * @param  string  path to the language files to enumerate
	 *
	 * @returns   array of array of language strings, one for source, one for target
	 */
	protected function enumerate_langs($path)
	{
		// storage for the result
		$result = array(
			$this->source => array(),
			$this->target => array(),
		);

		// double check if the path exists
		if ( ! is_dir($path))
		{
			\Cli::write(sprintf('Fatal error: given lanuage path "%s" no longer exists', $path), 'red');
		}
		else
		{
			// check if this is a path to a framework or package file
			$is_core = strpos($path, COREPATH) === 0 || strpos($path, PKGPATH) === 0;

			// enumerate the source files
			if (is_dir($path.DS.$this->source))
			{
				$files = \File::read_dir($path.DS.$this->source, 1, array('!^\.', '!.*' => 'dir'));
				foreach ($files as $file)
				{
					// translations for core files go into the app
					if ($is_core)
					{
						$result[$this->source][$file] = array(
							'file' => APPPATH.'lang'.DS.$this->target.DS.$file,
							'lang' => \Lang::load($path.DS.$this->source.DS.$file, false),
						);
					}
					else
					{
						$result[$this->source][$file] = array(
							'file' => $path.DS.$this->target.DS.$file,
							'lang' => \Lang::load($path.DS.$this->source.DS.$file, false),
						);
					}
				}
			}

			// enumerate the target files
			if (is_dir($path.DS.$this->target))
			{
				$files = \File::read_dir($path.DS.$this->target, 1, array('!^\.', '!.*' => 'dir'));
				foreach ($files as $file)
				{
					if ($is_core)
					{
						$result[$this->target][$file] = array(
							'file' => APPPATH.'lang'.DS.$this->source.DS.$file,
							'lang' => \Lang::load($path.DS.$this->target.DS.$file, false),
						);
					}
					else
					{
						$result[$this->target][$file] = array(
							'file' => $path.DS.$this->source.DS.$file,
							'lang' => \Lang::load($path.DS.$this->target.DS.$file, false),
						);
					}
				}
			}
		}

		// return the enimerated language strings
		return $result;
	}

	/**
	 * Generate a diff between two languages
	 *
	 * @param      array  multi-dimensional array of loaded translations, in the format
	 *                    [source][filename]['file'], [target][filename]['file'] and
	 *                    [source][filename]['lang'], [target][filename]['lang']
	 * @param      bool   whether or not to return a diff in array format
	 *
	 * @return     array|null
	 */
	protected function generate_diff($translations, $make_diff = false)
	{
		// storage for the diff
		$diff = array($this->source => array(), $this->target => array());

		// array_diff closure
		$compare = function($source, $target, &$diff, $prefix = '') use (&$compare, $make_diff) {
			if (is_array($source))
			{
				if (is_array($target))
				{
					foreach ($source as $skey => $value)
					{
						if (is_array($value))
						{
							if (array_key_exists($skey, $target))
							{
								$compare($value, $target[$skey], $diff, $prefix.$skey.'.');
							}
							else
							{
								$this->write(sprintf('    key "%s" in source is an array, but it isn\'t present in the target', $prefix.$skey), 'light_yellow');
								if ($make_diff and ($this->yes or \Cli::prompt('    do you want to translate it?', array('y','n')) == 'y'))
								{
									$diff[$skey] = $value;
								}
							}
						}
						elseif (array_key_exists($skey, $target))
						{
							if (gettype($value) != gettype($target[$skey]))
							{
								$this->write(sprintf('    key "%s" in source is defined in target, but not of the same data type', $prefix.$skey), 'light_yellow');
							}
						}
						else
						{
							$this->write(sprintf('    key "%s" in source isn\'t defined in the target', $prefix.$skey), 'light_yellow');
							$make_diff and $diff[$skey] = $value;
						}
					}
				}
				elseif (gettype($source) != gettype($target))
				{
					$this->write(sprintf('    key "%s" in source is defined in target, but not of the same data type', $prefix), 'light_yellow');
				}
			}
		};

		// storage for the validated result, assume all is well
		$validated = true;

		// input validation
		if ( ! is_array($translations))
		{
			\Cli::write(sprintf('==> Fatal error: given translation data must be an array of strings'), 'red');
			$validated = false;
		}
		else
		{
			if ( ! array_key_exists($this->source, $translations))
			{
				\Cli::write(sprintf('==> Fatal error: given translation array has no entry for source language "%s"', $this->source), 'red');
				$validated = false;
			}
			elseif ( ! is_array($translations[$this->source]))
			{
				\Cli::write(sprintf('==> Fatal error: given source translation most be an array'), 'red');
				$validated = false;
			}

			if ( ! array_key_exists($this->target, $translations))
			{
				\Cli::write(sprintf('==> Fatal error: given translation array has no entry for target language "%s"', $this->target), 'red');
				$validated = false;
			}
			elseif ( ! is_array($translations[$this->target]))
			{
				\Cli::write(sprintf('==> Fatal error: given target translation most be an array'), 'red');
				$validated = false;
			}
		}

		// if all was well, create the diff
		if ($validated)
		{
			foreach ($translations[$this->source] as $file => $langinfo)
			{
				// langinfo should be an array with two entries
				if ( ! is_array($langinfo))
				{
					$this->write(sprintf('--> source translation file %s is not correctly loaded!', $file), 'light_red');
				}
				elseif ( ! array_key_exists('file', $langinfo))
				{
					$this->write(sprintf('--> source translation file %s is missing its FQFN!', $file), 'light_red');
				}
				elseif ( ! array_key_exists('lang', $langinfo))
				{
					$this->write(sprintf('--> source translation file %s is missing its language strings!', $file), 'light_red');
				}
				// check first if this exists in the translation
				elseif ( ! array_key_exists($file, $translations[$this->target]))
				{
					$this->write(sprintf('--> source translation file %s does not exist as target translation', $file), 'light_yellow');

					// entire destination is missing
					if ($make_diff and ($this->yes or \Cli::prompt('    do you want to translate it?', array('y','n')) == 'y'))
					{
						$diff[$this->source][$file] = $translations[$this->source][$file];
					}
				}
				else
				{
					$this->write(sprintf('--> verifying %s...', $file), 'light_grey');

					// compare source and destination
					$diff[$this->source][$file] = array('file' => $langinfo['file'], 'lang' => array());
					$compare($langinfo['lang'], $translations[$this->target][$file]['lang'], $diff[$this->source][$file]['lang']);
				}
			}

			// do we need to diff target -> source too?
			if ($this->bidirectional)
			{
				// check if we have translations in target that don't exist in source
				foreach ($translations[$this->target] as $file => $langinfo)
				{
					// langinfo should be an array with two entries
					if ( ! is_array($langinfo))
					{
						$this->write(sprintf('--> target translation file %s is not correctly loaded!', $file), 'light_red');
					}
					elseif ( ! array_key_exists('file', $langinfo))
					{
						$this->write(sprintf('--> target translation file %s is missing its FQFN!', $file), 'light_red');
					}
					elseif ( ! array_key_exists('lang', $langinfo))
					{
						$this->write(sprintf('--> target translation file %s is missing its language strings!', $file), 'light_red');
					}
					// check first if this exists in the translation
					elseif ( ! array_key_exists($file, $translations[$this->source]))
					{
						$this->write(sprintf('--> target translation file %s does not exist as source translation', $file), 'light_yellow');

						// entire source is missing
						$make_diff and $diff[$this->target][$file] = $translations[$this->target][$file];
					}
					else
					{
						$this->write(sprintf('--> verifying %s...', $file), 'light_grey');

						// check all translations
						$diff[$this->target][$file] = array('file' => $langinfo['file'], 'lang' => array());
						$compare($langinfo['lang'], $translations[$this->source][$file]['lang'], $diff[$this->target][$file]['lang']);
					}
				}
			}
		}

		// remove diff structures without missing translations
		foreach ($diff as $lang => $diffs)
		{
			foreach ($diffs as $file => $filediff)
			{
				if (empty($filediff['lang']))
				{
					unset($diff[$lang][$file]);
				}
			}
		}

		// return the result
		return $make_diff ? $diff : null;
	}

	/**
	 * Manual translation of missing translation strings
	 *
	 * @param     string      type of translation, valid are 'manual', 'deepl'
	 */
	public function translate($type)
	{
		// make sure we have all required options
		if ($this->verify_options(true))
		{
			// enumarate all paths with language files
			$paths = $this->enumerate_paths();

			// framework paths are all in the "global" namespace
			$translations = array();
			foreach ($paths['framework'] as $name => $path)
			{
				// so merge them all
				$translations = \Arr::merge($translations, $this->enumerate_langs($path));
			}

			// generate a diff for the translations found
			$this->write(sprintf('Generating a diff for translations in the application:'), 'white');
			foreach ($this->generate_diff($translations, true) as $lang => $translation)
			{
				// get translations for every diff entry
				$this->translate_diff($type, $lang, $translation, $translations);
			}

			// for every module path found, enumerate the language files
			foreach ($paths['modules'] as $path)
			{
				$this->write(sprintf('Generating a diff for translations in %s:', $path), 'white');

				// generate a diff for this path
				foreach ($this->generate_diff($translations = $this->enumerate_langs($path), true) as $lang => $translation)
				{
					// get translations for every diff entry
					$this->translate_diff($type, $lang, $translation, $translations);
				}
			}
		}
	}

	/**
	 * Take a diff from a language location, and translate it
	 *
	 * @param     string      type of translation, valid are 'manual', 'deepl'
	 * @param     string      language code
	 * @param     array       generated diff to translate
	 * @param     array       all loaded translations
	 */
	protected function translate_diff($type, $lang, $translation, $translations)
	{
		if ( ! empty($translation))
		{
			foreach ($translation as $file => $diff)
			{
				$translated = array();
				foreach ($diff['lang'] as $key => $value)
				{
					switch ($type)
					{
						case "deepl":
							$translated[$key] = $this->translate_deepl($lang, $diff['file'], $key, $value);
							break;

						case "manual":
						default:
							$translated[$key] = $this->translate_manual($lang, $diff['file'], $key, $value);
					}
				}

				// update the translations file
				if ($lang == $this->source)
				{
					// get the original target file
					$original = array_key_exists($file, $translations[$this->target]) ? $translations[$this->target][$file]['lang'] : array();

					// language we're saving
					$lang = $this->target;
				}
				elseif ($lang == $this->target)
				{
					// get the original source file
					$original = array_key_exists($file, $translations[$this->source]) ? $translations[$this->source][$file]['lang'] : array();

					// language we're saving
					$lang = $this->source;
				}
				else
				{
					throw new \OutOfBoundsException(sprintf('Language "%s" is neither source nor target!', strtoupper($lang)));
				}

				// merge the translations in, and save it
				\Lang::save($diff['file'], array_merge($original, $translated), $lang);
			}
		}
	}

	/**
	 * Manual translation, prompt the user to enter a translation
	 *
	 * @param     string           language code being translated
	 * @param     string           name of the translation file
	 * @param     string           translation language key
	 * @param     string|array     translation value
	 *
	 * @return    string|array
	 */
	protected function translate_manual($lang, $file, $key, $translation)
	{
		// recusrse on an array of values
		if (is_array($translation))
		{
			foreach ($translation as $tkey => $value)
			{
				$translation[$tkey] = $this->translate_manual($lang, $file, $key.'.'.$tkey, $value);
			}
		}

		// booleans, integers and floats don't need translation
		elseif (is_int($translation) or is_float($translation) or is_bool($translation))
		{
			// noop
		}

		// prompt the user
		else
		{
			\Cli::write(sprintf('Translation for: %s', \Fuel::clean_path($file)), 'light_green');
			\Cli::write(sprintf('Translation key: %s', str_replace('.', ' => ', $key)), 'white');
			\Cli::write(sprintf('Original "%s" value: %s', strtoupper($lang), $translation), 'white');
			$translation = \Cli::prompt(sprintf('Enter translation:', ), $translation);
		}

		// return the translated value
		return $translation;
	}

	/**
	 * Deepl translation, call the Deepl translation API to translate
	 *
	 * @param     string           language code being translated
	 * @param     string           name of the translation file
	 * @param     string           translation language key
	 * @param     string|array     translation value
	 *
	 * @return    string|array
	 */
	protected function translate_deepl($lang, $file, $key, $translation)
	{
		// recusrse on an array of values
		if (is_array($translation))
		{
			foreach ($translation as $tkey => $value)
			{
				$translation[$tkey] = $this->translate_deepl($lang, $file, $key.'.'.$tkey, $value);
			}
		}

		// booleans, integers and floats don't need translation
		elseif (is_int($translation) or is_float($translation) or is_bool($translation))
		{
			// noop
		}

		// call deepl
		else
		{
			// construct a curl object
			if (str_ends_with($this->apikey, ':fx'))
			{
				$curl = $this->curl('POST', 'https://api-free.deepl.com/v2/translate');
			}
			else
			{
				$curl = $this->curl('POST', 'https://api.deepl.com/v2/translate');
			}

			// we're sending json
			$curl->set_header('Content-Type', 'application/json');

			// enable auto format of the response
			$curl->set_auto_format(true);

			// add the apikey to the request
			$curl->set_header('Authorization', 'DeepL-Auth-Key '.$this->apikey);

			// add the payload
			$curl->set_params(json_encode((object) array(
				'text' => array($translation),
				'source_lang' => strtoupper($this->source),
				'target_lang' => strtoupper($this->target),
			), JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

			// and execute it
			try
			{
				$curl->execute();
			}
			// request failed?
			catch (\RequestStatusException | \RequestException $e)
			{
				//  too many requests?
				if ($e->getCode() == 429)
				{
					// retry after a delay
					sleep($this->delay++);
					return $this->translate_deepl($lang, $file, $key, $translation);
				}

				$result = json_validate($e->getMessage()) ? json_decode($e->getMessage()) : $e->getMessage();
				$this->write(sprintf('Deepl error %s: %s', $e->getCode(), isset($result->message) ? $result->message : $result), 'red');
				die();
			}

			// we need to have got an OK back
			if ($curl->response_info('http_code') != 200)
			{
				$this->write(sprintf('Received an unknown error %s.', $curl->response_info('http_code')), 'red');
				die();
			}

			// check if we got a valid response back
			$result = $curl->response()->body;
			if ( ! is_array($result) or ! array_key_exists('translations', $result) or count($result['translations']) != 1)
			{
				$this->write('Invalid response received from Deepl', 'red');
				var_dump($result);
				die();
			}

			$result = array_first($result['translations']);

			if ( ! array_key_exists('detected_source_language', $result) or $result['detected_source_language'] != strtoupper($this->source))
			{
				$this->write('No or incorrect source language in the Deepl response', 'red');
				var_dump($result);
				die();
			}

			\Cli::write(sprintf('Translation for: %s', \Fuel::clean_path($file)), 'light_green');
			\Cli::write(sprintf('Translation key: %s', str_replace('.', ' => ', $key)), 'white');
			\Cli::write(sprintf('Original "%s" value: %s', strtoupper($lang), $translation), 'white');
			\Cli::write(sprintf('Translated to: %s', $result['text']), 'white');

			// store the translation
			$translation = $result['text'];

			// try to prevent to-many-requests error
			sleep($this->delay);
		}

		// return the translated value
		return $translation;
	}

	/**
	 * Create a CURL request object
	 */
	protected function curl(string $method, string $url): \Request_Curl
	{
		// authenticate with the end point and request an access token
		$curl = \Request_Curl::forge(
			$url,
			array(),
			$method);

		// set specific cURL options
		$curl->set_options(array(
			\CURLOPT_RETURNTRANSFER => true,
			\CURLOPT_ENCODING => '',
			\CURLOPT_TIMEOUT => 60,
			\CURLOPT_FOLLOWLOCATION => true,
			\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_1_1,
		));

		// store and return the object
		return $this->lastreq = $curl;
	}

	/**
	 * Write a line, either to the console or to the logfile
	 *
	 * @param     string     line of text to be logged
	 * @param     string     optional, a valid color code for text written to the console
	 */
	protected function write($line, $color = null)
	{
		// write to the console
		\Cli::write($line, $color);

		// write to the log file too, if needed
		if ($this->log)
		{
			fwrite($this->log, $line.PHP_EOL);
		}

	}
}
