<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;
use GuzzleHttp\Client as Http;

$title = sprintf('【日常事务】（%s）开始工作', System::app('name'));
Response::note([$title,'#dbline']);
$config = Config::get('jdfirewall');
$nowTime = time();

$http = new Http([
    'base_uri' => $config['url'],
    'cookies' => true
]);

//login
$res = $http->request('POST', 'login.cgi', [
    'form_params' => [
        'param_username' => $config['username'],
        'param_password' => $config['password'],
    ]
]);

//save data
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
if (strpos($content, "success") !== false) {
    Response::note('Done');
} else {
    Response::warn($content);
}
System::continues();

