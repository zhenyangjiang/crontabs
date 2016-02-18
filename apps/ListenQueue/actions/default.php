<?php

use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;

Response::note(['【任务队列监听器】（'.System::app('name').'）开始工作','#dbline']);
(new Queue())->listen();
