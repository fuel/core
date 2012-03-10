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
 * @deprecated  This should extend HttpException, but kept as Request404Exception for backwards compat.
 */
class HttpNotFoundException extends \Request404Exception
{
	public function response()
	{
		return new \Response(\View::forge('404'), 404);
	}
}

class HttpServerErrorException extends \HttpException
{
	public function response()
	{
		return new \Response(\View::forge('500'), 500);
	}
}
