<?php
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;

$fwurls = config('fwurls');
$fw_counts = count($fwurls);
$dat_counts = 8071;
for ( $i = 1;  $i <= $dat_counts; $i++ ) {
    $n = ($i + $fw_counts - 1) % $fw_counts;
    $fwurl = $fwurls[$n];
    $pack = Http::get($fwurl);
    if ($pack = json_decode($pack, true)) {
        $hosts = &$pack['attack_host']['onAttackingHosts'];
        foreach ($hosts as &$item) {
            $item['name'] = 'DEST_IP';
            $item['src'] = 'SRC_IP';
        }
        $pack = json_encode($pack);
        $bool = FwEmulator::create(array(
            'pack' => $pack
        ));
        Response::bool($bool, '数据插入%s，剩余：'.($dat_counts-$i));
    }

    usleep(100000);
}