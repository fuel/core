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

define('DS', DIRECTORY_SEPARATOR);
define('CRLF', chr(13).chr(10));

/**
 * Do we have access to mbstring?
 * We need this in order to work with UTF-8 strings
 */
if ( ! defined('MBSTRING'))
{
	// we do not support mb function overloading
	if (ini_get('mbstring.func_overload'))
	{
		die('Your PHP installation is configured to overload mbstring functions. This is not supported in FuelPHP!');
	}

	define('MBSTRING', function_exists('mb_get_info'));
}

// load the base functions
require COREPATH.'base.php';

// define the core classes to the autoloader
setup_autoloader();

// setup the composer autoloader
get_composer();

/**
 * Register all the error/shutdown handlers
 */
register_shutdown_function(function ()
{
	// reset the autoloader
	\Autoloader::_reset();

	// if we have sessions loaded, and native session emulation active
	if (\Config::get('session.native_emulation', false))
	{
		// close the name session
		session_id() and session_write_close();
	}

	// make sure we're having an output filter so we can display errors
	// occuring before the main config file is loaded
	\Config::get('security.output_filter', null) or \Config::set('security.output_filter', 'Security::htmlentities');

	try
	{
		// fire any app shutdown events
		\Event::instance()->trigger('shutdown', '', 'none', true);

		// fire any framework shutdown events
		\Event::instance()->trigger('fuel-shutdown', '', 'none', true);
	}
	catch (\Exception $e)
	{
		if (\Fuel::$is_cli)
		{
			\Cli::error("Error: ".$e->getMessage()." in ".$e->getFile()." on ".$e->getLine());
			\Cli::beep();
			exit(1);
		}
		else
		{
			logger(\Fuel::L_ERROR, 'shutdown - ' . $e->getMessage()." in ".$e->getFile()." on ".$e->getLine());
		}
	}
	return \Errorhandler::shutdown_handler();
});

set_exception_handler(function ($e)
{
	// reset the autoloader
	\Autoloader::_reset();

	// deal with PHP bugs #42098/#54054
	if ( ! class_exists('Errorhandler'))
	{
		include COREPATH.'classes/errorhandler.php';
		class_alias('\Fuel\Core\Errorhandler', 'Errorhandler');
		class_alias('\Fuel\Core\PhpErrorException', 'PhpErrorException');
	}

	return \Errorhandler::exception_handler($e);
});

set_error_handler(function ($severity, $message, $filepath, $line)
{
	// reset the autoloader
	\Autoloader::_reset();

	// deal with PHP bugs #42098/#54054
	if ( ! class_exists('Errorhandler'))
	{
		include COREPATH.'classes/errorhandler.php';
		class_alias('\Fuel\Core\Errorhandler', 'Errorhandler');
		class_alias('\Fuel\Core\PhpErrorException', 'PhpErrorException');
	}

	return \Errorhandler::error_handler($severity, $message, $filepath, $line);
});

function setup_autoloader()
{
	\Autoloader::add_namespace('Fuel\\Core', COREPATH.'classes/');

	\Autoloader::add_classes(array(
		'Fuel\\Core\\Agent'                            => COREPATH.'classes/agent.php',

		'Fuel\\Core\\Arr'                              => COREPATH.'classes/arr.php',

		'Fuel\\Core\\Asset'                            => COREPATH.'classes/asset.php',
		'Fuel\\Core\\Asset_Instance'                   => COREPATH.'classes/asset/instance.php',

		'Fuel\\Core\\Cache'                            => COREPATH.'classes/cache.php',
		'Fuel\\Core\\CacheNotFoundException'           => COREPATH.'classes/cache/notfound.php',
		'Fuel\\Core\\CacheExpiredException'            => COREPATH.'classes/cache.php',
		'Fuel\\Core\\Cache_Handler_Driver'             => COREPATH.'classes/cache/handler/driver.php',
		'Fuel\\Core\\Cache_Handler_Json'               => COREPATH.'classes/cache/handler/json.php',
		'Fuel\\Core\\Cache_Handler_Serialized'         => COREPATH.'classes/cache/handler/serialized.php',
		'Fuel\\Core\\Cache_Handler_String'             => COREPATH.'classes/cache/handler/string.php',
		'Fuel\\Core\\Cache_Storage_Driver'             => COREPATH.'classes/cache/storage/driver.php',
		'Fuel\\Core\\Cache_Storage_Apc'                => COREPATH.'classes/cache/storage/apc.php',
		'Fuel\\Core\\Cache_Storage_File'               => COREPATH.'classes/cache/storage/file.php',
		'Fuel\\Core\\Cache_Storage_Memcached'          => COREPATH.'classes/cache/storage/memcached.php',
		'Fuel\\Core\\Cache_Storage_Redis'              => COREPATH.'classes/cache/storage/redis.php',
		'Fuel\\Core\\Cache_Storage_Xcache'             => COREPATH.'classes/cache/storage/xcache.php',

		'Fuel\\Core\\Config'                           => COREPATH.'classes/config.php',
		'Fuel\\Core\\ConfigException'                  => COREPATH.'classes/config.php',
		'Fuel\\Core\\Config_Db'                        => COREPATH.'classes/config/db.php',
		'Fuel\\Core\\Config_File'                      => COREPATH.'classes/config/file.php',
		'Fuel\\Core\\Config_Ini'                       => COREPATH.'classes/config/ini.php',
		'Fuel\\Core\\Config_Json'                      => COREPATH.'classes/config/json.php',
		'Fuel\\Core\\Config_Interface'                 => COREPATH.'classes/config/interface.php',
		'Fuel\\Core\\Config_Php'                       => COREPATH.'classes/config/php.php',
		'Fuel\\Core\\Config_Yml'                       => COREPATH.'classes/config/yml.php',
		'Fuel\\Core\\Config_Memcached'                 => COREPATH.'classes/config/memcached.php',

		'Fuel\\Core\\Controller'                       => COREPATH.'classes/controller.php',
		'Fuel\\Core\\Controller_Rest'                  => COREPATH.'classes/controller/rest.php',
		'Fuel\\Core\\Controller_Template'              => COREPATH.'classes/controller/template.php',
		'Fuel\\Core\\Controller_Hybrid'                => COREPATH.'classes/controller/hybrid.php',

		'Fuel\\Core\\Cookie'                           => COREPATH.'classes/cookie.php',

		'Fuel\\Core\\DB'                               => COREPATH.'classes/db.php',
		'Fuel\\Core\\DBUtil'                           => COREPATH.'classes/dbutil.php',

		'Fuel\\Core\\Database_Connection'              => COREPATH.'classes/database/connection.php',
		'Fuel\\Core\\Database_Result'                  => COREPATH.'classes/database/result.php',
		'Fuel\\Core\\Database_Exception'               => COREPATH.'classes/database/exception.php',
		'Fuel\\Core\\Database_Expression'              => COREPATH.'classes/database/expression.php',
		// Generic Schema builder
		'Fuel\\Core\\Database_Schema'                  => COREPATH.'classes/database/schema.php',
		// Specific Schema builders
		// Generic Query builder
		'Fuel\\Core\\Database_Query'                   => COREPATH.'classes/database/query.php',
		'Fuel\\Core\\Database_Query_Builder'           => COREPATH.'classes/database/query/builder.php',
		'Fuel\\Core\\Database_Query_Builder_Insert'    => COREPATH.'classes/database/query/builder/insert.php',
		'Fuel\\Core\\Database_Query_Builder_Delete'    => COREPATH.'classes/database/query/builder/delete.php',
		'Fuel\\Core\\Database_Query_Builder_Update'    => COREPATH.'classes/database/query/builder/update.php',
		'Fuel\\Core\\Database_Query_Builder_Select'    => COREPATH.'classes/database/query/builder/select.php',
		'Fuel\\Core\\Database_Query_Builder_Where'     => COREPATH.'classes/database/query/builder/where.php',
		'Fuel\\Core\\Database_Query_Builder_Join'      => COREPATH.'classes/database/query/builder/join.php',
		// Specific Query builders
		'Fuel\\Core\\Database_SQLite_Builder_Delete'   => COREPATH.'classes/database/sqlite/builder/delete.php',
		'Fuel\\Core\\Database_SQLite_Builder_Update'   => COREPATH.'classes/database/sqlite/builder/update.php',
		// Generic PDO driver
		'Fuel\\Core\\Database_Pdo_Connection'          => COREPATH.'classes/database/pdo/connection.php',
		'Fuel\\Core\\Database_Pdo_Result'              => COREPATH.'classes/database/pdo/result.php',
		'Fuel\\Core\\Database_Pdo_Cached'              => COREPATH.'classes/database/pdo/cached.php',
		// Platform specific PDO drivers
		'Fuel\\Core\\Database_MySQL_Connection'        => COREPATH.'classes/database/mysql/connection.php',
		'Fuel\\Core\\Database_SQLite_Connection'       => COREPATH.'classes/database/sqlite/connection.php',
		'Fuel\\Core\\Database_Sqlsrv_Connection'       => COREPATH.'classes/database/sqlsrv/connection.php',
		'Fuel\\Core\\Database_Dblib_Connection'        => COREPATH.'classes/database/dblib/connection.php',
		// Legacy MySQL driver
		'Fuel\\Core\\Database_MySQLi_Connection'       => COREPATH.'classes/database/mysqli/connection.php',
		'Fuel\\Core\\Database_MySQLi_Result'           => COREPATH.'classes/database/mysqli/result.php',
		'Fuel\\Core\\Database_MySQLi_Cached'           => COREPATH.'classes/database/mysqli/cached.php',

		'Fuel\\Core\\Fuel'                             => COREPATH.'classes/fuel.php',
		'Fuel\\Core\\FuelException'                    => COREPATH.'classes/fuel.php',

		'Fuel\\Core\\Finder'                           => COREPATH.'classes/finder.php',

		'Fuel\\Core\\Date'                             => COREPATH.'classes/date.php',

		'Fuel\\Core\\Debug'                            => COREPATH.'classes/debug.php',

		'Fuel\\Core\\Cli'                              => COREPATH.'classes/cli.php',

		'Fuel\\Core\\Crypt'                            => COREPATH.'classes/crypt.php',

		'Fuel\\Core\\Event'                            => COREPATH.'classes/event.php',
		'Fuel\\Core\\Event_Instance'                   => COREPATH.'classes/event/instance.php',

		'Fuel\\Core\\Errorhandler'                     => COREPATH.'classes/errorhandler.php',
		'Fuel\\Core\\PhpErrorException'                => COREPATH.'classes/errorhandler.php',

		'Fuel\\Core\\Format'                           => COREPATH.'classes/format.php',

		'Fuel\\Core\\Fieldset'                         => COREPATH.'classes/fieldset.php',
		'Fuel\\Core\\Fieldset_Field'                   => COREPATH.'classes/fieldset/field.php',

		'Fuel\\Core\\File'                             => COREPATH.'classes/file.php',
		'Fuel\\Core\\FileAccessException'              => COREPATH.'classes/file.php',
		'Fuel\\Core\\OutsideAreaException'             => COREPATH.'classes/file.php',
		'Fuel\\Core\\InvalidPathException'             => COREPATH.'classes/file.php',
		'Fuel\\Core\\File_Area'                        => COREPATH.'classes/file/area.php',
		'Fuel\\Core\\File_Handler_File'                => COREPATH.'classes/file/handler/file.php',
		'Fuel\\Core\\File_Handler_Directory'           => COREPATH.'classes/file/handler/directory.php',

		'Fuel\\Core\\Form'                             => COREPATH.'classes/form.php',
		'Fuel\\Core\\Form_Instance'                    => COREPATH.'classes/form/instance.php',

		'Fuel\\Core\\Ftp'                              => COREPATH.'classes/ftp.php',
		'Fuel\\Core\\FtpConnectionException'           => COREPATH.'classes/ftp.php',
		'Fuel\\Core\\FtpFileAccessException'           => COREPATH.'classes/ftp.php',

		'Fuel\\Core\\HttpException'                    => COREPATH.'classes/httpexception.php',
		'Fuel\\Core\\HttpBadRequestException'          => COREPATH.'classes/httpexceptions.php',
		'Fuel\\Core\\HttpNoAccessException'            => COREPATH.'classes/httpexceptions.php',
		'Fuel\\Core\\HttpNotFoundException'            => COREPATH.'classes/httpexceptions.php',
		'Fuel\\Core\\HttpServerErrorException'         => COREPATH.'classes/httpexceptions.php',

		'Fuel\\Core\\Html'                             => COREPATH.'classes/html.php',

		'Fuel\\Core\\Image'                            => COREPATH.'classes/image.php',
		'Fuel\\Core\\Image_Driver'                     => COREPATH.'classes/image/driver.php',
		'Fuel\\Core\\Image_Gd'                         => COREPATH.'classes/image/gd.php',
		'Fuel\\Core\\Image_Imagemagick'                => COREPATH.'classes/image/imagemagick.php',
		'Fuel\\Core\\Image_Imagick'                    => COREPATH.'classes/image/imagick.php',

		'Fuel\\Core\\Inflector'                        => COREPATH.'classes/inflector.php',

		'Fuel\\Core\\Input'                            => COREPATH.'classes/input.php',
		'Fuel\\Core\\Input_Instance'                   => COREPATH.'classes/input/instance.php',

		'Fuel\\Core\\Lang'                             => COREPATH.'classes/lang.php',
		'Fuel\\Core\\LangException'                    => COREPATH.'classes/lang.php',
		'Fuel\\Core\\Lang_Db'                          => COREPATH.'classes/lang/db.php',
		'Fuel\\Core\\Lang_File'                        => COREPATH.'classes/lang/file.php',
		'Fuel\\Core\\Lang_Ini'                         => COREPATH.'classes/lang/ini.php',
		'Fuel\\Core\\Lang_Json'                        => COREPATH.'classes/lang/json.php',
		'Fuel\\Core\\Lang_Interface'                   => COREPATH.'classes/lang/interface.php',
		'Fuel\\Core\\Lang_Php'                         => COREPATH.'classes/lang/php.php',
		'Fuel\\Core\\Lang_Yml'                         => COREPATH.'classes/lang/yml.php',

		'Fuel\\Core\\Log'                              => COREPATH.'classes/log.php',

		'Fuel\\Core\\Markdown'                         => COREPATH.'classes/markdown.php',

		'Fuel\\Core\\Migrate'                          => COREPATH.'classes/migrate.php',

		'Fuel\\Core\\Model'                            => COREPATH.'classes/model.php',
		'Fuel\\Core\\Model_Crud'                       => COREPATH.'classes/model/crud.php',

		'Fuel\\Core\\Module'                           => COREPATH.'classes/module.php',
		'Fuel\\Core\\ModuleNotFoundException'          => COREPATH.'classes/module.php',

		'Fuel\\Core\\Mongo_Db'                         => COREPATH.'classes/mongo/db.php',
		'Fuel\\Core\\Mongo_DbException'                => COREPATH.'classes/mongo/db.php',

		'Fuel\\Core\\Output'                           => COREPATH.'classes/output.php',

		'Fuel\\Core\\Package'                          => COREPATH.'classes/package.php',
		'Fuel\\Core\\PackageNotFoundException'         => COREPATH.'classes/package.php',

		'Fuel\\Core\\Pagination'                       => COREPATH.'classes/pagination.php',

		'Fuel\\Core\\Presenter'                        => COREPATH.'classes/presenter.php',

		'Fuel\\Core\\Profiler'                         => COREPATH.'classes/profiler.php',

		'Fuel\\Core\\Request'                          => COREPATH.'classes/request.php',
		'Fuel\\Core\\Request_Driver'                   => COREPATH.'classes/request/driver.php',
		'Fuel\\Core\\RequestException'                 => COREPATH.'classes/request/driver.php',
		'Fuel\\Core\\RequestStatusException'           => COREPATH.'classes/request/driver.php',
		'Fuel\\Core\\Request_Curl'                     => COREPATH.'classes/request/curl.php',
		'Fuel\\Core\\Request_Soap'                     => COREPATH.'classes/request/soap.php',

		'Fuel\\Core\\Redis_Db'                         => COREPATH.'classes/redis/db.php',
		'Fuel\\Core\\RedisException'                   => COREPATH.'classes/redis/db.php',

		'Fuel\\Core\\Response'                         => COREPATH.'classes/response.php',

		'Fuel\\Core\\Route'                            => COREPATH.'classes/route.php',
		'Fuel\\Core\\Router'                           => COREPATH.'classes/router.php',

		'Fuel\\Core\\Sanitization'                     => COREPATH.'classes/sanitization.php',

		'Fuel\\Core\\Security'                         => COREPATH.'classes/security.php',
		'Fuel\\Core\\SecurityException'                => COREPATH.'classes/security.php',

		'Fuel\\Core\\Session'                          => COREPATH.'classes/session.php',
		'Fuel\\Core\\Session_Driver'                   => COREPATH.'classes/session/driver.php',
		'Fuel\\Core\\Session_Db'                       => COREPATH.'classes/session/db.php',
		'Fuel\\Core\\Session_Cookie'                   => COREPATH.'classes/session/cookie.php',
		'Fuel\\Core\\Session_File'                     => COREPATH.'classes/session/file.php',
		'Fuel\\Core\\Session_Memcached'                => COREPATH.'classes/session/memcached.php',
		'Fuel\\Core\\Session_Redis'                    => COREPATH.'classes/session/redis.php',
		'Fuel\\Core\\Session_Exception'                => COREPATH.'classes/session/exception.php',

		'Fuel\\Core\\Num'                              => COREPATH.'classes/num.php',

		'Fuel\\Core\\Str'                              => COREPATH.'classes/str.php',

		'Fuel\\Core\\TestCase'                         => COREPATH.'classes/testcase.php',

		'Fuel\\Core\\Theme'                            => COREPATH.'classes/theme.php',
		'Fuel\\Core\\ThemeException'                   => COREPATH.'classes/theme.php',

		'Fuel\\Core\\Uri'                              => COREPATH.'classes/uri.php',

		'Fuel\\Core\\Unzip'                            => COREPATH.'classes/unzip.php',

		'Fuel\\Core\\Upload'                           => COREPATH.'classes/upload.php',

		'Fuel\\Core\\Validation'                       => COREPATH.'classes/validation.php',
		'Fuel\\Core\\Validation_Error'                 => COREPATH.'classes/validation/error.php',

		'Fuel\\Core\\View'                             => COREPATH.'classes/view.php',
		'Fuel\\Core\\Viewmodel'                        => COREPATH.'classes/viewmodel.php',
	));
};

function get_composer()
{
	// storage for the composer autoloader
	static $composer;

	// load composer
	if ( ! $composer)
	{
	 // load the Composer autoloader if present
		defined('VENDORPATH') or define('VENDORPATH', realpath(COREPATH.'..'.DS.'vendor').DS);
		if ( ! is_file(VENDORPATH.'autoload.php'))
		{
			die('Composer is not installed. Please run "php composer.phar update" in the root to install Composer');
		}
		$composer = require(VENDORPATH.'autoload.php');
	}

	return $composer;
}
