<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Landers\Framework\Services\OAuthHttp;
include 'inc-headline.php';

//php app Tester payorder 3390 & php app Tester payorder 3300

if ( !$instance_id = System::argv(3) ) {
    System::halt('未指定订单ID');
}

$config = Config::get('oauth');
$oauthHttp = new OAuthHttp($config['apiurl'], 'password', $config['client_id'], $config['client_secret'], [
    'username' => '13566680502',
    'password' => '123456'
]);

$instance = Instance::find($instance_id);
$expire = date('Y-m-d H:i:s', $instance['expire']);
Response::note('当前实例%s的过期时间为：%s', $instance_id, $expire);

$apiurl = 'http://api.ulan.com/instance/renew';
$ret = $oauthHttp->post($apiurl, ['instance_id' => $instance_id, 'months' => 1]);

Response::note('续费后的过期时间为：%s', $ret['data']['expire']);

System::complete();