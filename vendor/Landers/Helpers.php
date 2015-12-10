<?php
use Landers\Utils\Arr;
use Landers\Utils\CallIndieFunction;

function is_ok_ret(&$var){
    if (is_array($var)) {
        $t1 = Arr::get($var, 0);
        $t2 = Arr::get($var, 'status');
        return $t1 === 0 || $t1 === true || $t2 === 'success';
    } else return (bool)$var;
}

Class CallTo extends CallIndieFunction {
    protected static $path;
    public static function init(){
         self::$path = __DIR__.'/Functions';
    }
}; \CallTo::init();

function auto_parse_args($args) {
    $ret = [];
    foreach ($args as $arg) {
        $type = gettype($arg);
        $ret[$type] = $arg;
    };
    return $ret;
}

function successfaild($bool) {
    return $bool ? '成功' : '失败';
}
?>
