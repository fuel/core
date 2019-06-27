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
	 *  Base Configurations
	 * -------------------------------------------------------------------------
	 *
	 *  The default 'File_Area' configurations.
	 *
	 */

	'base_config' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Basedir
		 * ---------------------------------------------------------------------
		 *
		 *  Path to 'basedir' restriction. Set to null for no restriction.
		 *
		 */

		'basedir' => null,

		/**
		 * ---------------------------------------------------------------------
		 *  Extensions
		 * ---------------------------------------------------------------------
		 *
		 *  Allowed extensions. Set to null for allow all extensions.
		 *
		 */

		'extensions' => null,

		/**
		 * ---------------------------------------------------------------------
		 *  URL
		 * ---------------------------------------------------------------------
		 *
		 *  Base URL for files. Set to null to make it unavailable.
		 *
		 */

		'url' => null,

		/**
		 * ---------------------------------------------------------------------
		 *  File Lock
		 * ---------------------------------------------------------------------
		 *
		 *  Whether or not to use file locks when doing file operations.
		 *
		 */

		'use_locks' => null,

		/**
		 * ---------------------------------------------------------------------
		 *  File Handler
		 * ---------------------------------------------------------------------
		 *
		 *  File driver per file extension.
		 *
		 */

		'file_handlers' => array(),
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Areas
	 * -------------------------------------------------------------------------
	 *
	 *  Pre-configure some areas.
	 *
	 *  Use these examples to enable:
	 *
	 *      'area_name' => array(
	 *          'basedir'       => null,
	 *          'extensions'    => null,
	 *          'url'           => null,
	 *          'use_locks'     => null,
	 *          'file_handlers' => array(),
	 *      )
	 *
	 */

	'areas' => array(),

	/**
	 * -------------------------------------------------------------------------
	 *  Magic File
	 * -------------------------------------------------------------------------
	 *
	 *  The 'fileinfo()' magic filename.
	 *
	 */

	'magic_file' => null,

	/**
	 * -------------------------------------------------------------------------
	 *  Permissions
	 * -------------------------------------------------------------------------
	 *
	 *  Default file and directory permissions.
	 *
	 */

	'chmod' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Files
		 * ---------------------------------------------------------------------
		 *
		 *  Permissions for newly created files.
		 *
		 */

		'files' => 0666,

		/**
		 * ---------------------------------------------------------------------
		 *  Folders
		 * ---------------------------------------------------------------------
		 *
		 *  Permissions for newly created folders.
		 *
		 */

		'folders' => 0777,
	),
);

