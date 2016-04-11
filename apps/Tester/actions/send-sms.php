<?php
use Landers\Framework\Core\Response;

Response::note('测试短信发送...');
$bool = Notify::send_sms('13566680506', '看看不在模板范围内会是什么样的 ');
Response::bool($bool, '短信发送任务入队%s');
Response::note('#line');