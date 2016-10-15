<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';


while (true) {
    Response::note('从%s开始：repository(\'tester\')->value() = ', System::startTime(true));
    try {
        $ret = repository('tester')->value();
        Response::echoText($ret);
    } catch (\Exception $e) {
        halt_by_exception($e);
    }
    // usleep(50000);
}

System::complete();