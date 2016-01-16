<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Log;
use Landers\Utils\Datetime;

Log::note(['【CPU，网络流量】（'.System::app('name').'）开始工作','#dbline']);

$str = VirtTop::getData($error);

if ($str) {
    $data = VirtTop::parseData($str);
} else {

}
?>
