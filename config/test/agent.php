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

return array(
    'browscap' => array(
        'enabled' => true,
        'url'     => '',
        'method'  => 'local',
        'file'    => __DIR__.DS.'..'.DS.'..'.DS.'tests'.DS.'agent'.DS.'browscap.ini',
    ),
    'cache' => array(
        'driver'     => '',
        'expiry'     => 1,
        'identifier' => 'fuel.agent-test',
    ),
);
