<?php
use Landers\Utils\Arr;
use Landers\Utils\CallIndieFunction;
use Landers\Utils\ApiResult;

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

/**
 * 生成api结果
 * @return [type] [description]
 */
function build_api_result() {
    $args = func_get_args();
    return call_user_func_array([ApiResult::class, 'make'], $args)->get();
}

/**
 * 输入出api结果
 * @return [type] [description]
 */
function output_api_result(){
    $args = func_get_args();
    call_user_func_array([ApiResult::class, 'make'], $args)->output();
}

function redis($key, $value) {
    $redis_key = 'AuthInfo_'.$access_token;
    $redis = &self::$redis;
    $redis or $redis = $redis = Redis::connection();
    if (func_num_args() == 2) {
        $redis->set($redis_key, json_encode($data));
    } else {
        $ret = $redis->get($redis_key);
        if (!$ret) return NULL;
        return json_decode($ret);
    }
}
?>
