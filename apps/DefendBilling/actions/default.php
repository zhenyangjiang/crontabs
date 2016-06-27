<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Queue;

echo PHP_EOL;
Response::note(['【按月防护，按需防护、计费】（'.System::app('name').'）开始工作','#dbline']);

// StartUp::check();

Response::note('正在对牵引过期的IP作解除牵引：');
BlackHole::unblock(); //解除牵引
Response::note('#line');

Response::note('正在从防火墙上获取了攻击信息...');
$pack_attack = Firewall::get_attack();
Response::note('#line');

Response::note('过滤掉已被牵引的IP...');
$pack_attack = DDoSInfo::filte_blocked($pack_attack);
#Response::note('#line');

if ( $pack_attack ) {
    Response::note('正在对攻击数据进行分组...');
    $pack_attack = Firewall::groupBy($pack_attack);
    Response::echoBool($pack_attack);
    Response::note('#line');
}

Response::note('保存攻击数到DDoSInfo...');
$all_ips = DDoSInfo::save($pack_attack);
if ($all_ips) Response::echoSuccess('共计%s条数据', count($all_ips));
Response::note('#line');

//对【数据库中存在，但当前攻击不存在】的IP，作【攻击结束】IP筛选条件范围
Response::note('正在查询被攻击中、且本次未被攻击的IP作攻击自然结束：');
$attaching_ips = Mitigation::get_ips_by_status('ATTACK');

if ($attaching_ips) {
    // foreach ($attaching_ips as $ip) Response::note('#tab%s', $ip);
    Response::note('#tab当前历史中有%sIP正在被攻击中', colorize(count($attaching_ips), 'yellow', 1));
    $diff_ips = array_diff($attaching_ips, $all_ips);
    // $diff_ips = ['172.31.52.244'];

    if ($diff_ips) {
        Response::note(['#blank', '#blank', '---------------- 逐一对以上IP作攻击自然结束：----------------']);

        // 一次性取得所有需要自然结束的IP的云盾
        $mitigations = Mitigation::lists([
            'awhere' => ['ip' => $diff_ips],
            'askey' => 'ip'
        ]);

        foreach ($diff_ips as $ip) {
            Response::note('#line');

            //云盾
            $mitigation = $mitigations[$ip];
            $mitigation = Mitigation::attachs($mitigation);

            switch ($mitigation['billing']) {
                case 'month' :
                    //按月计费
                    Response::note( 'IP：%s，计费方案：按月计费，无需计费', $ip );

                    break;

                case 'hour' :
                    Response::note('IP：%s，计费方案：按需计费，防护阈值：%sMbps / %spps', $ip, $mitigation['ability_mbps'], $mitigation['ability_pps']);
                    //数据中心
                    $datacenter = DataCenter::find($mitigation['datacenter_id']);

                    //获取当前ip所在的数据中心的价格规则(元/小时)的数组
                    $price_rules = DataCenter::price_rules($datacenter, 'hour');

                    //由IP确定用户
                    $uid = $mitigation['uid'];
                    $user = User::find($uid);
                    response_user_detail($user);

                    //由IP确定攻击历史记录
                    $DDoSHistory = DDoSHistory::find_ip_attacking($ip);
                    if ($DDoSHistory) {
                        //有攻击历史，计费扣费
                        Response::note('对此IP进行结算费用：');
                        DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                    } else {
                        $echo = Response::warn('未找到该IP正在被攻击中的历史记录，暂不计费');
                        reportDevException($echo, array('context' => $item));
                    }
                    break;
            }

            //写入攻击自然结束
            Response::note('写入自然攻击结束...：');
            $bool = DDoSHistory::save_end_attack($ip, 'STOP');
            if ($bool) Response::echoBool(true);

            //更新实例网络状态为正常
            Mitigation::setStatus($ip, 'NORMAL');
        }
    } else {
        Response::note('#tab攻击历史中的IP全部存在于当前被攻击IP中，没有IP需要作攻击结束');
    }
} else {
    Response::note('#tab当前历史中没有被攻击中的IP');
}

//空数据包时，任务提前结束
if (!$all_ips) {
    if (!$ori_pack_attack) {
        System::halt('空数据包时，本次任务提前结束');
    } else {
        System::halt('过滤掉后成为空数据包，本次任务提前结束');
    }
}

//记录开始攻击
Response::note('#line');
Response::note('当前所有被攻击IP中，给状态为正常的IP记录攻击开始：');
DDoSHistory::save_start_attack($all_ips);

//攻击中的数据处理
Response::note(['#blank', '#blank', '------------ 开始逐一对所有被攻击的数据中心的被攻击IP操作 ------------']);

foreach ($pack_attack as $dc_id => $group) {
    Response::note('#blank');

    //未归属数据中心的组内的IP按后台设置处理牵引
    if ( (!$dc_id) || (!$datacenter = DataCenter::find($dc_id)) ) {
        $threshold = Settings::get('defendbilling_unalloc_ip_block_threshold');
        $threshold or $threshold = 1000;
        $tmp = sprintf('以下为未启用IP遭到的攻击，将对攻击量超出阈值%sMbps作牵引处理', $threshold);
        $tmp = colorize($tmp, 'yellow');
        Response::note($tmp);

        foreach ($group as $dest_ip => $item) {
            $item = array(
                'mbps' => $item['bps0'],
                'pps' => $item['pps0'],
            );
            Response::note('#line');
            if ($item['mbps'] >= $threshold) {
                Response::note('IP：%s，当前攻击值：%sMbps / %spps，正在牵引...',  $dest_ip, $item['mbps'], $item['pps']);
                BlackHole::block($dest_ip, $item['mbps'], false);
            } else {
                Response::note('IP：%s，当前攻击值：%sMbps / %spps，忽略之...',  $dest_ip, $item['mbps'], $item['pps']);
            }
        }
    } else {
        //***********************开始正常流程**********************

        //对组数据排序，方便有大网安全问题时更精确处理
        $group = Arr::sort($group, 'bps0');

        //本组总攻击量
        $total_mbps = Arr::sum($group, 'bps0');

        //本数据中心最高防护值
        $max_mbps = $datacenter['max_mbps'];

        $tmp = sprintf('当前数据中心【%s】遭受总攻击：%sMbps, 最高防护值：%sMbps', $datacenter['name'], $total_mbps, $max_mbps);
        $tmp = colorize($tmp, 'pink');
        Response::note($tmp);

        // 本组（数据中心）存在大网威胁，优先牵引"包月免费"
        if ( $group_threat = $total_mbps >= $max_mbps ) {
            Response::note('#blank');

            $text = sprintf('总流量%s >= 本组最大防护%s，大网遭受威胁，需对 “包月且免费” 优先牵引....', $total_mbps, $max_mbps);
            Response::note(colorize($text, 'yellow',  'flash'));

            $month_free_ips = [];
            foreach ($group as $dest_ip => $item) {
                $item = array(
                    'mbps' => $item['bps0'],
                    'pps' => $item['pps0'],
                    'mitigation' => $item['mitigation']
                );

                //读取云盾表中该ip的云盾配置
                $mitigation = $item['mitigation'];

                //当前IP的云盾配额
                $ability_mbps = $mitigation['ability_mbps'];
                $ability_pps = $mitigation['ability_pps'];

                //是否免费版云盾
                $is_free = (float)$mitigation['price'] == 0;

                //是否包月
                $is_month = $mitigation['billing'] == 'month';

                //包月且免费，立即牵引
                if ( $is_month && $is_free ) {
                    Response::note('#line');

                    $month_free_ips[] = $dest_ip;

                    //按月计费：仅防护，由ExpireHandler进行到期扣取次月
                    Response::note('IP：%s，计费方案：按月计费，防护阈值：%sMbps / %spps', $dest_ip, $ability_mbps, $ability_pps);

                    Response::note('当前攻击速率：%sMbps，攻击报文：%spps', $item['mbps'], $item['pps']);

                    if (BlackHole::block($dest_ip, $item['mbps'], true)) {
                        $total_mbps -= $item['mbps'];
                    }
                }
            }
            $text = sprintf('共计 %s 个“包月且免费IP” 牵引完成', count($month_free_ips));
            Response::note(['#line', colorize($text, 'green',  'flash')]);
        }

        foreach ($group as $dest_ip => $item) {
            Response::note('#line');
            $item = array(
                'mbps' => $item['bps0'],
                'pps' => $item['pps0'],
                'mitigation' => $item['mitigation']
            );

            //读取云盾表中该ip的云盾配置
            $mitigation = $item['mitigation'];

            //当前IP的云盾配额
            $ability_mbps = $mitigation['ability_mbps'];
            $ability_pps = $mitigation['ability_pps'];

            switch ($mitigation['billing']) {
                case 'month' :
                    //按月计费：仅防护，由ExpireHandler进行到期扣取次月
                    $text1 = sprintf('IP：%s，计费方案：按月计费，防护阈值：%sMbps / %spps', $dest_ip, $ability_mbps, $ability_pps);
                    $text2 = sprintf('当前攻击速率：%sMbps，攻击报文：%spps', $item['mbps'], $item['pps']) ;

                    //是否免费版云盾
                    $is_free = (float)$mitigation['price'] == 0;

                    if ( $is_free && $group_threat ) {
                        Response::note([
                            colorize($text1, 'gray'),
                            colorize($text2, 'gray'),
                            colorize('免费包月云盾在大网安全受威胁时，早已优先牵引了', 'gray')
                        ]);
                    } else {
                        Response::note([$text1, $text2]);
                        if ($item['mbps'] >= $ability_mbps) {
                            Response::note('当前攻击速率到达所购买防护阈值，正在牵引...');
                            BlackHole::block($dest_ip, $item['mbps'], false);
                        } else if ($item['pps'] >= $ability_pps) {
                            Response::note('当前攻击报文到达所购买防护阈值，正在牵引...');
                            BlackHole::block($dest_ip, $item['mbps'], false);
                        } else {
                            Response::note('当前攻击值：%sMbps/%spps 未达到购买防护阈值，继续清洗...', $item['mbps'], $item['pps']);
                        }
                    }
                    break;

                case 'hour' :
                    //按需计费：先防护后计前1小时的费用，单价采用所属数据中心中的价格，按需无免费
                    Response::note('IP：%s，计费方案：按需计费，防护阈值：%sMbps / %spps', $dest_ip, $ability_mbps, $ability_pps);

                    Response::note('当前攻击速率：%sMbps，攻击报文：%spps', $item['mbps'], $item['pps']);

                    Response::note('由IP确定攻击历史记录：');
                    $DDoSHistory = DDoSHistory::find_ip_attacking($dest_ip);

                    if (!$DDoSHistory) {
                        if ( $total_mbps >= $max_mbps ) {
                            $echo = Response::warn('有待考证#1');
                            Notify::developer($echo);
                            //经过不断对 $total_mbps 做减算，还是超过了最高防护，继续牵引
                            // $text = sprintf('当前总流量 %s >= 大网安全流量 %s，超大网安全，需立即强制牵引 >>>', $total_mbps, $max_mbps);
                            // Response::note(colorize($text, 'yellow',  'flash'));
                            // if (BlackHole::block($dest_ip, $item['mbps'], true)) {
                            //     $total_mbps -= $item['mbps'];
                            // }
                        } else  {
                            $msg = '未找到该IP正在被攻击中的历史记录';
                            Notify::developer($msg);
                        }
                    } else {
                        $DDoSHistory_id = $DDoSHistory['id'];
                        Response::note('#tab当前攻击的所属历史记录ID：%s', $DDoSHistory_id);

                        //获取当前ip所在的数据中心的价格规则(元/小时)的数组
                        $price_rules = DataCenter::price_rules($datacenter, 'hour');

                        //由云盾->用户->余额
                        $uid = $mitigation['uid'];
                        $user = User::find($uid);
                        response_user_detail($user);

                        if ( $total_mbps >= $max_mbps ) {
                            //经过不断对 $total_mbps 做减算，还是超过了最高防护，继续牵引
                            $text = sprintf('当前总流量 %s >= 大网安全流量 %s，超大网安全，需立即强制牵引 >>>', $total_mbps, $max_mbps);
                            Response::note(colorize($text, 'yellow',  'flash'));

                            // 在本次牵引之前，此ip是否被牵引了
                            $block_exists = BlackHole::exists($ip);

                            //强制牵引
                            if (BlackHole::block($dest_ip, $item['mbps'], true)) {
                                $total_mbps -= $item['mbps'];
                            }

                            //计费扣费
                            if ( $block_exists ) {
                                Response::note(' 在本次牵引之前，此IP就已被牵引了，无需再计费...');
                            } else {
                                DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
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
                                if (BlackHole::exists($ip)) {
                                    $echo = Response::warn('异常：IP：%s, 已经处于牵引中，无需操作，无需计费', $dest_ip);
                                    reportDevException($echo);
                                    //已经存在牵引中了，可能由于防火强还没处理牵引请求造成的延时
                                } else {
                                    Response::note('需要对IP：%s作牵引处理', $dest_ip);
                                    BlackHole::block($dest_ip, $item['mbps'], 御前);

                                    //计费扣费
                                    DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                                }
                            } else {
                                //超过?否
                                //模拟本次攻击总计，检查余额是否足以支付
                                $fee = DDoSHistory::calcFee($DDoSHistory, $price_rules);

                                if ( $fee > $user['money'] ) {
                                    Response::note('模拟总计费用：￥%s，已超出用户余额：%s，需立即处理：', $fee, $user['money']);

                                    //产生的总费用超过用户余额，作牵引处理
                                    BlackHole::block($dest_ip, $item['mbps'], false);

                                    //计费扣费
                                    DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                                } else {
                                    Response::note('模拟总计费用：￥%s，当前余额：￥%s 足以支付，继续清洗中...', $fee, $user['money']);
                                }
                            }
                        }
                    }
                    break;
            }
        }
    }
}

System::continues();
?>
