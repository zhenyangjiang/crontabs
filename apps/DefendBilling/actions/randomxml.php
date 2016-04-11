<?php
use Landers\Substrate\Utils\Fso;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;

Response::note(['【生成模拟防火墙数据】（'.System::app('name').'）开始工作','#dbline']);

$ips = [
    '192.168.7.100',
    '192.168.7.101',
    '192.168.7.102',
    '192.168.7.10',
];

$random_count = 20;

$hosts = [];
for ($i = 1; $i <= $random_count; $i++) {
    $sub_tpl = '
    <host>
        <type>host</type>
        <address>%s</address>
        <input_bps>%s</input_bps>
        <input_pps>%s</input_pps>
        <status>[UDP]</status>
    </host>
    ';
    $address = $ips[rand(0, count($ips)-1)];
    $bps = rand(10000, 25000);
    $pps = rand(100000, 500000);
    $hosts[] = sprintf($sub_tpl, $address, $bps, $pps);
}

$tpl = Fso::read(dirname(__DIR__).'/data/random-tpl.xml');
$content = sprintf($tpl, implode('', $hosts));
$file = dirname(__DIR__).'/data/random.xml';
if (Fso::write($file, $content)) {
    Response::note('模拟防火墙数据成功生成在'.$file);
} else {
    Response::note('模拟防火墙数据生成失败');
}