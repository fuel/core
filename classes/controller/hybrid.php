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

/**
 * Hybrid Controller class
 *
 * A base controller that combines both templated and REST output
 *
 * @package   Fuel
 * @category  Core
 * @author    Fuel Development Team
 */
abstract class Controller_Hybrid extends \Controller_Rest
{

	/**
	* @var string page template
	*/
	public $template = 'template';

	/**
	 * Load the template and create the $this->template object
	 */
	public function before()
	{
		if ( ! $this->is_restful())
		{
			if ( ! empty($this->template) and is_string($this->template))
			{
				// Load the template
				$this->template = \View::forge($this->template);
			}
		}

		return parent::before();
	}

	/**
	 * After controller method has run output the template
	 *
	 * @param  Response  $response
	 */
	public function after($response)
	{
		if ( ! $this->is_restful())
		{
			// If nothing was returned default to the template
			if (empty($response))
			{
				$response = $this->template;
			}

			// If the response isn't a Response object, embed in the available one for BC
			// @deprecated  can be removed when $this->response is removed
			if ( ! $response instanceof Response)
			{
				$this->response->body = $response;
				$response = $this->response;
			}
		}

		return parent::after($response);
	}

	/**
	 * router
	 *
	 * requests are not made to methods directly The request will be for an "object".
	 * this simply maps the object and method to the correct Controller method.
	 *
	 * this router will call action methods for normal requests,
	 * and REST methods for RESTful calls
	 *
	 * @param  string
	 * @param  array
	 */
	public function router($resource, array $arguments)
	{
		// if this is an ajax call
		if ($this->is_restful())
		{
			// have the Controller_Rest router deal with it
			return parent::router($resource, $arguments);
		}

		// check if a input specific method exists
		$controller_method = strtolower(\Input::method()) . '_' . $resource;

		// fall back to action_ if no rest method is provided
		if ( ! method_exists($this, $controller_method))
		{
			$controller_method = 'action_'.$resource;
		}

		// check if the action method exists
		if (method_exists($this, $controller_method))
		{
			return call_user_func_array(array($this, $controller_method), $arguments);
		}

		// if not, we got ourselfs a genuine 404!
		throw new \HttpNotFoundException();
	}

	/**
	 * Response
	 *
	 * Takes pure data and optionally a status code, then creates the response
	 *
	 * @param   mixed
	 * @param   int
	 * @return  object  Response instance
	 */
	protected function response($data = array(), $http_status = null)
	{
		// if this is an ajax call
		if ($this->is_restful())
		{
			// have the Controller_Rest deal with it
			return parent::response($data, $http_status);
		}

		// not an ajax call, but it was an ajax method? convert the
		// data array into something that can be displayed properly
		if ( ! is_string($data))
		{
			// only dump the return value if it isn't already a string
			ob_start();
			var_dump($data);
			$result = ob_get_clean();
		}

		// and return it
		return html_entity_decode($result);
	}

	/**
	 * Decide whether to return RESTful or templated response
	 * Override in subclass to introduce custom switching logic.
	 *
	 * @param  boolean
	 */
	public function is_restful()
	{
		return \Input::is_ajax();
	}
}
