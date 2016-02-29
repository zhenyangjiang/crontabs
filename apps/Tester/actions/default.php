<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Utils\Datetime;

dp(BlackHole::doBlock('123.1.1.10', 6000), false);
dp(BlackHole::doBlock('123.1.1.101', 6000));
BlackHole::doUnblock('123.1.1.10');
BlackHole::doUnblock('123.1.1.101'); exit();