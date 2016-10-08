<?php
return array(
    'mitigation' => array(
        'read'      => array(
            'host'      => '172.31.66.200',
            'port'      => '3306',
            'dbname'    => 'ulan_mitigation',
            'username'  => 'root',
            'password'  => 'ULan.io123',
            'charset'   => 'utf8',
            'log-path'  => dirname(__DIR__).'/logs/'
        ),
        'write'      => array(
            'host'      => '172.31.66.200',
            'port'      => '3306',
            'dbname'    => 'ulan_mitigation',
            'username'  => 'root',
            'password'  => 'ULan.io123',
            'charset'   => 'utf8',
            'log-path'  => dirname(__DIR__).'/logs/'
        )
    ),

    'main' => array(
        'host'      => '172.31.66.200',
        'port'      => '3306',
        'dbname'    => 'ulan_main',
        'username'  => 'root',
        'password'  => 'ULan.io123',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'oauth' => array(
        'host'      => '172.31.66.200',
        'port'      => '3306',
        'dbname'   => 'ulan_oauth',
        'username'  => 'root',
        'password'  => 'ULan.io123',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'collecter' => array(
        'host'      => '172.31.66.134',
        'port'      => '3306',
        'dbname'    => 'ulan_collecter',
        'username'  => 'root',
        'password'  => 'ULan.io123',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    ),

    'blackhole' => array(
        'host'      => '172.31.66.200',
        'port'      => '3306',
        'dbname'    => 'ulan_blackhole',
        'username'  => 'root',
        'password'  => 'ULan.io123',
        'charset'   => 'utf8',
        'log-path'  => dirname(__DIR__).'/logs/'
    )
);
?>