<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::relay('根据指定值bps：%sMbps, 构造峰值信息', 'aaabbb');


System::complete();