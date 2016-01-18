<?php
return array(
    'mitigation' => array(
        'host'      => '172.31.50.5',
        'port'      => '3301',
        'dbname'  => 'ulan_mitigation',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'main' => array(
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'dbname'  => 'ulan_main',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'oauth' => array(
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'dbname'  => 'ulan_oauth',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'resource-usage' => array(
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'dbname'  => 'ulan_monitor',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    )
);
?>