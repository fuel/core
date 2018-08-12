<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  [!] NOTICE
 * -----------------------------------------------------------------------------
 *
 *  If you need to make modifications to the default configuration,
 *  copy this file to your 'app/config' folder, and make them in there.
 *
 *  This will allow you to upgrade FuelPHP without losing your custom config.
 *
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Patterns
	 * -------------------------------------------------------------------------
	 *
	 *  A couple of named patterns that are often used.
	 *
	 */

	'patterns' => array(
		'local' => '%c',

		'mysql'		 => '%Y-%m-%d %H:%M:%S',
		'mysql_date' => '%Y-%m-%d',

		'us'       => '%m/%d/%Y',
		'us_short' => '%m/%d',
		'us_named' => '%B %d %Y',
		'us_full'  => '%I:%M %p, %B %d %Y',

		'eu'       => '%d/%m/%Y',
		'eu_short' => '%d/%m',
		'eu_named' => '%d %B %Y',
		'eu_full'  => '%H:%M, %d %B %Y',

		'24h' => '%H:%M',
		'12h' => '%I:%M %p',
	),
);
