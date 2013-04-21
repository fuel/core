<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.6
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

abstract class Controller
{

	/**
	 * @var  Request  The current Request object
	 */
	public $request;

	/**
	 * Sets the controller request object.
	 *
	 * @param   Request   The current request object
	 */
	public function __construct(\Request $request)
	{
		$this->request = $request;
	}

	/**
	 * This method gets called before the action is called
	 */
	public function before() {}

	/**
	 * Router
	 *
	 * Requests are not made to methods directly The request will be for an "object".
	 * this simply maps the object and method to the correct Controller method.
	 *
	 * @param  string
	 * @param  array
	 */
	public function router($resource, $arguments)
	{
		// If they call user, go to $this->post_user();
		$controller_method = strtolower(\Input::method()) . '_' . $resource;

		// Fall back to action_ if no HTTP request method based method exists
		if ( ! method_exists($this, $controller_method))
		{
			$controller_method = 'action_'.$resource;
		}

		// If method is not available, throw an HttpNotFound Exception
		if (method_exists($this, $controller_method))
		{
			return call_user_func_array(array($this, $controller_method), $arguments);
		}
		else
		{
			throw new \HttpNotFoundException();
		}
	}

	/**
	 * This method gets called after the action is called
	 */
	public function after($response)
	{
		// Make sure the $response is a Response object
		if ( ! $response instanceof Response)
		{
			$response = \Response::forge($response);
		}

		return $response;
	}

	/**
	 * This method returns the named parameter requested, or all of them
	 * if no parameter is given.
	 *
	 * @param   string  $param    The name of the parameter
	 * @param   mixed   $default  Default value
	 * @return  mixed
	 */
	public function param($param, $default = null)
	{
		return $this->request->param($param, $default);
	}

	/**
	 * This method returns all of the named parameters.
	 *
	 * @return  array
	 */
	public function params()
	{
		return $this->request->params();
	}
}

