<?php
namespace Landers\Utils;

class Crypt {
    //DZ加密算法
    public static function encode($str, $key = NULL, $expiry = NULL) {
        $key or $key = ''; $expiry or $expiry = 0;
        $len = 4; $key = md5($key !== '' ? $key : 'LANDERS');
        $fixedkey = md5($key); $egiskeys = md5(substr($fixedkey, 16, 16));

        $runtokey = $len ? substr(md5(microtime(true)), -$len) : '';
        $keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
        $str = sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($str.$egiskeys), 0, 16) . $str;

        $i = 0; $result = '';
        $str_length = strlen($str);
        for ($i = 0; $i < $str_length; $i++){
            $result .= chr(ord($str{$i}) ^ ord($keys{$i % 32}));
        }

        return $runtokey . str_replace('=', '', base64_encode($result));
    }

    //DZ解密算法
    public static function decode($str, $key = NULL, $expiry = NULL) {
        $key or $key = ''; $expiry or $expiry = 0;
        $len = 4; $key = md5($key != '' ? $key : 'LANDERS');
        $fixedkey = md5($key); $egiskeys = md5(substr($fixedkey, 16, 16));

        $runtokey = $len ? substr($str, 0, $len) : '';
        $keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
        $str = base64_decode(substr($str, $len));

        $i = 0; $result = '';
        $str_length = strlen($str);
        for ($i = 0; $i < $str_length; $i++){
            $result .= chr(ord($str{$i}) ^ ord($keys{$i % 32}));
        }

        if  (
            (substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
             substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)
            ){
            return substr($result, 26);
        } else {
            return '';
        }
    }

}
?>