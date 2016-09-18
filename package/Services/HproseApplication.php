<?php
namespace Services;

use Landers\Substrate\Traits\MakeInstance;
use SuperClosure\Serializer;
use ULan\HProse\Client;
use Landers\Framework\Core\Config;

class HproseApplication  {
    use MakeInstance;

    public static function init() {
        self::singletonBy('ULan_HProse', function() {
            $config = Config::get('hprose');
            return new Client($config['url'], $config['async']);
        });

        self::singletonBy('ULan_Serialize', function() {
            return new Serializer();;
        });
    }
}

