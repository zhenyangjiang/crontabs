<?php
namespace Landers\Substrate\Utils;
/**
 * Json编码、解码
 * @author Landers
 */
class Json {
    //改写系统json_decode函数
    public static function decode($str){
        $str = stripslashes($str);
        $str = str_replace(array("\r\n", "\n"), '\n', $str);
        return json_decode($str, true);
    }
    private static function chinaese($str, $is_to_gbk = false) {
        //$str = preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $str);
        $str = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '$1'))", $str);
        return $is_to_gbk ? iconv('utf-8', 'gbk//ignore', $str) : $str;
    }

    //json_encode对中文在不同php版本下的兼容处理
    public static function encode($str){
        if (PHP_VERSION >= '5.4') {
            return json_encode($str, JSON_UNESCAPED_UNICODE);
        } else {
            $str = json_encode($str);
            return self::chinaese($str);
        }
    }
}