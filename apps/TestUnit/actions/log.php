<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试写日志...');
$file = AppLog::info('kkkkkkkk', [
    'aaaa' => '111111111',
    'bbb' => '222222222222222'
], true);
Response::echoBool( !!$file, '测试%s');
Response::note($file);

Response::note('#line');
System::complete();