<?php
namespace Landers\Framework\Core;

use Landers\Substrate\Traits\AdapterStatic;

Class Response {
    use AdapterStatic;

    private static $adapter;
    public static function init($mode, $args = array()) {
        $pre = '\\Landers\Substrate\\Framework\\Adapters\\Response\\';
        switch (strtoupper($mode)) {
            case 'LWAP' :
                self::$adapter = $pre.'LwapResponse';
                break;
            case 'LCLI' :
                self::$adapter = $pre.'LcliResponse';
                break;
            default :
                exit('未指定系统适配器！');
        }
    }
}
