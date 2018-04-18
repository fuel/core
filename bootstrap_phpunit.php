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
 * Set error reporting and display errors settings.  You will want to change these when in production.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$app_path     = rtrim($_SERVER['app_path'], '/').'/';
$package_path = rtrim($_SERVER['package_path'], '/').'/';
$vendor_path  = rtrim($_SERVER['vendor_path'], '/').'/';
$core_path    = rtrim($_SERVER['core_path'], '/').'/';

/**
 * Website docroot
 */
define('DOCROOT', realpath(__DIR__.DIRECTORY_SEPARATOR.$_SERVER['doc_root']).DIRECTORY_SEPARATOR);

( ! is_dir($app_path) and is_dir(DOCROOT.$app_path)) and $app_path = DOCROOT.$app_path;
( ! is_dir($core_path) and is_dir(DOCROOT.$core_path)) and $core_path = DOCROOT.$core_path;
( ! is_dir($vendor_path) and is_dir(DOCROOT.$vendor_path)) and $vendor_path = DOCROOT.$vendor_path;
( ! is_dir($package_path) and is_dir(DOCROOT.$package_path)) and $package_path = DOCROOT.$package_path;

define('APPPATH', realpath($app_path).DIRECTORY_SEPARATOR);
define('PKGPATH', realpath($package_path).DIRECTORY_SEPARATOR);
define('VENDORPATH', realpath($vendor_path).DIRECTORY_SEPARATOR);
define('COREPATH', realpath($core_path).DIRECTORY_SEPARATOR);

unset($app_path, $core_path, $package_path, $_SERVER['app_path'], $_SERVER['core_path'], $_SERVER['package_path']);

// Get the start time and memory for use later
defined('FUEL_START_TIME') or define('FUEL_START_TIME', microtime(true));
defined('FUEL_START_MEM') or define('FUEL_START_MEM', memory_get_usage());

// Load the Composer autoloader if present
defined('VENDORPATH') or define('VENDORPATH', realpath(COREPATH.'..'.DS.'vendor').DS);
if ( ! is_file(VENDORPATH.'autoload.php'))
{
	die('Composer is not installed. Please run "php composer.phar update" in the project root to install Composer');
}
require VENDORPATH.'autoload.php';

if (class_exists('AspectMock\Kernel'))
{
	// Configure AspectMock
	$kernel = \AspectMock\Kernel::getInstance();
	$kernel->init(array(
		'debug' => true,
		'appDir' => __DIR__.'/../',
		'includePaths' => array(
			APPPATH, COREPATH, PKGPATH,
		),
		'excludePaths' => array(
			APPPATH.'tests', COREPATH.'tests',
		),
		'cacheDir' => APPPATH.'tmp/AspectMock',
	));

	// Load in the Fuel autoloader
	$kernel->loadFile(COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php');
}
else
{
	// Load in the Fuel autoloader
	require COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php';
}

class_alias('Fuel\\Core\\Autoloader', 'Autoloader');

// Boot the app
require_once APPPATH.'bootstrap.php';

// Set test mode
\Fuel::$is_test = true;

// Ad hoc fix for AspectMock error
if (class_exists('AspectMock\Kernel'))
{
	class_exists('Errorhandler');
}

// Import the TestCase class
if (class_exists('\PHPUnit\Framework\TestCase'))
{
	import('testcase_ns');
}
else
{
	import('testcase');
}
