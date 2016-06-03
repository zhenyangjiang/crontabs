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
if ($file) {
    Response::echoSuccess($file . '追加成功');
} else {
    Response::bool(false);
}
Response::note('#line');
System::complete();