<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试远程调用Hprose服务端...');

$ret = repository('plan')->config();

dp($ret, false);

Response::note('#line');
System::complete();