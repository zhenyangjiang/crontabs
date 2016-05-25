<?php
use Landers\Framework\Core\Response;
use Landers\Framework\Core\System;

if (!$action = System::argv(3)) {
    System::halt('缺少操作方法');
}

if (!$instance_id = (int)System::argv(4)) {
    System::halt('缺少操作对象');
}

Response::note('测试操作实例（云主机)...');
Response::note('操作命令：%s %s', $action, $instance_id);

$bool = call_user_func_array(array('Instance', $action), [$instance_id]);
Response::bool($bool, '操作%s');

// if ( !$instance_id = System::argv(3) ) {
//
// }

// $ret = Instance::suspend();
// Response::bool($bool, '短信发送任务入队%s');
// Response::note('#line');