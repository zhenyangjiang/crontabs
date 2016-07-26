<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Landers\Framework\Services\OAuthHttp;
include 'inc-headline.php';

//php app Tester payorder 3390 & php app Tester payorder 3300

if ( !$order_id = System::argv(3) ) {
    System::halt('未指定订单ID');
}

$config = Config::get('oauth');
$oauthHttp = new OAuthHttp($config['apiurl'], 'password', $config['client_id'], $config['client_secret'], [
    'username' => '13566680502',
    'password' => '123456'
]);

$apiurl = 'http://api.ulan.com/instance-order/pay';
$ret = $oauthHttp->post($apiurl, ['order_id' => $order_id]);
dp($ret, false);

System::complete();