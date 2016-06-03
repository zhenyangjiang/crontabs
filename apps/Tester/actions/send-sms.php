<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
include 'inc-headline.php';

Response::note('测试短信发送...');

$config = Config::get('notify-content');
unset($config['instance_is_about_to_expire_enough_balance_for_manual_renew']);
unset($config['instance_is_about_to_expire_not_enough_balance_for_auto_renew']);
unset($config['instance_is_about_to_expire_not_enough_balance_for_manual_renew']);
unset($config['instance_auto_renew_success']);
unset($config['instance_expire_retain_auto']);
unset($config['instance_expire_retain_manual']);

foreach ($config as $key => $contents) {
    if ($content = $contents['sms']) {
        dp([
            'key' => $key,
            'content' => $content
        ], false);
    }
}

$bool = Notify::client('instance_expire_retain_manual', 1, [
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