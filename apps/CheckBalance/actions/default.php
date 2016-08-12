<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;

$title = sprintf('【日常事务】（%s）开始工作', System::app('name'));
Response::note([$title, '#dbline']);

$mits = Mitigation::hourMits();
$users = [];
foreach ($mits as $mit) {
    $price_rules = json_decode($mit['price_rules'], true);
    if (isset($price_rules['mitigation-hour'][$mit['ability']])) {
        $mit['price'] = $price_rules['mitigation-hour'][$mit['ability']];
        $users[$mit['uid']][] = $mit;
    }
}

$userBalance = User::balance();
$config = Config::get('check_balance');
$hour = $config['calc_hour'];
Response::note("Hour:{$hour}");
foreach ($users as $uid => $mits) {
    $total_price = 0;
    Response::note("[{$uid}]:按需云盾列表");
    foreach ($mits as $mit) {
        $total_price += $mit['price'];
        Response::note("\t\t{$mit['ability']}:\t{$mit['price']}");
    }
    $total_price *= $hour;
    Response::note("[{$uid}]:{$total_price}/{$userBalance[$uid]}");
    if ($total_price > $userBalance[$uid]) {
        Response::note("[{$uid}]:余额不足");
        Notify::clientApi($uid, 'CHECKBALANCE-NOTENOUGH', [
            'hour' => $hour
        ]);
    }
    Response::note("#line");
}


