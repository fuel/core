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

return array(
	/**
	 * Log Settings.
	 */
	'driver' => 'file',

	// the index of custom levels MUST be up to 5
	'levels' => array(
		// 5 => 'Custom',
	),

	/**
	 * Logging Threshold.  Can be set to any of the following:
	 *
	 * Fuel::L_NONE
	 * Fuel::L_ERROR
	 * Fuel::L_WARNING
	 * Fuel::L_DEBUG
	 * Fuel::L_INFO
	 * Fuel::L_ALL
	 *
	 * ...also can be an array of levels:
	 *
	 * array(Fuel::L_ERROR, Fuel::L_INFO[, custom levels])
	 *
	 */
	'threshold' => Fuel::L_WARNING,

	// special settings for file driver
	'file' => array(
		'path' => APPPATH.'logs/',
		'date_format'  => 'Y-m-d H:i:s',
	),

	// special settings for db driver
	'db' => array(
		'table' => 'logs',
	)
);