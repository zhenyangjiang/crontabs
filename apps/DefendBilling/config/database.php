<?php
return [
    'mitigation' => [
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'dbname'  => 'ulan_mitigation',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ],

    'main' => [
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'dbname'  => 'ulan_main',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ],

    'oauth' => [
        'host'      => '172.31.50.5',
        'port'      => '3306',
        'dbname'  => 'ulan_oauth',
        'username'  => 'landers',
        'password'  => 'CpBbVRJqGvsrLc3n',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ],
];
?>