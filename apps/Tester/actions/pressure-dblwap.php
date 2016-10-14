<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';


while (true) {
    Response::note('从%s开始：repository(\'tester\')->dblwap() = ', System::startTime(true));
    try {
        $ret = repository('tester')->dblwap();
        Response::echoText($ret);
    } catch (\Exception $e) {
        System::halt();
    }
    // usleep(50000);
}

System::complete();