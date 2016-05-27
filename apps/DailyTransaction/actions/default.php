<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;

$title = sprintf('【日常事务】（%s）开始工作', System::app('name'));
Response::note([$title,'#dbline']);
$config = Config::get('transaction');
$nowTime = time();

// 删除用量历史数据
$useage_save_days = $config['useage_save_days'];
Response::note('删除主机用量%s天前的数据', $useage_save_days);
$delete_time = strtotime("-$useage_save_days day", $nowTime);
if ( Usage::delete(array(
    "created_at < $delete_time"
))) {
    $affect_rows = Usage::affect_rows();
    Response::note('成功删除 %s 条用量历史数据！', $affect_rows);
} else {
    Response::warn('主机用量历史数据删除失败！');
}

// 删除DDoS-Source历史数据
Response::note(['#blank', '#line', '#blank']);
$ddossource_save_hours = $config['ddossource_save_hours'];
Response::note('删除%s小时前的DDoS-Source', $ddossource_save_hours);
$delete_time = strtotime("-$ddossource_save_hours hour", $nowTime);
if ( DDoSSource::delete(array(
    "time < $delete_time"
))) {
    $affect_rows = DDoSSource::affect_rows();
    Response::note('成功删除 %s 条DDoS-Source历史数据！', $affect_rows);
} else {
    Response::warn('DDoS-Source历史数据删除失败！');
}

// 删除CC-Source历史数据
Response::note(['#blank', '#line', '#blank']);
$ccsource_save_hours = $config['ccsource_save_hours'];
Response::note('删除%s小时前的CC-Source', $ccsource_save_hours);
$delete_time = strtotime("-$ccsource_save_hours hour", $nowTime);
if ( CCSource::delete(array(
    "time < $delete_time"
))) {
    $affect_rows = CCSource::affect_rows();
    Response::note('成功删除 %s 条CC-Source历史数据！', $affect_rows);
} else {
    Response::warn('CC-Source历史数据删除失败！');
}

System::continues();