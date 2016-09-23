<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Landers\Framework\Services\OAuthHttp;
use ULan\Repository\Crypt;

include 'inc-headline.php';

if ( !$uid = (int)System::argv(3) ) {
    System::halt('未指定UID');
}

if ( !$amount = (float)System::argv(4) ) {
    System::halt('未指定支付金额');
}

$money = User::money($uid);
Response::note('用户ID:%s，当前余额：%s', $uid, $money);

Response::note('测试支付：%s', $amount);
User::expend($uid, $amount, [
    'description' => '测试账单描述',
    'amount' => $amount,
    'privilege' => 0,
    'occur_way' => '余额支付',
    'typekey' => 'test_key',
    'client_ip' => 'crontab'
]);

$money = User::money($uid);
Response::note('支付后余额：%s', $money);

System::complete();