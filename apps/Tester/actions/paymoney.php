<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Landers\Framework\Services\OAuthHttp;
use ULan\Repository\Crypt;

include 'inc-headline.php';

if ( !$uid = System::argv(3) ) {
    System::halt('未指定UID');
}

if ( !$amount = System::argv(4) ) {
    System::halt('未指定支付金额');
}

// $users = User::lists();
// foreach($users as $user) {
//     $uid = $user['id'];
//     $money = $user['money'];
//     $money = Crypt::encode($money, $uid);
//     User::update([
//         'money' => $money
//     ], $uid);
// }
// pause();

// dp(Crypt::encode(350982.654, $uid), false);
// dp(crypt::decode('0a60CAgGUwcCUlQDBgAHDgxUBA8FUANSDFQHAANRB1UJWgMWDgNX', $uid));


$money = User::get_money($uid);
Response::note('用户ID:%s，当前余额：%s', $uid, $money);

Response::note('测试支付：%s', $amount);
User::pay_money($uid, $amount);


$money = User::get_money($uid);
Response::note('支付后余额：%s', $money);

System::complete();