<?php
use Landers\Framework\Core\Response;

Response::note('测试邮件发送...');
$bool = Notify::send_email([
    'tos'       => [
        'luhaixing' => [
            'name'   => 'LANDEDS',
            'email'  => 'luhaixing@qq.com'
        ]
    ],
    'subject'   => '这是邮件标题',
    'content'   => '这是邮件内容'
], $retdat);
Response::bool($bool, '邮件发送任务入队%s');
Response::note('#line');