<?php
use Landers\Framework\Core\Response;

function reportException($message, $type, $extra_data = array()){
    $message = colorize($message, 'pink');
    Response::note('#tab日志上报任务【'.$message.'】加入队列...');
    // $task = new \Tasks\ReportException($message, $type, $extra_data);
    // $temp_ququeId = \Landers\Framework\Core\Queue::singleton('report-exception')->push($task );
    // Response::echoBool(!!$temp_ququeId);
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

function response_mitigation_detail(array $mitWithDDoSInfos) {
    $mit = &$mitWithDDoSInfos;

    Response::note(
        '云盾ID：%s，绑定服务：%s (%s)',
        $mit['id'],
        $mit['service'], $mit['service_id']
    );
    Response::note(
        '计费方案：%s，防护阈值：%sMbps / %spps',
        Mitigation::billingText($mit['billing']),
        $mit['ability_mbps'], $mit['ability_pps']
    );
    $ips = Landers\Substrate\Utils\Arr::pick($mit['ddosinfos'], 'dest');

    Response::note(
        '当前被攻击IP：%s，共 %s 个',
        implode('，', $ips), count($ips)
    );
    Response::note(
        '被攻击总速率：%sMbps，被攻击总报文：%spps',
        $mit['sum_mbps'], $mit['sum_pps']
    );
    unset($mit);
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

function parse_general_exception($e) {
    $message = $e->getMessage();
    $dat = json_decode($message);
    return (object)[
        'message' => $dat->message,
        'file' => $dat->debug->file,
        'line' => $dat->debug->line
    ];
}

function run_log($event_key, $message, $context = []){

}

function halt_by_exception($e) {
    System::halt($e->getMessage());
    throw $e;
}
?>