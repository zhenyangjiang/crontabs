<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
include 'inc-headline.php';

Response::note('测试短信发送...');

$config = Config::get('notify-contents');
unset($config['REMIND-ENOUGH-BALANCE-FOR-MANUAL-RENEW']);
unset($config['REMIND-NOT-ENOUGH-BALANCE-FOR-AUTO-RENEW']);
unset($config['REMIND-NOT-ENOUGH-BALANCE-FOR-MANUAL-RENEW']);
unset($config['HANDLE-EXPIRE-SUCCESS-FOR-AUTO-RENEW']);
unset($config['HANDLE-EXPIRE-ALERT-NOT-ENOUGH-BALANCE-FOR-AUTO-RENEW']);
unset($config['HANDLE-EXPIRE-ALERT-FOR-MANUAL-RENEW']);

foreach ($config as $key => $contents) {
    if ($content = $contents['sms']) {
        dp([
            'key' => $key,
            'content' => $content
        ], false);
    }
}

$bool = Notify::clientApi(1, 'HANDLE-EXPIRE-ALERT-FOR-MANUAL-RENEW', [
    'instance_name' => 'HOSTNAME',
    'instance_ip' => '127.127.127.127',
    'days' => 10,
    'old_expire' => '2098-05-09 09:05:01',
    'renew_money' => 666,
    'new_expire' => '3098-05-09 09:05:01',
    'expire_date' => '2099-12-12 22:22:22',
    'expire_days' => '99',
    'retain_days' => '999'

]);
Response::bool($bool, '短信发送任务入队%s');
Response::note('#line');
System::complete();