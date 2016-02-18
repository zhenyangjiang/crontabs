<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Landers\Utils\Http;

while(true) {
    Response::reInit();
    Response::note(array('【实例资源(CPU, NET)用量采集器】（'.System::app('name').'）开始工作','#dbline'));
    $data = VirtTop::getData($error);
    if (!$data) {
        Response::error('数据采集失败'); exit();
    }

    Response::note('数据采集成功');
    $url = Config::getDefault('postUrl');
    $content = Http::post($url, $data);
    $response = json_decode($content);
    if ($response->success) {
        Response::note('数据入库成功');
    } else {
        Response::error('数据入库成功');
    }
    System::continues(5);
}
?>
