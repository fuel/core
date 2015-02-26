<?php
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
