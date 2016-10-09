<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use GuzzleHttp\Client as Http;
use ULan\Repository\JDFirewall;

Response::note([sprintf('【时常事务】（%s）开始工作', System::app('name')),'#dbline']);
StartUp::check();


Response::note("保存防火墙数据");
$config = JDFirewall::getConfig();
$nowTime = time();
$http = new Http([
    'base_uri' => $config['base_uri'],
    'cookies' => true
]);
Response::note("登陆防火墙中...");
$res = $http->request('POST', 'login.cgi', [
    'form_params' => [
        'param_username' => $config['username'],
        'param_password' => $config['password'],
    ]
]);
Response::note("防火墙数据回存中...");
$res = $http->request('POST', 'setting_config.cgi', [
    'form_params' => [
        'param_submit_type' => 'submit',
        'param_devaddr' => 'ON',
        'param_global' => 'ON',
        'param_portpro' => 'ON',
        'param_host' => 'ON',
        'param_filter' => 'ON',
    ]
]);
$content = $res->getBody()->getContents();
if (preg_match('~<info>([^<]+)</info>~', $content, $match)) {
    $info = $match[1];
    if (strpos($content, "success") !== false) {
        Response::note($info);
    } else {
        Response::warn($info);
    }
} else {
    Response::warn($content);
}





System::continues();

