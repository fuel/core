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

class Route {

	public $segments = array();

	public $named_params = array();

	public $method_params = array();

	public $path = '';

	public $module = null;

	public $directory = null;

	public $controller = null;

	public $action = 'index';

	public $translation = null;

	public $callable = null;

	protected $search = null;

	protected $reverse_search = null;

	protected $reverse_translation = null;

	public function __construct($path, $translation = null)
	{
		$this->path = $path;
		$this->translation = ($translation === null) ? $path : $translation;
		$this->search = ($translation == stripslashes($path)) ? $path : $this->compile($this->path);
		list ($this->reverse_search, $this->reverse_translation) = $this->compile_reverse($this->path);
	}

	protected function compile($path)
	{
		if ($path === '_root_')
		{
			return '';
		}

		$search = str_replace(array(':any', ':segment'), array('.+', '[^/]*'), $path);
		return preg_replace('|:([a-z\_]+)|uD', '(?P<$1>.+?)', $search);
	}

	protected function compile_reverse($path)
	{
		if ($path === '_root_')
		{
			return array('', '');
		}

		// Go through path, and every time we find :any or :secment, $<num> in $translation
		// Construct $translation, which is later compiled down to reverse_search.
		// Go through $path, and every time we find :and or :segment, replace
		// $<num> with this in $translation (taking num in incrementing order).

		// Also construct $reverse_translation.
		// Every time we find :any or :segment in $path, replace it with the
		// corresponding $<num> from $this->translation.

		// The net result is $this->reverse_search, which is used to match against
		// a requested reverse route, and $this->reverse_translation, which is the
		// path this is translated into.

		preg_match_all('/\(:any\)|\(:segment\)/', $path, $matches);
		preg_match_all('/\$\d+/', $this->translation, $translation_matches);

		$translation = $this->translation;
		$reverse_translation = $this->path;
		foreach ($matches[0] as $i => $match)
		{
			$translation_search = '$'.($i+1);
			$translation = str_replace($translation_search, $match, $translation);
			$reverse_translation = substr_replace($reverse_translation, $translation_matches[0][$i],
					strpos($reverse_translation, $match), strlen($match));
		}

		return array($this->compile($translation), $reverse_translation);
	}

	/**
	 * Given a path, sees whether this route could point towards that path.
	 * If true, construct the url which would point towards the given path.
	 * If false, return false
	 *
	 * @access public
	 * @param string $path	The path to check
	 * @return string|false	The uri which point to the path if we do match, false if not
	 */
	public function match_reverse($path)
	{
		if ($this->reverse_search == '')
			return false;

		$uri = preg_replace('#^'.$this->reverse_search.'$#uD', $this->reverse_translation, $path, -1, $count);
		return $count ? $uri : false;
	}

	/**
	 * Attempts to find the correct route for the given URI
	 *
	 * @access	public
	 * @param	object	The URI object
	 * @return	array
	 */
	public function parse(\Request $request)
	{
		$uri = $request->uri->get();

		if ($uri === '' and $this->path === '_root_')
		{
			return $this->matched();
		}

		$result = $this->_parse_search($uri);

		if ($result)
		{
			return $result;
		}

		return false;
	}

	/**
	 * Parses a route match and returns the controller, action and params.
	 *
	 * @access	public
	 * @param	string	The matched route
	 * @return	array
	 */
	public function matched($uri = '', $named_params = array())
	{
		// Clean out all the non-named stuff out of $named_params
		foreach($named_params as $key => $val)
		{
			if (is_numeric($key))
			{
				unset($named_params[$key]);
			}
		}

		$this->named_params = $named_params;

		if ($this->translation instanceof \Closure)
		{
			$this->callable = $this->translation;
		}
		else
		{
			$path = $this->translation;

			if ($uri != '')
			{
				$path = preg_replace('#^'.$this->search.'$#uD', $this->translation, $uri);
			}

			$this->segments = explode('/', trim($path, '/'));
		}

		return $this;
	}

	/**
	 * Parses an actual route - extracted out of parse() to make it recursive.
	 *
	 * @param   string  The URI object
	 * @return  array|boolean
	 */
	protected function _parse_search($uri, $route = null)
	{
		if ($route === null)
		{
			$route = $this;
		}

		if (is_array($route->translation))
		{
			foreach ($route->translation as $r)
			{
				$verb = $r[0];

				if (\Input::method() == strtoupper($verb))
				{
					$r[1]->search = $route->search;
					$result = $route->_parse_search($uri, $r[1]);

					if ($result)
					{
						return $result;
					}
				}
			}

			return false;
		}

		if (preg_match('#^'.$route->search.'$#uD', $uri, $params) != false)
		{
			return $route->matched($uri, $params);
		}
		else
		{
			return false;
		}
	}
}


