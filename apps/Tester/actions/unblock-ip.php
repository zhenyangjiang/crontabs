<?php
use Landers\Framework\Core\Response;

Response::note('测试解除牵引IP...');
$bool = !!BlackHole::doUnblock('123.1.1.10', 6000);
Response::bool($bool, '解除牵引IP入队%s');
Response::note('#line');
