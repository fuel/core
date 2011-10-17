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


abstract class HttpException extends FuelException
{
	abstract public function handle();
}


class HttpNotFoundException extends \HttpException
{
	/**
	 * When this type of exception isn't caught this method is called by
	 * Error::exception_handler() to deal with the problem.
	 */
	public function handle()
	{
		$response = new \Response(\View::forge('404'), 404);
		\Event::shutdown();
		$response->send(true);
	}
}

class HttpServerErrorException extends \HttpException
{
	/**
	 * When this type of exception isn't caught this method is called by
	 * Error::exception_handler() to deal with the problem.
	 */
	public function handle()
	{
		$response = new \Response(\View::forge('500'), 500);
		\Event::shutdown();
		$response->send(true);
	}
}
