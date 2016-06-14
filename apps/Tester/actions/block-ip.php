<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试牵引IP...');
$bool = !!BlackHole::doBlock('123.1.1.10', 6000, true);
Response::bool($bool, '牵引IP入队%s');
Response::note('#line');
System::complete();