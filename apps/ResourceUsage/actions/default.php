<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Log;
use Landers\Framework\Core\Config;
use Landers\Utils\Http;

while(true) {
    Log::note(array('【实例资源(CPU, NET)用量采集器】（'.System::app('name').'）开始工作','#dbline'));
    $data = VirtTop::getData($error);
    if (!$data) {
        Log::error('数据采集失败'); exit();
    }

    Log::note('数据采集成功');
    $url = Config::getDefault('postUrl');
    $content = Http::post($url, $data);
    $response = json_decode($content);
    if ($response->success) {
        Log::note('数据入库成功');
    } else {
        Log::error('数据入库成功');
    }
    System::continues(false);
}
?>
