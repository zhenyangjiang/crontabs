<?php
return array(
    'mitigation' => array(
        'host'      => '172.31.50.5',
        'port'      => '3301',
        'database'  => 'ulan_mitigation',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'main' => array(
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'database'  => 'ulan_main',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'oauth' => array(
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'database'  => 'ulan_oauth',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'monitor' => array(
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'database'  => 'ulan_monitor',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    )
);
?>