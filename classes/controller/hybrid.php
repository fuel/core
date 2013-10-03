<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
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
	 * Load the template and create the $this->template object if needed
	 */
	public function before()
	{
		// setup the template if this isn't a RESTful call
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
	 * router
	 *
	 * this router will call action methods for normal requests,
	 * and REST methods for RESTful calls
	 *
	 * @param  string
	 * @param  array
	 */
	public function router($resource, $arguments)
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
			return call_fuel_func_array(array($this, $controller_method), $arguments);
		}

		// if not, we got ourselfs a genuine 404!
		throw new \HttpNotFoundException();
	}

	/**
	 * After controller method has run output the template
	 *
	 * @param  Response  $response
	 */
	public function after($response)
	{
		// return the template if no response is present and this isn't a RESTful call
		if ( ! $this->is_restful())
		{
			// do we have a response passed?
			if ($response === null)
			{
				// maybe one in the rest body?
				$response = $this->response->body;
				if ($response === null)
				{
					// fall back to the defined template
					$response = $this->template;
				}
			}

			if ( ! $response instanceof Response)
			{
				$response = \Response::forge($response, $this->response_status);
			}
		}

		return parent::after($response);
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
