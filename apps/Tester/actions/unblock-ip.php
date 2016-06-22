<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试解除牵引IP...');

if ( !$ips = System::argv(3) ) {
    System::halt('未指定牵引IP');
} else {
    $ips = explode(',', $ips);
}

// 123.1.1.7,123.1.1.16,123.1.1.21,123.1.1.20,123.1.1.9,123.1.1.6,123.1.1.19,123.1.1.8,123.1.1.13,123.1.1.3,123.1.1.10
foreach ($ips as $ip) {
    $ip = trim($ip);

    Response::note('#tab解除牵引任务入队...');
    $bool = !!BlackHole::doUnblock($ip);
    Response::echoBool($bool);

    if ($bool) {

        //更新标志
        Response::note('#tab解除牵引更新“标志值”为已解除...');
        $blackhole = BlackHole::find([
            'ip' => $ip,
            'order' => 'id desc',
            'is_unblock' => 1
        ]);
        if ($blackhole) {
            $id = $blackhole['id'];
            $bool = BlackHole::update(['is_unblock' => 1], ['id' => $id]);
            Response::echoBool($bool);
            if (!$bool) return false;
        } else {
            Response::echoWarn('未找到记录');
        }
    }
    if ($bool) {
        //将牵引过期的ips所在的实例的net_state字段为正常(0)
        Response::note('#tab更新实例为“正常”...');
        $bool = Instance::update_net_status($ip, 0);
        Response::echoBool($bool);
        if (!$bool) return false;
    }

    Response::bool($bool, '解除牵引IP%s');
    Response::note('#line');
}
System::complete();