
<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Queue;


// IPBase::update(['status' => 'ATTACK'], 77);



echo PHP_EOL;

Response::note(['【按月防护，按需防护、计费】（'.System::app('name').'）开始工作','#dbline']);

//StartUp::check();

//
//Response::note('正在对牵引过期的IP作解除牵引：');
//IPBase::release(); //释放牵引
//Response::note('#line');

Response::note('正在从防火墙上获取了攻击信息...');
$pack_attack1  = Firewall::getAttacks();


Response::note('#line');
//
Response::note('过滤掉已被牵引的IP...');
$pack_attack1 = DDoSInfo::filteBlocked($pack_attack1);
Response::note('#line');

if ( $pack_attack1 ) {
    Response::note('正在对攻击数据进行分组...');
    $pack_attack2 = IPBase::groupBy($pack_attack1);

    Response::echoBool(!!$pack_attack2);
    Response::note('#line');
} else {
    $pack_attack2 = [];
}
//
Response::note('保存攻击数据到DDoSInfo...');
if ($all_ips = DDoSInfo::save($pack_attack2)) {

    Response::echoSuccess('成功：%s', count($all_ips));
} else {
    Response::echoBool(false);
}
Response::note('#line');

// if ( count($pack_attack1) != count($all_ips) ) {
//     $text = sprintf('pack_attack1的数量%s与pack_attack2的数量%s不一至！', count($pack_attack1), count($all_ips));
//     throw new Exception($text);
// }

//对【数据库中存在，但当前攻击不存在】的IP，作【攻击结束】IP筛选条件范围
Response::note('正在查询被攻击中、且本次未被攻击的IP作攻击自然结束：');
$attaching_ips = IPBase::getByStatus('ATTACK');
$diff_ips = array_diff($attaching_ips, $all_ips);


// if ($attaching_ips) {
//     Response::warn('制造IP:%s攻击自然结束', $attaching_ips[0]);
//     $all_ips = Arr::remove($all_ips, $attaching_ips[0]);
// }

if ($attaching_ips) {
    Response::note('#tab当前历史中有%sIP正在被攻击中', colorize(count($attaching_ips), 'yellow', 1));
    $diff_ips = array_diff($attaching_ips, $all_ips);

    if ($diff_ips) {
        Response::note('#tab其中有%sIP已停止攻击：', colorize(count($diff_ips), 'yellow', 1));
        Response::note('#tab%s', implode('，', $diff_ips));
        Response::note(['#blank', '#blank', '--------------------- 逐一对结束攻击的IP作攻击自然结束：--------------------']);

        // 一次性取得所有需要自然结束的IP的云盾
        $ipbases = IPBase::lists([
            'awhere' => ['ip' => $diff_ips],
            'askey' => 'ip'
        ]);

        $mitigations = IPBase::getMitigations($ipbases);

        foreach ($ipbases as $item) {
            $dest_ip = $item['ip'];
            $mit_id = $item['mit_id'];
            Response::note('IP：%s，所属云盾：%s', $dest_ip, $mit_id);

            //云盾
            $mitigation = $mitigations[$mit_id];
            $mitigation = Mitigation::attachs($mitigation);

            //确定用户
            $uid = $mitigation['uid'];
            $user = User::find($uid);

            switch ($mitigation['billing']) {
                case 'month' :
                    //按月计费
                    Response::note( '计费方案：按月计费，无需计费', $dest_ip );

                    break;

                case 'hour' :
                    Response::note('计费方案：按需计费，防护阈值：%sMbps / %spps', $mitigation['ability_mbps'], $mitigation['ability_pps']);
                    response_user_detail($user);

                    //数据中心
                    $datacenter = DataCenter::find($mitigation['datacenter_id']);
                    print_r($datacenter);

                    //获取当前ip所在的数据中心的价格规则(元/小时)的数组
                    $price_rules = DataCenter::priceRules($datacenter, 'hour');

                    //由IP确定攻击历史记录
                    $DDoSHistory = DDoSHistory::findByAttackingIp($dest_ip);
                    if ($DDoSHistory) {
                        Response::note('对此云盾IP进行结算费用：');
                        if ( Mitigation::isTrial($mitigation) ) {
                            Response::echoWarn('试用期云盾免计费。');
                        } else {
                            //有攻击历史，计费扣费
                            DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                        }
                    } else {
                        $echo = Response::warn('未找到该IP正在被攻击中的历史记录，暂不计费');
                        reportDevException($echo, array('context' => $item));
                    }
                    break;
            }

            //写入攻击自然结束
            Response::note('写入自然攻击结束:');
            $bool = DDoSHistory::saveAttackEnd($dest_ip, 'STOP');
            if (!$bool) Response::bool($bool, '自然攻击结束执行%s');

            //更新云盾状态为正常
            Response::note('更新IP状态为正常...');
            $bool = IPBase::setStatus($dest_ip, 'NORMAL', true);
            Response::echoBool($bool);

            Response::note('#line');

        }
    } else {
        Response::note('#tab攻击历史中的IP全部存在于当前被攻击IP中，没有IP需要作攻击结束');
    }
} else {
    Response::note('#tab当前历史中没有被攻击中的IP');
}

//空数据包时，任务提前结束
if (!$all_ips) {
    Response::note('#line');
    if (!$ori_pack_attack) {
        System::halt('空数据包时，本次任务提前结束');
    } else {
        System::halt('过滤掉后成为空数据包，本次任务提前结束');
    }
}
print_r(DataCenter::lowestPriceCase(DataCenter::find(1),'month'));

// pause();

//记录开始攻击
Response::note(['#blank', '#blank', '---------------------------- 记录DDoS攻击 ----------------------------']);
Response::note('当前所有被攻击IP中，给状态为正常的IP记录攻击开始：');
$start_attack_ips = DDoSHistory::saveAttackStart($all_ips);
Alert::beginDDoS($start_attack_ips);
Response::note('#line');

//攻击中的数据处理
Response::note(['#blank', '#blank', '------------ 开始逐一对所有被攻击的数据中心的被攻击IP操作 ------------']);



foreach ($pack_attack2 as $dc_id => $mitigations) {
    Response::note('#blank');

    //未归属数据中心的组内的IP按后台设置处理牵引
    if ( (!$dc_id) || (!$datacenter = DataCenter::find($dc_id)) ) {
        $threshold = Settings::get('defendbilling_unalloc_ip_block_threshold');
        $threshold or $threshold = 1000;

        Response::noteColor('pink', '以下为未启用IP遭到的攻击，将对攻击量超出阈值%sMbps作牵引处理', $threshold);
        continue;

        if ($mitigation = &$mitigations[0]) {
            $ddosinfos = &$mitigation['ddosinfos'];
            foreach ($ddosinfos as $item) {
                $dest_ip = $item['dest'];
                $item = array(
                    'mbps' => $item['bps0'],
                    'pps' => $item['pps0'],
                );
                Response::note('#line');
                if ($item['mbps'] >= $threshold) {
                    Response::note('IP：%s，当前攻击值：%sMbps / %spps，需要牵引',  $dest_ip, $item['mbps'], $item['pps']);
                    IPBase::block($dest_ip, $item['mbps']);
                } else {
                    Response::note('IP：%s，当前攻击值：%sMbps / %spps，忽略之',  $dest_ip, $item['mbps'], $item['pps']);
                }
            }
        }
    } else {
        // pause();
        //***********************开始正常流程**********************
        //每个云盾的ddosinfos包中bps0改为名为mbps, pps0改名为pps, 计算所遭到的总攻击
        foreach ($mitigations as &$mitigation) {
            $mitigation = Mitigation::attachs($mitigation);
            $sum_mbps = (float)0; $sum_pps = (float)0;
            foreach ($mitigation['ddosinfos'] as &$item) {
                $item = [
                    'dest' => $item['dest'],
                    'mbps' => $item['bps0'],
                    'pps' => $item['pps0'],
                ];
                $sum_mbps += $item['mbps'];
                $sum_pps += $item['pps'];
            }; unset($item);
            $mitigation['sum_mbps'] = (float)$sum_mbps;
            $mitigation['sum_pps'] = (float)$sum_pps;
        }; unset($mitigation);

        //对组数据排序，方便有大网安全问题时更精确处理
        $mitigations = Arr::sort($mitigations, 'sum_mbps');

        //本组总攻击量
        $total_mbps = Arr::sum($mitigations, 'sum_mbps');

        //本数据中心最高防护值
        $max_mbps = $datacenter['max_mbps'];

        Response::noteColor('pink', '当前数据中心【%s】遭受总攻击：%sMbps, 最高防护值：%sMbps', $datacenter['name'], $total_mbps, $max_mbps);

        // 本组（数据中心）存在大网威胁，优先牵引免费版"包年包月"
        $total_mbps = 300000.1;
        if ( $total_mbps >= $max_mbps) {
            Response::note('#tab本组（数据中心）存在大网威胁，优先牵引免费版"包年包月"');
            free::handles($mitigations,$total_mbps,$max_mbps);


        }

        // 对“按需计费”进行遍历操作
        foreach ($mitigations as $index => $mitigation) {
            if ($mitigation['billing'] != 'hour') continue;
            Response::note('#line');

            //当前IP的云盾配额
            $ability_mbps = $mitigation['ability_mbps'];
            $ability_pps = $mitigation['ability_pps'];

            //获取当前ip所在的数据中心的价格规则(元/小时)的数组
            $price_rules = DataCenter::priceRules($datacenter, 'hour');

            //显示云盾详情
            response_mitigation_detail($mitigation);

            //由云盾->用户->余额
            $uid = $mitigation['uid'];
            $user = User::find($uid);
            response_user_detail($user);

            $ddosinfos = $mitigation['ddosinfos'];
            if (count($ddosinfos) >= 2) {
                $echo = Response::warn('云盾ID:%s为按需计费，却存在2个以上的对应的IP', $mitigation['id']);
                reportDevException($echo, compact('mitigation'));
            }
            $item = &$ddosinfos[0];

            $dest_ip = $item['dest'];

            $DDoSHistory = DDoSHistory::findByAttackingIp($dest_ip);

            if (!$DDoSHistory) {
                if ( $total_mbps >= $max_mbps ) {
                    $echo = Response::warn('有待考证#1');
                    Notify::developer($echo);
                } else  {
                    $msg = '未找到该IP正在被攻击中的历史记录';
                    Notify::developer($msg);
                }
            } else {
                Response::note('当前攻击的所属历史记录ID：%s', $DDoSHistory['id']);

                if ( $total_mbps >= $max_mbps ) {
                    //经过不断对 $total_mbps 做减算，还是超过了最高防护，继续牵引
                    $text = sprintf('当前总流量 %s >= 大网安全流量 %s，超大网安全，需立即强制牵引 >>>', $total_mbps, $max_mbps);
                    Response::note(colorize($text, 'yellow',  'flash'));

                    // 在本次牵引之前，此ip是否被牵引了
                    $block_exists = BlackHole::exists($dest_ip);

                    //强制牵引
                    if (IPBase::block($dest_ip, $item['mbps'], 'force')) {
                        $total_mbps -= $item['mbps'];
                        Alert::ipBlock($dest_ip, [
                            'reason' => '超大网安全且被攻击速率过高'
                        ]);
                    }

                    //计费扣费
                    Response::note('对此云盾IP进行结算费用：');
                    if ( $block_exists ) {
                        Response::reply(' 在本次牵引之前，此IP就已被牵引了，无需再计费...');
                    } else {
                        if ( Mitigation::isTrial($mitigation) ) {
                            Response::echoWarn('试用期云盾免计费。');
                        } else {
                            //当前攻击是否超过用户购买的最高防护能力
                            if ($item['mbps'] > $ability_mbps || $item['pps'] > $ability_pps) {
                                $text = '当前攻击已超过用户购买防护阈值，按用户购买值计算';
                                Response::note($text);
                                DDoSHistory::billing($uid, $DDoSHistory, $price_rules, $ability_mbps);
                            } else {
                                $text = '当前攻击未超过用户购买防护阈值，按实际峰值计算';
                                DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                            }
                        }
                    }
                } else {
                    //当前攻击是否超过用户购买的最高防护能力
                    if ($item['mbps'] > $ability_mbps || $item['pps'] > $ability_pps) {
                        //超过?是
                        if ($item['mbps'] > $ability_mbps) {
                            Response::note('到达用户最高承受支付能力防护值：%sMbps', $ability_mbps);
                        } else {
                            Response::note('到达用户最高承受支付能力防护值：%spps', $ability_pps);
                        }

                        //牵引
                        if (BlackHole::exists($dest_ip)) {
                            $echo = Response::warn('异常：IP：%s, 已经处于牵引中，无需操作，无需计费', $dest_ip);
                            reportDevException($echo);
                            //已经存在牵引中了，可能由于防火强还没处理牵引请求造成的延时
                        } else {
                            Response::note('需要对IP：%s作牵引处理', $dest_ip);
                            IPBase::block($dest_ip, $item['mbps'], 'force');
                            Alert::ipBlock($dest_ip, [
                                'reason' => '攻击速率到达所购买防护阈值'
                            ]);

                            Response::note('对此云盾IP进行结算费用：');
                            if ( Mitigation::isTrial($mitigation) ) {
                                Response::echoWarn('试用期云盾免计费。');
                            } else {
                                //计费扣费
                                DDoSHistory::billing($uid, $DDoSHistory, $price_rules, $ability_mbps);
                            }
                        }
                    } else {
                        //超过?否
                        //模拟本次攻击总计，检查余额是否足以支付
                        $fee = DDoSHistory::calcFee($DDoSHistory, $price_rules, $peak_info, $duration);

                        Response::note('持续时间：%s小时，模拟总计费用：￥%s', $duration, $fee);
                        if ( $fee > $user['money'] ) {
                            Response::note('已超出用户余额：%s，需立即处理：', $fee, $user['money']);

                            //产生的总费用超过用户余额，作牵引处理
                            IPBase::block($dest_ip, $item['mbps']);
                            Alert::ipBlock($dest_ip, [
                                'reason' => '您的余额不足，无法继续按需防护'
                            ]);

                            Response::note('对此云盾IP进行结算费用：');
                            if ( Mitigation::isTrial($mitigation) ) {
                                Response::echoWarn('试用期云盾免计费。');
                            } else {
                                //计费扣费
                                DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                            }
                        } else {
                            Response::note('当前余额：￥%s 足以支付，继续清洗中...', $user['money']);
                        }
                    }
                }
            }
        }

        // 对“包年包月”进行遍历操作
        foreach ($mitigations as $index => $mitigation) {
            if ($mitigation['billing'] != 'month') continue;

            $uid = $mitigation['uid'];

            //当前IP的云盾配额
            $ability_mbps = $mitigation['ability_mbps'];
            $ability_pps = $mitigation['ability_pps'];

            Response::note('#line');
            response_mitigation_detail($mitigation);

            if ( $total_mbps >= $max_mbps ) {
                Mitigation::block(
                    $mitigation, '超大网安全',
                    function($item) use (&$total_mbps) {
                        $total_mbps -= $item['mbps'];
                    }
                );
            } else {
                if ($mitigation['sum_mbps'] >= $ability_mbps) {
                    Response::reply('当前攻击速率到达所购买防护阈值，需要牵引...');
                    $reason = sprintf('攻击速率%s到达所购买防护阈值', $mitigation['sum_mbps']);
                    Mitigation::block($mitigation, $reason, function(){

                    });
                    run_log($uid, 'MITIGATION', sprintf('%s, 执行牵引', $reason));

                } else if ($mitigation['sum_pps'] >= $ability_pps) {
                    Response::reply('当前攻击报文到达所购买防护阈值，需要牵引...');
                    $reason = sprintf('攻击报文%s到达所购买防护阈值', $item['pps']);
                    Mitigation::block($mitigation, $reason, function($item) use (&$total_mbps) {
                        $total_mbps -= $item['mbps'];
                    });
                    run_log($uid, 'MITIGATION', sprintf('%s, 执行牵引', $reason));

                } else {
                    Response::reply('未达到购买防护阈值，继续清洗...');
                }
            }

        }
    }
}

System::continues();
?>
