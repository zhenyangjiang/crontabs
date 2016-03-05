<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Utils\Datetime;

Response::note('测试邮件发送...');
$bool = Notify::send_email([
    'tos'       => [
        'luhaixing' => [
            'name'   => 'LANDEDR',
            'email'  => 'luhaixing@qq.com',
            'mobile' => '13566680502'
        ]
    ],
    'subject'   => '这是邮件标题',
    'content'   => '这是邮件内容'
], $retdat);
Response::noteSuccessFail('邮件发送%s', $bool);
Response::note('#line');

Response::note('测试牵引IP...');
$bool = !!BlackHole::doBlock('123.1.1.10', 6000);
Response::noteSuccessFail('牵引IP入队%s', $bool);
Response::note('#line');

Response::note('测试解除牵引IP...');
$bool = !!BlackHole::doUnblock('123.1.1.10', 6000);
Response::noteSuccessFail('解除牵引IP入队%s', $bool);
Response::note('#line');
