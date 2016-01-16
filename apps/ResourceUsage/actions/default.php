<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Log;
// use Landers\Utils\Http;

Log::note(array('【CPU，网络流量】（'.System::app('name').'）开始工作','#dbline'));
$data = VirtTop::getData($error);

Monitor::debug();
Monitor::import($data);
?>
