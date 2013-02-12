<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.5
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Template Controller class
 *
 * A base controller for easily creating templated output.
 *
 * @package   Fuel
 * @category  Core
 * @author    Fuel Development Team
 */
abstract class Controller_Template extends \Controller
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
		if ( ! empty($this->template) and is_string($this->template))
		{
			// Load the template
			$this->template = \View::forge($this->template);
		}

		return parent::before();
	}

	/**
	 * Router
	 *
	 * Requests are not made to methods directly The request will be for an "object".
	 * this simply maps the object and method to the correct Controller method.
	 *
	 * @param  string
	 * @param  array
	 */
	public function router($resource, array $arguments)
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
	 * After controller method has run output the template
	 *
	 * @param  Response  $response
	 */
	public function after($response)
	{
		// If nothing was returned default to the template
		if (empty($response))
		{
			$response = $this->template;
		}

		return parent::after($response);
	}

}
