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
        }

        if (method_exists(self::$class, 'init')) {
            call_user_func_array([self::$class, 'init'], $args);
        }
    }

    public static function __callStatic($method, $args) {
        $class = self::$class;
        switch (count($args)) {
            case 0: return $class::$method();
            case 1: return $class::$method($args[0]);
            case 2: return $class::$method($args[0], $args[1]);
            case 3: return $class::$method($args[0], $args[1], $args[2]);
            case 4: return $class::$method($args[0], $args[1], $args[2], $args[3]);
            default: return call_user_func_array([$class, $method], $args);
        }
    }
}
?>