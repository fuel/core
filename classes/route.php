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

class Route
{
	/**
	 * @var  array  segments array
	 */
	public $segments = array();

	/**
	 * @var  array  named params array
	 */
	public $named_params = array();

	/**
	 * @var  array  method params array
	 */
	public $method_params = array();

	/**
	 * @var  string  route path
	 */
	public $path = '';

	/**
	 * @var  boolean  route case match behaviour
	 */
	public $case_sensitive = false;

	/**
	 * @var  boolean  whether to strip the extension from the URI
	 */
	public $strip_extension = true;

	/**
	 * @var  string  route name
	 */
	public $name = null;

	/**
	 * @var  string  route module
	 */
	public $module = null;

	/**
	 * @var  string  route directory
	 */
	public $directory = null;

	/**
	 * @var  string  controller name
	 */
	public $controller = null;

	/**
	 * @var  string  controller path
	 */
	public $controller_path = null;
	/**
	 * @var  string  default controller action
	 */
	public $action = 'index';

	/**
	 * @var  mixed  route translation
	 */
	public $translation = null;

	/**
	 * @var  closure
	 */
	public $callable = null;

	/**
	 * @var  mixed  the compiled route regex
	 */
	protected $search = null;

	public function __construct($path, $translation = null, $case_sensitive = null, $strip_extension = null, $name = null)
	{
		$this->path = $path;
		$this->translation = ($translation === null) ? $path : $translation;
		$this->search = ($translation == stripslashes($path)) ? $path : $this->compile();
		$this->case_sensitive = ($case_sensitive === null) ? \Config::get('routing.case_sensitive', true) : $case_sensitive;
		$this->strip_extension = ($strip_extension === null) ? \Config::get('routing.strip_extension', true) : $strip_extension;
		$this->name = $name;
	}

	/**
	 * Compiles a route. Replaces named params and regex shortcuts.
	 *
	 * @return  string  compiled route.
	 */
	protected function compile()
	{
		if ($this->path === '_root_')
		{
			return '';
		}

		$search = str_replace(array(
			':any',
			':everything',
			':alnum',
			':num',
			':alpha',
			':segment',
		), array(
			'.+',
			'.*',
			'[[:alnum:]]+',
			'[[:digit:]]+',
			'[[:alpha:]]+',
			'[^/]*',
		), $this->path);

		return preg_replace('#(?<!\[\[):([a-z\_]+)(?!:\]\])#uD', '(?P<$1>.+?)', $search);
	}

	/**
	 * Attempts to find the correct route for the given URI
	 *
	 * @param	\Request	$request  The URI object
	 * @return	array
	 */
	public function parse(\Request $request)
	{
		$uri = $request->uri->get();
		$method = $request->get_method();

		if ($uri === '' and $this->path === '_root_')
		{
			return $this->matched();
		}

		$result = $this->_parse_search($uri, null, $method);

		if ($result)
		{
			return $result;
		}

		return false;
	}

	/**
	 * Parses a route match and returns the controller, action and params.
	 *
	 * @param   string  $uri           The matched route
	 * @param   array   $named_params  Named parameters
	 * @return  object  $this
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
				// strip the extension if needed and there is something to strip
				if ($this->strip_extension and strrchr($uri, '.') == $ext = '.'.\Input::extension())
				{
					if ($this->strip_extension === true or (is_array($this->strip_extension) and in_array($ext, $this->strip_extension)))
					{
						$uri = substr($uri, 0, -(strlen($ext)));
					}
				}

				if ($this->case_sensitive)
				{
					$path = preg_replace('#^'.$this->search.'$#uD', $this->translation, $uri);
				}
				else
				{
					$path = preg_replace('#^'.$this->search.'$#uiD', $this->translation, $uri);
				}
			}

			$this->segments = explode('/', trim($path, '/'));
		}

		return $this;
	}

	/**
	 * Parses an actual route - extracted out of parse() to make it recursive.
	 *
	 * @param   string  $uri     The URI object
	 * @param   object  $route   route object
	 * @param   string  $method  request method
	 * @return  array|boolean
	 */
	protected function _parse_search($uri, $route = null, $method = null)
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

				$protocol = isset($r[2]) ? ($r[2] ? 'https' : 'http') : false;

				if (($protocol === false or $protocol == \Input::protocol()) and $method == strtoupper($verb))
				{
					$r[1]->search = $route->search;
					$result = $route->_parse_search($uri, $r[1], $method);

					if ($result)
					{
						return $result;
					}
				}
			}

			return false;
		}

		if ($this->case_sensitive)
		{
			$result = preg_match('#^'.$route->search.'$#uD', $uri, $params);
		}
		else
		{
			$result = preg_match('#^'.$route->search.'$#uiD', $uri, $params);
		}

		if ($result === 1)
		{
			return $route->matched($uri, $params);
		}
		else
		{
			return false;
		}
	}
}
