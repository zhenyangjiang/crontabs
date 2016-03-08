<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Utils\Datetime;
use Landers\Framework\Core\Queue;
use Tasks\CollectUpload;

// require_once('randomxml.php'); usleep(100000);
// Response::note(['#blank', '#blank', '#blank']);
echo PHP_EOL;
Response::note(['#blank', '【按月防护，按需防护、计费】（'.System::app('name').'）开始工作','#dbline']);

//解除之前牵引
Response::note('正在对牵引过期的IP作解除牵引：');
BlackHole::unblock(); //解除牵引
Response::note('#line');

//读取防火墙数据
$ori_pack_attack = Firewall::get_attack();
if (ENV_debug == true) {
    $ori_pack_attack = Firewall::make_attacks($ori_pack_attack);
}
Response::note('从防火墙上获取了%s条攻击信息', count($ori_pack_attack));

//推往收集器collecter.ulan.com

$temp_ququeId = Queue::singleton('ddoscollecter')->push(new CollectUpload($ori_pack_attack));
Response::noteSuccessFail('DDoSInfo发送到收集中心%s', !!$temp_ququeId);

// 过滤掉 pack_attack 中被牵引的IP(用Instances中的net_state作为过滤依据)
$pack_attack = DDoSInfo::filte_blocked_attack($ori_pack_attack);
//如果有存在的话，需要给出异常

//保存攻击数据
$pack_attack = DDoSInfo::save_attack($pack_attack);
$pack_attack or $pack_attack = [];
if ( $all_ips = array_keys($pack_attack) ) {
    foreach ($all_ips as $ip) Response::note("#tab$ip");
    Response::note('#tab成功导入%s条数据', colorize(count($all_ips), 'green', 1));
}
Response::note('#line');

//对【数据库中存在，但当前攻击不存在】的IP，作【攻击结束】IP筛选条件范围
Response::note('正在查询被攻击中、且本次未被攻击的IP作攻击结束：');
$attaching_ips = DDoSHistory::get_attacking_ips();

if ($attaching_ips) {
    foreach ($attaching_ips as $ip) Response::note('#tab%s', $ip);
    Response::note('#tab当前历史中有%sIP正在被攻击中', colorize(count($attaching_ips), 'yellow', 1));
    $diff_ips = array_diff($attaching_ips, $all_ips);

    Response::note('#blank');

    if ($diff_ips) {
        Response::note(['#blank', '#blank', '逐一对以上IP作攻击自然结束：']);
        foreach ($diff_ips as $ip) {
            Response::note('#line');

            //由ip确定实例记录
            $instance = Instance::find_ip($ip);
            Response::instance_detail($instance);

            //获取当前ip所在的数据中心的价格规则(元/小时)的数组
            $price_rules = DataCenter::price_rules($datacenter, 'hour');

            //由IP确定用户
            $uid = $instance['uid'];
            $user = User::find($uid);
            Response::user_detail($user);

            //由IP确定攻击历史记录
            $DDoSHistory = DDoSHistory::find_ip_attacking($ip);
            if ($DDoSHistory) {
                //有攻击历史，计费扣费
                DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
            } else {
                Response::warn('未找到该IP正在被攻击中的历史记录，暂不计费，直接写入攻击结束');
            }

            //写入攻击自然结束
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
$ret = DDoSHistory::save_start_attack($all_ips);

//攻击中的数据处理
Response::note(['#blank', '#blank', '#blank', '开始逐一对所有被攻击IP操作...']);
foreach ($pack_attack as $item) {
    Response::note('#line');
    $item['mbps'] = &$item['bps0'];//bps0改名为mbps
    $item['pps'] = &$item['pps0'];//pps0改名为pps

    $dest_ip = $item['dest'];

    //读取云盾表中该ip的云盾配置
    $mitigation = Mitigation::find_ip($dest_ip);
    if (!$mitigation) {
        //找不到记录，属异常，超100Mbps即牵引
        Response::note('IP：%s，未知的异常IP地址', $dest_ip);

        if ($item['mbps'] >= 100 || $item['pps'] >= 100000) {
            Response::note('当前攻击值：%sMbps / %spps，正在牵引...', $item['mbps'], $item['pps']);
            BlackHole::block($dest_ip, $item['mbps']); continue;
        } else {
            Response::note('当前攻击值：%sMbps/%spps，继续清洗...', $item['mbps'], $item['pps']);
        }
    } else {
        switch ($mitigation['billing']) {
            case 'month' :
                //按月计费：仅防护，由ExpireHandler进行到期扣取次月
                Response::note('IP：%s，计费方案：按月计费', $dest_ip);
                Response::note('当前购买防护值：%sMbps / %spps', $mitigation['ability_mbps'], $mitigation['ability_pps']);

                if ($item['mbps'] >= $mitigation['ability_mbps']) {
                    Response::note('当前攻击速率%sMbps到达所购买防护值，正在牵引...', $item['mbps']);
                    BlackHole::block($dest_ip, $item['mbps']);
                    continue;
                } else if ($item['pps'] >= $mitigation['ability_pps']) {
                    Response::note('当前攻击包数%spps到达所购买防护值，正在牵引...', $item['pps']);
                    BlackHole::block($dest_ip, $item['mbps']);
                    continue;
                } else {
                    Response::note('当前攻击值：%sMbps/%spps 未达到购买防护值，继续清洗...', $item['mbps'], $item['pps']);
                }
                break;

            case 'hour' :
                //按需计费：先防护后计前1小时的费用，单价采用所属数据中心中的价格
                Response::note('IP：%s，计费方案：按需计费', $dest_ip);

                //由ip确定实例记录
                $instance = Instance::find_ip($dest_ip);
                Response::instance_detail($instance);

                //由实例确定所在数据中心：（中心最高防护能力、中心的价格规则）
                $datacenter = Instance::datacenter($instance);

                //当前IP的云盾配额
                $max_mbps = $mitigation['ability_mbps'];
                $max_pps = $mitigation['ability_pps'];

                //由实例确定用户余额
                $uid = $instance['uid'];
                $user = User::find($uid);
                Response::user_detail($user);

                //获取当前ip所在的数据中心的价格规则(元/小时)的数组
                $price_rules = DataCenter::price_rules($datacenter, 'hour');

                //由IP确定攻击历史记录
                Response::note('由IP确定攻击历史记录：');
                $DDoSHistory = DDoSHistory::find_ip_attacking($dest_ip);
                if (!$DDoSHistory) {
                    $msg = '未找到该IP正在被攻击中的历史记录';
                    Notify::developer($msg);
                    continue;
                }
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
                        Response::warn('异常：IP：%s, 已经处于牵引中，无需操作，无需计费', $dest_ip);
                        //已经存在牵引中了，可能由于防火强还没处理牵引请求造成的延时
                        continue;
                    } else {
                        Response::note('需要对IP：%s作强制牵引处理', $dest_ip);
                        BlackHole::block($dest_ip, $item['mbps']);
                    }

                    //计费扣费
                    DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                } else {
                    //超过?否
                    //模拟本次攻击总计，检查余额是否足以支付
                    $fee = DDoSHistory::calcFee($DDoSHistory, $price_rules);
                    if ( $total_fee > $user['money'] ) {
                        Response::note('模拟总计费用：%s，已超出用户余额：%s，需立即处理：', $total_fee, $user['money']);

                        //产生的总费用超过用户余额，强制牵引
                        BlackHole::block($dest_ip, $item['mbps']);

                        //计费扣费
                        DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                    } else {
                        Response::note('当前余额足够，继续清洗中...');
                    }
                }
                break;
        }
    }
}

System::continues();
?>
