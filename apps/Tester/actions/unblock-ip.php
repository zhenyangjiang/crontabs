<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

if ( !$ips = System::argv(3) ) {
    System::halt('未指定牵引IP');
} else {
    if ($ips == 'ALL') {
        if ( $mitigations = Mitigation::lists(['askey' => 'ip', 'fields' => 'ip']) ) {
            $ips = array_keys($mitigations);
        } else {
            $ips = [];
        }
    } else {
        $ips = explode(',', $ips);
    }
}

// 123.1.1.7,123.1.1.16,123.1.1.21,123.1.1.20,123.1.1.9,123.1.1.6,123.1.1.19,123.1.1.8,123.1.1.13,123.1.1.3,123.1.1.10
foreach ($ips as $ip) {
    $ip = trim($ip);
    Response::note('测试解除牵引IP：%s...', $ip);
    $bool = BlackHole::unblock($ip);
    Response::echoBool($bool);
}

Response::note('#line');
System::complete();
