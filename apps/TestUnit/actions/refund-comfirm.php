<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Modules\Log;
include 'inc-headline.php';

if (!$instance_id = (int)System::argv(3)) {
    System::halt('缺少实例id');
}

$instance = Instance::find($instance_id);
if ($instance) {

    Response::note('创建“销毁云主机”申请记录...');
    $repo = repository('refund');
    $uid = $instance['uid'];
    $client_ip = 'crontab';
    try {
        $ret = $repo->apply( $uid,  $instance_id, $client_ip, true);
        $refund_id = $ret['refund_id'];
        Response::echoBool(true);
    } catch (\Exception $e) {
        $refund_id = NULL;
        $e = parse_general_exception($e);
        Response::bool(false, $e->message);
    }

    if ( $refund_id) {
        Response::note('同意“销毁云主机”申请记录%s...', $refund_id);
        $bool = $repo->confirm( $refund_id, 'crontab' );
        Response::echoBool($bool);
    }
} else {
    Response::warn('云主机不存在！！');
}



Response::note('#line');
System::complete();