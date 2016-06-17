<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试牵引IP...');

if ( !$ip = System::argv(3) ) {
    System::halt('未指定牵引IP');
}
!!BlackHole::block($ip, 6000, true);
Response::note('#line');
System::complete();