<?php
use Landers\Framework\Core\Response;
use Landers\Framework\Core\System;

$title = sprintf('【%s单元测试】开始工作', System::app('name'));
Response::note([$title,'#dbline']);
?>