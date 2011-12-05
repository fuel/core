<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
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
	 * @var  array  the required params to generate a url
	 */
	public $required_params = array();

	/**
	 * @var  array  any additional restrictions on the url
	 */
	public $url_restrictions = array();

	/**
	 * @var  array  the HTTP method this route requires
	 */
	public $required_method = null;

	/**
	 * @var  array  method params array
	 */
	public $method_params = array();

	/**
	 * @var  string  route path
	 */
	public $path = '';

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

	public function __construct($path, $translation = null)
	{
		$this->path = $path;
		$this->translation = ($translation === null) ? $path : $this->parse_translation($translation);
		$this->search = ($translation == stripslashes($path)) ? $path : $this->compile();
	}

	protected function parse_translation($translation)
	{
		$destination = $translation;

		if (!is_array($translation)) {
			return $translation;
		}

		//$translation is array of options
		foreach ($translation as $name=>$value) {
			if (is_numeric($name) && is_string($value)) {
				if (in_array(strtoupper($value), array('GET', 'POST', 'PUT', 'DELETE'))) {
					$this->required_method = strtoupper($value);
				} else {
					$destination = $value;
				}
			} elseif(is_numeric($name) && is_array($value)) {
				$this->url_restrictions = $value;
			}
		}
		return $destination;
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
			':alnum',
			':num',
			':alpha',
			':segment',
		), array(
			'.+',
			'[[:alnum:]]+',
			'[[:digit:]]+',
			'[[:alpha:]]+',
			'[^/]*',
		), $this->path);

		$required_parameters = array();
		$params_regexes = is_null($this->url_restrictions) ? array() : $this->url_restrictions;
		$callback = function ($match) use (&$required_parameters, $params_regexes)
		{
			static $used_names = array();

			$match = $match[1];
			//Leave the duplicates as-is
			if (array_key_exists($match, $used_names)) {
				return ":$match";
			}
			else {
				$used_names[$match] = true;
			}
			//Add the named parameter to required route parameters
			$required_parameters[] = $match;
			$regex = isset($params_regexes[$match]) ? $params_regexes[$match] : '.+';
			return sprintf('(?P<%s>'.$regex.'?)', $match);
		};
		$result = preg_replace_callback('#(?<!\[\[):([a-z\_]+)(?!:\]\])#uD', $callback, $search);
		$this->required_params = $required_parameters;
		return $result;
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
	 * @return	object  $this
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

		if (!is_null($route->required_method) && \Input::method() != $route->required_method) {
			return false;
		}

		if ($route->translation instanceof static) {
			$route->translation->search = $route->search;
			$result = $route->_parse_search($uri, $route->translation);
			return $result ? $result : false;
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


