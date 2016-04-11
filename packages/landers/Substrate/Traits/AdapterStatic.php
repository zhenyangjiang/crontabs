<?php
namespace Landers\Substrate\Traits;

Trait AdapterStatic {
    public static function __callStatic($method, $args) {
        if (!$class = self::$adapter) {
            throw new \Exception('未执行'.static::class.'初始化！');
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
