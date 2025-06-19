<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class HttpBadRequestException extends HttpException
{
	public function response()
	{
		return new \Response(\View::forge('400')->set('message', $this->getMessage()), 400);
	}
}

class HttpNoAccessException extends HttpException
{
	public function response()
	{
		return new \Response(\View::forge('403')->set('message', $this->getMessage()), 403);
	}
}

class HttpNotFoundException extends HttpException
{
	public function response()
	{
		return new \Response(\View::forge('404')->set('message', $this->getMessage()), 404);
	}
}

class HttpServerErrorException extends HttpException
{
	public function response()
	{
		return new \Response(\View::forge('500')->set('message', $this->getMessage()), 500);
	}
}
