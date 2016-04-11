<?php
namespace Landers\Framework\Core;

use Landers\Substrate\Traits\AdapterStatic;

Class System {
    use AdapterStatic;

    private static $adapter;
    public static function init($mode, $args = array()) {
        $pre = '\\Landers\Substrate\\Framework\\Adapters\\System\\';
        switch (strtoupper($mode)) {
            case 'LWAP' :
                self::$adapter = $pre.'LwapSystem';
                break;
            case 'LCLI' :
                self::$adapter = $pre.'LcliSystem';
                break;
            case 'LARAVEL' :
                self::$adapter = $pre.'LaravelSystem';
                break;
            case 'SIMPLE' :
                self::$adapter = $pre.'SimpleSystem';
                break;
            default :
                throw new \Expectation('未指定系统适配器！');
        }

        if (class_exists(Response::class)) {
            Response::init($mode);
        }

        if (method_exists(self::$adapter, 'init')) {
            call_user_func_array(array(self::$adapter, 'init'), $args);
        }
    }
}
?>