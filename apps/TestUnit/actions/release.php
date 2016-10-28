<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试释放所有牵引到期的IP：');
$ips = IPBase::release();
Response::bool($ips, '操作%s');

Response::note('#line');
System::complete();
