<?php

namespace Fuel\Core;

import('phpquickprofiler/phpquickprofiler', 'vendor');

use \Console;
use \PhpQuickProfiler;

class Profiler {

	protected static $profiler = null;

	protected static $query = null;

	public static function init()
	{
		static::$profiler = new PhpQuickProfiler(FUEL_START_TIME);
		static::$profiler->queries = array();
	}

	public static function mark($label)
	{
		Console::logSpeed($label);
	}

	public static function mark_memory($label)
	{
		Console::logMemory($label);
	}

	public static function console($text)
	{
		Console::log($test);
	}

	public static function output()
	{
		return static::$profiler->display(static::$profiler);
	}

	public static function start($dbname, $sql)
	{
		static::$query = array(
			'sql' => $sql,
			'time' => static::$profiler->getMicroTime(),
		);
		return true;
	}

	public static function stop($text)
	{
		static::$query['time'] = static::$profiler->getMicroTime() - static::$query['time'];
		array_push(static::$profiler->queries, static::$query);
		static::$profiler->queryCount++;
	}

	public static function delete($text)
	{
		static::$query = null;
	}

	public static function app_total()
	{
		return array(
			microtime(true) - FUEL_START_TIME,
			memory_get_usage() - FUEL_START_MEM
		);
	}
}
