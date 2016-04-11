<?php
namespace Landers\Framework\Core;

use Predis\Client;

Class Redis {
    private static $client;
    public static function init(){
        self::$client = new Client(Config::getDefault('predis'));
    }

    public static function get($key) {
        return self::$client->get($key);
    }

    public static function set($key, $value) {
        return self::$client->set($key, $value);
    }
}
Redis::init();