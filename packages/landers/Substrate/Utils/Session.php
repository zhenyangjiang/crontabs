<?php
namespace Landers\Substrate\Utils;
class Session {
    private static $has_started = false;
    public static function start(){
        if (!self::$has_started) {
            session_start();
            self::$has_started = true;
        }
    }

    public static function get($key) {
        self::start();
        $val = $_SESSION[$key];
        $test = unserialize($val);
        return $test ? $test : $val;
    }

    public static function set($key, $val) {
        self::start();
        if (is_array($val)) $val = serialize($val);
        $_SESSION[$key] = $val;
    }
}