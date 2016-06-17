<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;

$queue = System::argv(3);
if (!$queue) System::halt('未指定队列名称！');

$config = Config::get('queue', $queue);
if (!$config) System::halt('不存在队列标识“%s”', $queue);

$title = sprintf('【%s监听器】（%s）开始工作', $config['name'], System::app('name'));
Response::note([$title,'#dbline']);
Queue::singleton($queue)->listen();
