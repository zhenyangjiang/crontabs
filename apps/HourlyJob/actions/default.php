<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use GuzzleHttp\Client as Http;

Response::note([sprintf('【日常事务】（%s）开始工作', System::app('name')),'#dbline']);
StartUp::check();

$config = config('jdfirewall');
$nowTime = time();

$http = new Http([
    'base_uri' => $config['url'],
    'cookies' => true
]);
Response::note("Logining...");
$res = $http->request('POST', 'login.cgi', [
    'form_params' => [
        'param_username' => $config['username'],
        'param_password' => $config['password'],
    ]
]);

Response::note("Saving...");
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

