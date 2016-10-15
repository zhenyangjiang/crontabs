<?php
return array(
    'mitigation' => array(
        'read'      => array(
            'host'      => env('db.mitigation.read.host', '172.31.66.200'),
            'port'      => '3306',
            'dbname'    => 'ulan_mitigation',
            'username'  => env('db.mitigation.read.username', 'root'),
            'password'  => env('db.mitigation.read.password', 'ULan.io123'),
            'charset'   => 'utf8',
            'log-path'  => dirname(__DIR__).'/logs/'
        ),
        'write'      => array(
            'host'      => env('db.mitigation.write.host', '172.31.66.200'),
            'port'      => '3306',
            'dbname'    => 'ulan_mitigation',
            'username'  => env('db.mitigation.write.username', 'root'),
            'password'  => env('db.mitigation.write.password', 'ULan.io123'),
            'charset'   => 'utf8',
            'log-path'  => dirname(__DIR__).'/logs/'
        )
    ),

    'main' => array(
        'host'      => env('db.main.host', '172.31.66.200'),
        'port'      => '3306',
        'dbname'    => 'ulan_main',
        'username'  => env('db.main.username', 'root'),
        'password'  => env('db.main.password', 'ULan.io123'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'oauth' => array(
        'host'      => env('db.oauth.host', '172.31.66.200'),
        'port'      => '3306',
        'dbname'    => 'ulan_oauth',
        'username'  => env('db.oauth.username', 'root'),
        'password'  => env('db.oauth.password', 'ULan.io123'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'collecter' => array(
        'host'      => env('db.collecter.host', '172.31.66.134'),
        'port'      => '3306',
        'dbname'    => 'ulan_collecter',
        'username'  => env('db.collecter.username', 'root'),
        'password'  => env('db.collecter.password', 'ULan.io123'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'blackhole' => array(
        'host'      => env('db.blackhole.host', '172.31.66.200'),
        'port'      => '3306',
        'dbname'    => 'ulan_blackhole',
        'username'  => env('db.blackhole.username', 'root'),
        'password'  => env('db.blackhole.password', 'ULan.io123'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    )
);
?>