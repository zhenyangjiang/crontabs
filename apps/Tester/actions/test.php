<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';


while (true) {
    Response::note('repository(\'tester\')->index() = ');
    try {
        $ret = repository('tester')->index();
        Response::echoText($ret);
    } catch (\Exception $e) {
        System::halt();
    }
    usleep(50000);
}

System::complete();