<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
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

	// The default File_Area config
	'base_config' => array(

		/**
		 * Path to basedir restriction, null for no restriction
		 */
		'basedir'  => null,

		/**
		 * Array of allowed extensions, null for all
		 */
		'extensions'  => null,

		/**
		 * Base url for files, null for not available
		 */
		'url'  => null,

		/**
		 * Whether or not to use file locks when doing file operations
		 */
		'use_locks'  => null,

		/**
		 * array containing file driver per file extension
		 */
		'file_handlers'  => array(),
	),

	// Pre configure some areas
	'areas' => array(
		/* 'area_name' => array(
			'basedir'        => null,
			'extensions'     => null,
			'url'            => null,
			'use_locks'      => null,
			'file_handlers'  => array(),
		), */
	),

	// fileinfo() magic filename
	'magic_file' => null,

);


