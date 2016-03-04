<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Utils\Datetime;

$msg = '未找到该IP正在被攻击中的历史记录';
dp(Notify::developer($msg));

dp(BlackHole::doBlock('123.1.1.10', 6000), false);
dp(BlackHole::doUnblock('123.1.1.101', 6000));
