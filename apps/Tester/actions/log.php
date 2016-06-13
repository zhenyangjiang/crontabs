<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Modules\Log;
include 'inc-headline.php';

Response::note('测试写日志...');
$file = Log::info('kkkkkkkk', [
    'aaaa' => '111111111',
    'bbb' => '222222222222222'
]);

Response::echoSuccess(!!$file, '追加%s');

Response::note('#line');
System::complete();