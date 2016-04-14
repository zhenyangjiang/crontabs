<?php
use Landers\Substrate\Utils\Http;
use Tasks\ReportException;
use Landers\Framework\Core\Queue;
use Landers\Framework\Core\Response;

function reportException($message, $type, $extra_data = array()){
    $task = new ReportException($message, $type, $extra_data);
    $temp_ququeId = Queue::singleton('report-exception')->push($task );
    Response::bool(!!$temp_ququeId, '异常上报任务入队%s');
}
function reportDevException($message, $extra_data = array()){
    return reportException($message, 0, $extra_data);
}
function reportOptException($message, $extra_data = array()){
    return reportException($message, 1, $extra_data);
}

function array_search_less_that(&$a, $val, $callback = NULL) {
    $arr = array_filter($a, function($v) use ($val, $callback){
        if ($callback) $v = $callback($v);
        return $val < $v;
    });
    sort($arr); reset($arr);
    return pos($arr);
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
?>