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
 * Template Controller class
 *
 * A base controller for easily creating templated output.
 *
 * @package		Fuel
 * @category	Core
 * @author		Fuel Development Team
 */
abstract class Controller_Template extends \Controller
{

	/**
	* @var string page template
	*/
	public $template = 'template';

	/**
	* @var boolean auto render template
	**/
	public $auto_render = true;

	// Load the template and create the $this->template object
	public function before()
	{
		if ($this->auto_render === true)
		{
			// Load the template
			$this->template = \View::forge($this->template);
		}

		return parent::before();
	}

	// After controller method has run output the template
	public function after($response)
	{
		// If the response is a Response object, we don't want to create a new one
		if ($this->auto_render === true and ! $response instanceof \Response)
		{
			$response = $this->response;
			$response->body = $this->template;
		}

		return parent::after($response);
	}

}
