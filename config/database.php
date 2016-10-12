<?php
return array(
    'mitigation' => array(
        'read'      => array(
            'host'      => env('db.mitigation.read.host', 'xxx.xxx.xxx.xxx'),
            'port'      => '3306',
            'dbname'    => 'ulan_mitigation',
            'username'  => env('db.mitigation.read.username', 'xxxxxx'),
            'password'  => env('db.mitigation.read.password', 'xxxxxx'),
            'charset'   => 'utf8',
            'log-path'  => dirname(__DIR__).'/logs/'
        ),
        'write'      => array(
            'host'      => env('db.mitigation.write.host', 'xxx.xxx.xxx.xxx'),
            'port'      => '3306',
            'dbname'    => 'ulan_mitigation',
            'username'  => env('db.mitigation.write.username', 'xxxxxx'),
            'password'  => env('db.mitigation.write.password', 'xxxxxx'),
            'charset'   => 'utf8',
            'log-path'  => dirname(__DIR__).'/logs/'
        )
    ),

    'main' => array(
        'host'      => env('db.main.host', 'xxx.xxx.xxx.xxx'),
        'port'      => '3306',
        'dbname'    => 'ulan_main',
        'username'  => env('db.main.username', 'xxxxxx'),
        'password'  => env('db.main.password', 'xxxxxx'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'oauth' => array(
        'host'      => env('db.oauth.host', 'xxx.xxx.xxx.xxx'),
        'port'      => '3306',
        'dbname'    => 'ulan_oauth',
        'username'  => env('db.oauth.username', 'xxxxxx'),
        'password'  => env('db.oauth.password', 'xxxxxx'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'collecter' => array(
        'host'      => env('db.collecter.host', 'xxx.xxx.xxx.xxx'),
        'port'      => '3306',
        'dbname'    => 'ulan_collecter',
        'username'  => env('db.collecter.username', 'xxxxxx'),
        'password'  => env('db.collecter.password', 'xxxxxx'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'blackhole' => array(
        'host'      => env('db.blackhole.host', 'xxx.xxx.xxx.xxx'),
        'port'      => '3306',
        'dbname'    => 'ulan_blackhole',
        'username'  => env('db.blackhole.username', 'xxxxxx'),
        'password'  => env('db.blackhole.password', 'xxxxxx'),
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    )
);
?>