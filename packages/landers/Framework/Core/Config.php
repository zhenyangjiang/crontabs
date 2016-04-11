<?php
namespace Landers\Framework\Core;

Class Config {
    private static $paths = array();
    public static function init($paths) {
        self::$paths = $paths;
    }

    private static function file($filekey) {
        foreach ((array)self::$paths as $path) {
            $file = $path."$filekey.php";
            if (is_file($file)) return $file;
        }
        return NULL;
    }

    /**
     * 取得配置文件的数据
     * @param  string $filekey 主文件名
     * @return mixed
     */
    public static function get($filekey, $key = NULL) {
        $file = self::file($filekey);
        $configs = System::cache('CONFIG') or $configs = array();
        if (!$config = &$configs[$filekey]) {
            if ($file) $config = include($file);
            if ($config) System::cache('CONFIG', $configs);
            $config or $config = array();
        }
        if (!$key) {
            return $config;
        } else {
            if ($key === true) {
                return $config[$config['default']];
            } else {
                return $config[$key];
            }
        }
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