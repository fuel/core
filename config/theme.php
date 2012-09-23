<?php

return array(
	/**
	 * The active theme to use.  This can also be set in code using Theme::active('foo');
	 */
	'active' => 'default',

	/**
	 * The fallback theme to use.  If a view is not found in the active theme, this theme
	 * is used as a fallback.  This can also be set in code using Theme::fallback('foo');
	 */
	'fallback' => 'default',

	/**
	 * The theme search paths.  They are searched in the order given.  You can add paths
	 * on the fly via Theme::add_path($path) or Theme::add_paths(array($path1, $path2));
	 */
	'paths' => array(
		DOCROOT.'themes',
	),

	/**
	 * The folder inside the theme to be used to store assets.  This is relative to the
	 * theme's path.
	 */
	'assets_folder' => 'assets',

	/**
	 * The extension for theme view files.
	 */
	'view_ext' => '.html',

	/**
	 * Whether to require a theme info file
	 */
	'require_info_file' => false,

	/**
	 * The theme info file name
	 */
	'info_file_name' => 'themeinfo.php',

	/**
	 * Use auto prefixing for modules
	 */
	'use_modules' => false,
);
