<?php

use Landers\Framework\Core\System;
use Landers\Framework\Core\Log;
use Landers\Framework\Core\Queue;

Log::note(['【任务队列监听器】（'.System::app('name').'）开始工作','#dbline']);
(new Queue())->listen();
