<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Modules\Log;
include 'inc-headline.php';



Response::note('测试告警通知...');

// Alert::ipBlock('123.1.1.11', [
//     'reason' => '超大网安全'
// ]);

Alert::ipUnBlock(['123.1.1.11']);

Response::note('#line');
System::complete();