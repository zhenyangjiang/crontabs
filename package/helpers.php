<?php
use Landers\Framework\Core\Response;

function reportException($message, $type, $extra_data = array()){
    $message = colorize($message, 'pink');
    Response::note('#tab日志上报任务【'.$message.'】加入队列...');
    $task = new \Tasks\ReportException($message, $type, $extra_data);
    $temp_ququeId = \Landers\Framework\Core\Queue::singleton('report-exception')->push($task );
    Response::echoBool(!!$temp_ququeId);
}
function reportDevException($message, $context = array(), $level = NULL){
    $extra_data = array();
    if ($context) $extra_data['context'] = $context;
    if ($level) $extra_data['level'] = $level;
    return reportException($message, 0, $extra_data);
}
function reportOptException($message, $context = array()){
    return reportException($message, 1, compact('context'));
}

function array_search_less_that(&$a, $val, $callback = NULL) {
    $arr = array_filter($a, function($v) use ($val, $callback){
        if ($callback) $v = $callback($v);
        return $val <= $v;
    });
    if ( $arr ) {
        sort($arr); reset($arr);
        return pos($arr);
    } else {
        rsort($a); reset($a);
        return pos($a);
    }
}

function during_prev_hours($hours) {
    $end = strtotime(date('Y-m-d H:0:0'));
    $begin = strtotime('-1 hours', $end); $end--;
    //$begin = strtotime('2015-09-12 10:00:00');
    //$end = strtotime('2015-09-12 10:59:59');
    return array(
        'begin'         => $begin,
        'end'           => $end,
        'begin_text'    => date('Y-m-d H:i:s', $begin),
        'end_text'      => date('Y-m-d H:i:s', $end)
    );
}

function generateUUID(){
    $currentTime = (string)microtime(true);
    $randNumber = (string)rand(10000, 1000000);
    $shuffledString = str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");
    return md5($currentTime . $randNumber . $shuffledString);
}

/**
 * 输出实例详情
 * @param  array  $instance [description]
 * @return [type]           [description]
 */
function response_instance_detail(array $instance){
    Response::note('实例详情如下：');
    Response::note(
        '#tabID=%s，名称=%s，标识=%s，自动续费=%s',
        $instance['id'],
        $instance['hostname'],
        $instance['hostkey'],
        $instance['is_auto_renew'] ? '开启' : colorize('未', 'yellow').'开启',
        $instance['mainipaddress']
    );
    Response::note('#blank');
}

/**
 * 输出用户详情
 * @param  array  $user [description]
 * @return [type]       [description]
 */
function response_user_detail(array $user) {
    Response::note('实例所属用户详情如下：');
    Response::note('#tabUID=%s，名称=%s，余额=%s元', $user['id'], $user['username'], $user['money']);
    Response::note('#tab手机=%s，邮箱=%s', $user['mobile'], $user['email']);
    Response::note('#blank');
}

/**
 * [app description]
 * @param  [type] $class [description]
 * @return [type]        [description]
 */
function app($key) {
    return \Services\HproseApplication::singletonBy($key);
}

function config($key) {
    return \Landers\Framework\Core\Config::get($key);
}

function env($key, $default = NULL) {
    $a = @include(__DIR__ . '/../.env');
    if ( !$a ) $a = array();
    $ret = \Landers\Substrate\Utils\Arr::get($a, $key);
    $ret or $ret = $default;
    return $ret;
}
?>