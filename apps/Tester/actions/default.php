<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Datetime;

include 'inc-headline.php';

$ret =runlog(1, 'MONEY', 'It is a message', [
    'a' => 'aaa'
]);
dp($ret);

// $now = date('Y-m-d H:i:s');
// $hours = 4;

// Response::note('当前时间：%s', $now);

// $ret = Datetime::duringBeforeHours($hours, $now);


// Response::note('%s 小时之前的时间段为：%s～%s', $hours, $ret['begin_text'], $ret['end_text']);

Response::note('#line');
System::complete();