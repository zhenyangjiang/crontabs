<?php
namespace Services;

use Landers\Substrate\Traits\MakeInstance;
use SuperClosure\Serializer;
use ULan\HProse\Client;

class HproseApplication {

    public static function init() {
        self::createObject('ULan_HProse', function() {
            $config = Config::get('hprose');
            return new Client($config['url'], $config['async']);
        });

        self::createObject('ULan_Serialize', function() {
            return new Serializer();;
        });
    }

    public private function createObject($key, $callback) {
        return self::singletonBy($key, $callback);
    }
}

