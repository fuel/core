<?php
/**
 * Part of the Fuel framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 */

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */


return array(

	/*
	| Which version of the schema should be considered "current"
	|
	|	Default: 0
	|
	*/
	'version' => 0,

	/*
	| Where are these migrations stored?
	|
	|	Default: APPPATH.'migrations/'
	|
	*/
	'path' => APPPATH.'migrations/',

	/*
	| Table name
	|
	|	Default: 'migration'
	|
	*/
	'table' => 'migration',

);


