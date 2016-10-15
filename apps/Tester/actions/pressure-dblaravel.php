<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';


while (true) {
    Response::note('从%s开始：repository(\'tester\')->dblaravel() = ', System::startTime(true));
    try {
        $ret = repository('tester')->dblaravel();
        Response::echoText($ret['id']);
    } catch (\Exception $e) {
        halt_by_exception($e);
    }
    // usleep(50000);
}

System::complete();