<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试客户通知发送...');
$bool = Notify::clientApi(23, 'HANDLE-EXPIRE-ALERT-FOR-MANUAL-RENEW', [
    'instance_name' => '云主机名称',
    'instance_ip'   => '192.168.0.0',
    'days'   => 3,
    'expire_date'   => '2016-6-6',
]);
Response::bool($bool, '客户通知%s');
Response::note('#line');
System::complete();