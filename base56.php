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

/**
 * Faster equivalent of call_user_func_array using variadics
 */
if ( ! function_exists('call_fuel_func_array'))
{
	function call_fuel_func_array($callback, array $args)
	{
		// deal with "class::method" syntax
		if (is_string($callback) and strpos($callback, '::') !== false)
		{
			$callback = explode('::', $callback);
		}

		// dynamic call on an object?
		if (is_array($callback) and isset($callback[1]) and is_object($callback[0]))
		{
			// make sure our arguments array is indexed
			if ($count = count($args))
			{
				$args = array_values($args);
			}

			list($instance, $method) = $callback;

			return $instance->{$method}(...$args);
		}

		// static call?
		elseif (is_array($callback) and isset($callback[1]) and is_string($callback[0]))
		{
			list($class, $method) = $callback;
			$class = '\\'.ltrim($class, '\\');

			return $class::{$method}(...$args);
		}

		// if it's a string, it's a native function or a static method call
		elseif (is_string($callback) or $callback instanceOf \Closure)
		{
			is_string($callback) and $callback = ltrim($callback, '\\');
		}

		return $callback(...$args);
	}
}
