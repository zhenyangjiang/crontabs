<?php
namespace Landers\Interfaces;

abstract Class SystemClass {
    /**
     * 系统初始化
     */
    public static function init(){

    }

    /**
     * 运行时的缓存读写
     * @param  String $key 键
     * @param  Mixed $dat 值 值为空时取值，反之设置值
     * @return Mixed
     */
    private static $cache_data = array();
    public static function cache($key, $dat = NULL) {
        $ret = &self::$cache_data;
        if (func_num_args() == 2) {
            $ret[$key] = $dat;
            return $dat;
        } else {
            if (array_key_exists($key, $ret)) {
                return $ret[$key];
            } else {
                return NULL;
            }
        }
    }
}
?>