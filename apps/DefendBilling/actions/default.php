<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Queue;

echo PHP_EOL;
Response::note(['#blank', '【按月防护，按需防护、计费】（'.System::app('name').'）开始工作','#dbline']);

//解除之前牵引
Response::note('正在对牵引过期的IP作解除牵引：');
BlackHole::unblock(); //解除牵引
Response::note('#line');

//读取防火墙数据
Response::note('正在从防火墙上获取了攻击信息...');
$pack_attack = Firewall::get_attack();
// if (ENV_debug == true) $pack_attack = Firewall::make_attacks($pack_attack);

//对攻击数据进行分组
$pack_attack = Firewall::groupBy($pack_attack);
Response::bool($pack_attack);

//保存攻击数据
Response::note(['#line', '保存攻击数到DDoSInfo...']);
$all_ips = DDoSInfo::save_attack($pack_attack);
Response::echoSuccess('成功导入%s条数据', count($all_ips));
Response::note('#line');

//对【数据库中存在，但当前攻击不存在】的IP，作【攻击结束】IP筛选条件范围
Response::note('正在查询被攻击中、且本次未被攻击的IP作攻击自然结束：');
$attaching_ips = DDoSHistory::get_attacking_ips();

if ($attaching_ips) {
    // foreach ($attaching_ips as $ip) Response::note('#tab%s', $ip);
    Response::note('#tab当前历史中有%sIP正在被攻击中', colorize(count($attaching_ips), 'yellow', 1));
    $diff_ips = array_diff($attaching_ips, $all_ips);
    // $diff_ips = ['172.31.52.244'];

    if ($diff_ips) {
        Response::note(['#blank', '#blank', '逐一对以上IP作攻击自然结束：']);

        // 一次性取得所有需要自然结束的IP的云盾
        $mitigations = Mitigation::lists([
            'awhere' => ['ip' => $diff_ips],
            'askey' => 'ip'
        ]);

        foreach ($diff_ips as $ip) {
            Response::note('#line');

            //云盾
            $mitigation = $mitigations[$ip];

            switch ($mitigation['billing']) {
                case 'month' :
                    //按月计费
                    Response::note( 'IP：%s，计费方案：包月包月，无需计费', $ip );
                    break;

                case 'hour' :
                    Response::note( 'IP：%s，计费方案：按月计费', $ip );

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
                        DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                    } else {
                        $echo = Response::warn('未找到该IP正在被攻击中的历史记录，暂不计费');
                        reportDevException($echo, array('context' => $item));
                    }
                    break;
            }

            //写入攻击自然结束
            Response::note('#tab写入自然攻击结束...');
            DDoSHistory::save_end_attack($ip, 'stop');

            //更新实例网络状态为正常
            Instance::update_net_status($ip, 0);
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

        // 大网威胁
        $group_threat = $total_mbps >= $max_mbps;

        foreach ($group as $dest_ip => $item) {
            Response::note('#line');
            $item = array(
                'mbps' => $item['bps0'],
                'pps' => $item['pps0'],
                'mitigation' => $item['mitigation']
            );

            //读取云盾表中该ip的云盾配置
            $mitigation = $item['mitigation'];

            if ( !$mitigation ) {
                //找不到记录：（可能是第二IP，暂属异常），超100Mbps即牵引
                $echo = Response::note('IP：%s，未知的异常IP地址', $dest_ip);
                reportOptException($echo, array('context' => $item));

                if ( $group_threat ) {
                    Response::note('存在大网安全问题需立即牵引');
                    if (BlackHole::block($dest_ip, $item['mbps'], true)) {
                        $total_mbps -= $item['mbps'];
                    }
                } else {
                    if ($item['mbps'] >= 100 || $item['pps'] >= 100000) {
                        Response::note('当前攻击值：%sMbps / %spps，正在牵引...', $item['mbps'], $item['pps']);
                        BlackHole::block($dest_ip, $item['mbps'], false);
                    } else {
                        Response::note('当前攻击值：%sMbps/%spps，继续清洗...', $item['mbps'], $item['pps']);
                    }
                }
            } else {
                //当前IP的云盾配额
                $max_mbps = $mitigation['ability_mbps'];
                $max_pps = $mitigation['ability_pps'];

                switch ($mitigation['billing']) {
                    case 'month' :
                        //是否免费版云盾
                        $is_free = (float)$mitigation['price'] == 0;

                        //按月计费：仅防护，由ExpireHandler进行到期扣取次月
                        Response::note('IP：%s，计费方案：按月计费', $dest_ip);
                        Response::note('当前购买防护阈值：%sMbps / %spps', $max_mbps, $max_pps);

                        if ( $is_free && $group_threat ) {
                            //免费版在大网受到威胁时，立即牵引
                            Response::note('存在大网安全问题需立即牵引');
                            if (BlackHole::block($dest_ip, $item['mbps'], true)) {
                                $total_mbps -= $item['mbps'];
                            }
                        } else {
                            if ($item['mbps'] >= $max_mbps) {
                                Response::note('当前攻击速率%sMbps到达所购买防护阈值，正在牵引...', $item['mbps']);
                                BlackHole::block($dest_ip, $item['mbps'], false);
                            } else if ($item['pps'] >= $max_pps) {
                                Response::note('当前攻击包数%spps到达所购买防护阈值，正在牵引...', $item['pps']);
                                BlackHole::block($dest_ip, $item['mbps'], false);
                            } else {
                                Response::note('当前攻击值：%sMbps/%spps 未达到购买防护阈值，继续清洗...', $item['mbps'], $item['pps']);
                            }
                        }
                        break;

                    case 'hour' :
                        //按需计费：先防护后计前1小时的费用，单价采用所属数据中心中的价格
                        Response::note('IP：%s，计费方案：按需计费', $dest_ip);

                        if ( $total_mbps >= $max_mbps ) {
                            //经过不断对 $total_mbps 做减算，还是超过了最高防护，继续牵引
                            Response::note('存在大网安全问题需立即牵引');
                            if (BlackHole::block($dest_ip, $item['mbps'], true)) {
                                $total_mbps -= $item['mbps'];
                            }
                        } else {

                            //由实例确定用户余额
                            $uid = $mitigation['uid'];
                            $user = User::find($uid);
                            response_user_detail($user);

                            //获取当前ip所在的数据中心的价格规则(元/小时)的数组
                            $price_rules = DataCenter::price_rules($datacenter, 'hour');

                            //由IP确定攻击历史记录
                            Response::note('由IP确定攻击历史记录：');
                            $DDoSHistory = DDoSHistory::find_ip_attacking($dest_ip);
                            if (!$DDoSHistory) {
                                $msg = '未找到该IP正在被攻击中的历史记录';
                                Notify::developer($msg);
                            } else {
                                $DDoSHistory_id = $DDoSHistory['id'];
                                Response::note('#tab当前攻击的所属历史记录ID：%s', $DDoSHistory_id);

                                //当前攻击是否超过用户设定的最高防护能力
                                if ($item['mbps'] > $max_mbps || $item['pps'] > $max_pps) {
                                    //超过?是
                                    if ($item['mbps'] > $max_mbps) {
                                        Response::note('当前攻击速率：%sMbps', $item['mbps']);
                                        //Response::note('到达数据中心%s最高防护值：%sMbps', $datacenter['name'], $max_mbps);
                                        Response::note('到达用户最高承受支付能力防护值：%sMbps', $max_mbps);
                                    } else {
                                        Response::note('当前攻击包率：%spps', $item['pps']);
                                        //Response::note('到达数据中心%s最高防护值：%spps', $datacenter['name'], $max_pps);
                                        Response::note('到达用户最高承受支付能力防护值：%spps', $max_pps);
                                    }

                                    //强制牵引
                                    if (BlackHole::exists($ip)) {
                                        $echo = Response::warn('异常：IP：%s, 已经处于牵引中，无需操作，无需计费', $dest_ip);
                                        reportDevException($echo);
                                        //已经存在牵引中了，可能由于防火强还没处理牵引请求造成的延时
                                    } else {
                                        Response::note('需要对IP：%s作强制牵引处理', $dest_ip);
                                        BlackHole::block($dest_ip, $item['mbps'], false);

                                        //计费扣费
                                        DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                                    }

                                } else {
                                    //超过?否
                                    //模拟本次攻击总计，检查余额是否足以支付
                                    $fee = DDoSHistory::calcFee($DDoSHistory, $price_rules);

                                    if ( $fee > $user['money'] ) {
                                        Response::note('模拟总计费用：%s，已超出用户余额：%s，需立即处理：', $fee, $user['money']);

                                        //产生的总费用超过用户余额，强制牵引
                                        BlackHole::block($dest_ip, $item['mbps'], false);

                                        //计费扣费
                                        DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                                    } else {
                                        Response::note('当前余额足够支付%s，继续清洗中...', $fee);
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
    }
}

System::continues();
?>
