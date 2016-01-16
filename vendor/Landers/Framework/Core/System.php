<?php
namespace Landers\Framework\Core;

Class System {
    private static $class;
    public static function init($mode, $args = array()) {
        $pre = '\\Landers\\Framework\\Adapters\\System\\';
        switch (strtoupper($mode)) {
            case 'LWAP' :
                self::$class = $pre.'LwapSystem';
                break;
            case 'LCLI' :
                self::$class = $pre.'LcliSystem';
                break;
            case 'LARAVEL' :
                self::$class = $pre.'LaravelSystem';
                break;
            default :
                exit('未指定系统适配器！');
        }

        if (method_exists(self::$class, 'init')) {
            call_user_func_array(array(self::$class, 'init'), $args);
        }
    }

    public static function __callStatic($method, $args) {
        if (!$class = self::$class) {
            exit('未执行'.__CLASS__.'初始化！');
        }
        switch (count($args)) {
            case 0: return $class::$method();
            case 1: return $class::$method($args[0]);
            case 2: return $class::$method($args[0], $args[1]);
            case 3: return $class::$method($args[0], $args[1], $args[2]);
            case 4: return $class::$method($args[0], $args[1], $args[2], $args[3]);
            default: return call_user_func_array(array($class, $method), $args);
        }
    }
}
?>