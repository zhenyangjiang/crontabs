<?php
use Landers\Framework\Core\Response;

Response::note('测试牵引IP...');
$bool = !!BlackHole::doBlock('123.1.1.10', 6000);
Response::bool($bool, '牵引IP入队%s');
Response::note('#line');