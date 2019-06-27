<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

import('phpquickprofiler/console', 'vendor');
import('phpquickprofiler/phpquickprofiler', 'vendor');

class Profiler
{
	protected static $profiler = null;

	protected static $query = null;

	public static function init()
	{
		if ( ! static::$profiler)
		{
			static::$profiler = new \PhpQuickProfiler(FUEL_START_TIME);
			static::$profiler->queries = array();
			static::$profiler->queryCount = 0;
			static::mark(__METHOD__.' Start');
			\Fuel::$profiling = true;
		}
	}

	public static function mark($label)
	{
		static::$profiler and \Console::logSpeed($label);
	}

	public static function mark_memory($var = false, $name = 'PHP')
	{
		static::$profiler and \Console::logMemory($var, $name);
	}

	public static function console($text)
	{
		static::$profiler and \Console::log($text);
	}

	public static function output($return = false)
	{
		return static::$profiler ? static::$profiler->display(static::$profiler, $return) : '';
	}

	public static function start($dbname, $sql, $stacktrace = array())
	{
		if (static::$profiler)
		{
			static::$query = array(
				'sql' => \Security::htmlentities($sql),
				'time' => static::$profiler->getMicroTime(),
				'stacktrace' => $stacktrace,
				'dbname' => $dbname,
			);
			return true;
		}
	}

	public static function stop($text)
	{
		if (static::$profiler)
		{
			static::$query['time'] = (static::$profiler->getMicroTime() - static::$query['time']) *1000;
			static::$profiler->queries[] = static::$query;
			static::$profiler->queryCount++;
		}
	}

	public static function delete($text)
	{
		static::$query = null;
	}

	public static function app_total()
	{
		return array(
			microtime(true) - FUEL_START_TIME,
			memory_get_peak_usage() - FUEL_START_MEM,
		);
	}
}
