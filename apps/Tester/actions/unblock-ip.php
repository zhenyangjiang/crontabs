<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

if ( !$ips = System::argv(3) ) {
    System::halt('未指定牵引IP');
} else {
    $ips = explode(',', $ips);
}

// 123.1.1.7,123.1.1.16,123.1.1.21,123.1.1.20,123.1.1.9,123.1.1.6,123.1.1.19,123.1.1.8,123.1.1.13,123.1.1.3,123.1.1.10
foreach ($ips as $ip) {
    $ip = trim($ip);
    Response::note('测试解除牵引IP：%s...', $ip);

    Response::note('#tab解除牵引任务入队...');
    $bool = !!BlackHole::doUnblock($ip);
    Response::echoBool($bool);

    if ($bool) {
        //将牵引过期的ips所在的云盾的status字段为正常
        Response::note('#tab更新云盾IP为“正常”...');
        $bool = Mitigation::setStatus($ip, 'NORMAL');
        Response::echoBool($bool);
        if (!$bool) return false;
    }

    Response::bool($bool, '解除牵引IP%s');
    Response::note('#line');
}
System::complete();