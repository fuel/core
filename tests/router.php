<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Router class tests
 *
 * @group Core
 * @group Router
 */
class Test_Router extends TestCase
{
    /**
     * Provider for test_classnames.
     */
    public function provider_test_classnames()
    {
        return array(
            array(
                'api/app',
                'Controller_Api',
                'app',
                function ($class) {
                    return $class === 'Controller_Api';
                },
                function () {
                    return 'Controller_';
                },
            ),
            array(
                'api/app',
                'Controller\\Api',
                'app',
                function ($class) {
                    return $class === 'Controller\\Api';
                },
                function () {
                    return 'Controller\\';
                },
            ),
            array(
                'api/app/version',
                'Controller_Api_App',
                'version',
                function ($class) {
                    return $class === 'Controller_Api_App';
                },
                function () {
                    return 'Controller_';
                },
            ),
            array(
                'api/app/version',
                'Controller\\Api\\App',
                'version', function ($class) {
                    return $class === 'Controller\\Api\\App';
                },
                function () {
                    return 'Controller\\';
                },
            ),
            array(
                'api/app/version/more',
                'Controller_Api_App_Version',
                'more',
                function ($class) {
                    return $class === 'Controller_Api_App_Version';
                },
                function () {
                    return 'Controller_';
                },
            ),
            array(
                'api/app/version/more',
                'Controller\\Api\\App\\Version',
                'more', function ($class) {
                    return $class === 'Controller\\Api\\App\\Version';
                },
                function () {
                    return 'Controller\\';
                },
            ),
            array(
                'api/app/version/more/subdirs',
                'Controller_Api_App_Version_More',
                'subdirs',
                function ($class) {
                    return $class === 'Controller_Api_App_Version_More';
                },
                function () {
                    return 'Controller_';
                },
            ),
            array(
                'api/app/version/more/subdirs',
                'Controller\\Api\\App\\Version\\More',
                'subdirs',
                function ($class) {
                    return $class === 'Controller\\Api\\App\\Version\\More';
                },
                function () {
                    return 'Controller\\';
                },
            ),
        );
    }

    /**
     * Check that both Controller_Index and Controller\Index with
     * subdirs will both be found.
     *
     * @dataProvider provider_test_classnames
     */
    public function test_classnames($url, $controller, $action, $check_class, $get_prefix)
    {
        // Mock check_class to avoid class_exists and autoloader.
        Test_Router_Mock::$check_class = $check_class;

        // Mock get_prefix to avoid Config and test both
        // Controller\\ and Controller_ prefixes.
        Test_Router_Mock::$get_prefix = $get_prefix;

        $match = Test_Router_Mock::process(\Request::forge($url));
        $this->assertEquals($controller, $match->controller);
        $this->assertEquals($action, $match->action);
        $this->assertEquals(array(), $match->method_params);
    }

	public function test_add_route_and_router_name()
	{
		$path = 'testing/route';
		$options = null;
		$prepend = false;
		$case_sensitive = null;
		Router::add($path, $options, $prepend, $case_sensitive);
		
		$this->assertEquals($path, Router::$routes[$path]->path);
		$this->assertEquals($path, Router::$routes[$path]->name);
		
		Router::delete($path);
	}

	public function test_add_route_and_router_option_name()
	{
		$path = 'testing/route';
		$name = 'option_name';
		$options = array('name' => $name);
		$prepend = false;
		$case_sensitive = null;
		Router::add($path, $options, $prepend, $case_sensitive);
		
		$this->assertEquals($path, Router::$routes[$name]->path);
		$this->assertEquals($name, Router::$routes[$name]->name);
		
		Router::delete($name);
	}
}
