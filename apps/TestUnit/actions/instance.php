<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

if (!$action = System::argv(3)) {
    System::halt('缺少操作方法');
}

if (!$instance_id = (int)System::argv(4)) {
    System::halt('缺少操作对象');
}

Response::note('测试操作实例（云主机)...');
Response::note('操作命令：%s %s', $action, $instance_id);

try {
    call_user_func_array(array('Instance', $action), [$instance_id]);
    Response::bool(true, '操作%s');
} catch (\Exception $e) {
    $e = parse_general_exception($e);
    Response::bool(false, $e->message);
}
System::complete();