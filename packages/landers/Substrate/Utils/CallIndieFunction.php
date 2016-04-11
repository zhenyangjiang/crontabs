<?php
namespace Landers\Substrate\Utils;
abstract Class CallIndieFunction {
    //子类需定义 $path;
    public static function __callStatic($fun, $args) {
        if ( !function_exists($fun)) {
            require_once(static::$path.'/'.$fun.'.php');
        }
        switch (count($args)) {
            case 0: return $fun();
            case 1: return $fun($args[0]);
            case 2: return $fun($args[0], $args[1]);
            case 3: return $fun($args[0], $args[1], $args[2]);
            case 4: return $fun($args[0], $args[1], $args[2], $args[3]);
            default: return call_user_func_array($fun, $args);
        }
    }
}
?>