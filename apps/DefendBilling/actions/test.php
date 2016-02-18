<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Utils\Datetime;

$begin = time()-86400; $end = time(); $ip = '192.168.1.100';
$awhere = ['ip' => $ip, "created_at between $begin and $end"];
$ret = DDoSInfo::find([
    'unions'    => [$begin, $end],
    'awhere'    => $awhere,
    'order'     => 'bps desc'
]);
dp($ret);

DDoSInfo::debug();

dp(DDoSInfo::delete(["ip<>'192.168.1.100'"], [
    'unions' => [$begin, $end]
]));

dp(DDoSInfo::update(['ip' => '127.0.0.1'], ["ip<>'192.168.1.100'"], [
    'unions' => [$begin, $end]
]));