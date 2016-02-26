<?php
namespace Landers\Utils;

!defined('ENV_cookietime') or define('ENV_cookietime', 1);

class Cookie {
    private static $is_cookie_encrypt = false;
    private static $cookie_data = array();
    public static function set($key, $val, $expire = NULL){
        if (self::$is_cookie_encrypt === true) {
            $key = md5('lwap'.$key);
            if ($val) $val = Crypt::encode($val);
        }
        if (!is_null($val)) {
            $expire or $expire = (int)ENV_cookietime or $expire = 1;
            $expire = strtolower((string)$expire);
            preg_match('/\d+/', $expire, $match);
            if ($match) $match_number = $match[0];
            if (preg_match('/[a-zA-Z]+/', $expire, $match)) {
                $match_letter = $match[0];
                switch ($match_letter) {
                    case 'h': $expire = 3600 * $match_number; break;
                    case 'm': $expire = 60 * $match_number; break;
                    case 's': $expire = $match_number; break;
                    default : $expire = $match_number <= 24 ? $match_number * 3600 : $match_number; break;
                }
            } else {
                $expire = $match_number * 3600;
            }

            self::$cookie_data[$key] = $val;
        } else {
            unset(self::$cookie_data[$key]); $expire = -1;
        }
        @setcookie($key, $val, time() + $expire, '/');
    }

    public static function get($key){
        $is = self::$is_cookie_encrypt === true;
        if ( $is ) $key = md5('xweb'.$key);
        if ( array_key_exists($key, self::$cookie_data) ) {
            $ret = self::$cookie_data[$key];
        } else {
            if ( array_key_exists($key, $_COOKIE) ) {
                $ret = $_COOKIE[$key];
            } else {
                $ret = NULL;
            }
        }
        if ( $is ) $ret = Crypt::decode($ret);
        return $ret;
    }

    public static function clear(){
        foreach ($_COOKIE as $k => $v) {
            @setcookie($k, null, time() - 1);
            unset(self::$cookie_data[$k]);
        }
    }
}