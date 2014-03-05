<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
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
 	public function test_foo() {}

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
                }
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
                }
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
                }
            ),
            array(
                'api/app/version',
                'Controller\\Api\\App',
                'version', function ($class) {
                    return $class === 'Controller\\Api\\App';
                },
                function () {
                    return 'Controller\\';
                }
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
                }
            ),
            array(
                'api/app/version/more',
                'Controller\\Api\\App\\Version',
                'more', function ($class) {
                    return $class === 'Controller\\Api\\App\\Version';
                },
                function () {
                    return 'Controller\\';
                }
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
                }
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
                }
            )
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
        $router = $this->getMockBuilder('\\Router')
        ->setMethods(array(
            'check_class',
            'get_prefix'
        ))
        ->getMock();

        // Mock check_class to avoid class_exists and autoloader.
        $router::staticExpects($this->any())
        ->method('check_class')
        ->will($this->returnCallback($check_class));

        // Mock get_prefix to avoid Config abd test both
        // Controller\\ and Controller_ prefixes.
        $router::staticExpects($this->any())
        ->method('get_prefix')
        ->will($this->returnCallback($get_prefix));

        $match = $router::process(\Request::forge($url));
        $this->assertEquals($controller, $match->controller);
        $this->assertEquals($action, $match->action);
        $this->assertEquals(array(), $match->method_params);
    }
}
