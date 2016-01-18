<?php
namespace Landers\Framework\Core;

Class Config {
    private static $path;
    public static function init($path) {
        self::$path = $path;
    }

    private static function file($filekey) {
        if (self::$path) {
            return self::$path."$filekey.php";
        } else {
            return NULL;
        }
    }

    /**
     * 取得配置文件的数据
     * @param  string $filekey 主文件名
     * @return mixed
     */
    public static function get($filekey, $is_default = false) {
        $file = self::file($filekey);
        $config = System::cache('CONFIG') or $config = array();
        if ($ret = &$config[$filekey]) return $ret;
        if ($file) $ret = @include($file);
        if ($ret) System::cache('CONFIG', $config);
        $ret or $ret = array();
        if ($is_default) {
            return $ret[$ret['default']];
        } else return $ret;
    }

    /**
     * 取得默认配置项
     * @param  [type] $filekey [description]
     * @param  [type] $default 默认配置key
     * @return [type]          [description]
     */
    public static function get_default($filekey, $default = NULL) {
        $configs = self::get($filekey);
        $def = $configs['default'] or $def = $default;
        return $configs[$def];
    }
    public static function getDefault($filekey, $default = NULL) {
        return self::get_default($filekey, $default);
    }


    /**
     * 回存数据至文件
     * @param [type] $filekey [description]
     * @param  array  $append 追加配置项
     * @param  array  $is_save 是否回存
     */
    public static function set($filekey, $append = array(), $is_save = false) {
        $config = self::get($filekey);
        $config = array_merge($config, $append);
        System::cache('CONFIG', array($filekey => $config));
        if ( $is_save ) {
            $content = "<?\nreturn ".var_export($config, true).";\n?>";
            $file = self::file($filekey);
            return file_put_contents($file, $content);
        }
    }
}