<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试 DDoSHistoryhourePirce ...');

if ( !$ip = System::argv(3) ) {
    System::halt('未指定IP');
}

$datacenter = DataCenter::findByIp($ip);
$price_rules = DataCenter::priceRules($datacenter, 'hour');

$history = DDoSHistory::findByAttackingIp($ip);

$ret = DDoSHistory::calcFee($history, $price_rules, $peak_info = array(), $duration);

Response::note('持续时间：%s', $duration);

Response::note('产生费用：%s', $ret);

System::complete();

