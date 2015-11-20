<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Log;
use Landers\Utils\Datetime;

require_once('randomxml.php'); usleep(100000);
Log::note(['#blank', '#blank', '#blank']);

Log::note(['【按月防护，按需防护、计费】（'.System::app('name').'）开始工作','#dbline']);

// $sqls = [
//     'TRUNCATE ulan_mitigation.ddoshistory',
//     'TRUNCATE ulan_mitigation.historyfee',
//     'update ulan_main.`ulan_instances` set net_state=0, net_state_updtime'
// ];
//System::db('mitigation')->querys($sqls);

//解除之前牵引
Log::note('正在对牵引过期的IP作解除牵引：');
BlackHole::unblock(); //解除牵引
Log::note('#line');

//确定防火墙数据文件
$fw_dat = System::param('fwdat') or $fw_dat = 'random.xml';
$fw_dat = dirname(__DIR__).'/data/'.$fw_dat;
if (!is_file($fw_dat)) {
    $msg = '防火墙数据文件错误！';
    Log::error($msg);
    Notify::developer($msg);
    System::halt();
}

//解析防火墙攻击xml数据、并存入数据库
Log::note('读取防火墙xml：%s', $fw_dat);
$pack_attack = DDoSInfo::save_attack($fw_dat);
if ( $all_ips = array_keys($pack_attack) ) {
    foreach ($all_ips as $ip) Log::note("#tab$ip");
    Log::note('#tab成功导入%s条数据', colorize(count($all_ips), 'green', 1));
}
Log::note('#line');

//对【数据库中存在，但当前攻击不存在】的IP，作【攻击结束】IP筛选条件范围
Log::note('正在查询被攻击中、且本次未被攻击的IP作攻击结束：');
$attaching_ips_history = History::get_attacking_ips();

if ($attaching_ips_history) {
    foreach ($attaching_ips_history as $ip) Log::note('#tab%s', $ip);
    Log::note('#tab当前历史中有%sIP正在被攻击中', colorize(count($attaching_ips_history), 'yellow', 1));
    $diff_ips = array_diff($attaching_ips_history, $all_ips);

    Log::note('#blank');

    if ($diff_ips) {
        Log::note(['#blank', '#blank', '逐一对以上攻击结束的IP进行总计结算：']);
        foreach ($diff_ips as $ip) {
            Log::note('#line');

            //由ip确定实例记录
            $instance = Instance::find_ip($ip);
            Log::instance_detail($instance);

            //由IP确定用户
            $uid = $instance['uid'];
            $user = User::find($uid);
            Log::user_detail($user);

            //由IP确定攻击历史记录
            $history = History::find_ip_attacking($ip);
            if ($history) {
                $history_id = $history['id'];
                Log::note('此IP归属历史记录ID：%s', $history_id);

                HistoryFee::deduction($uid, $history, function($ip){
                    return History::save_end_attack($ip);
                });
            } else {
                Log::warn('未找到该IP正在被攻击中的历史记录，暂不计费时，直接写入攻击结束');
                History::save_end_attack($ip);
            }
        }
    } else {
        Log::note('#tab攻击历史中的IP全部存在于当前被攻击IP中，没有IP需要作攻击结束');
    }
} else {
    Log::note('#tab当前历史中没有被攻击中的IP');
}

//空数据包时，任务提前结束
if (!$all_ips) System::halt('空数据包时，本次任务提前结束');

//记录开始攻击
Log::note('#line');
Log::note('当前所有被攻击IP中，给状态为正常的IP记录攻击开始：');
$ret = History::save_start_attack($all_ips);

//攻击中的数据处理
Log::note(['#blank', '#blank', '#blank', '开始逐一对所有被攻击IP操作...']);
foreach ($pack_attack as $item) {
    Log::note('#line');
    $item['mbps'] = &$item['bps'];//bps改名为mbps
    $ip = $item['ip'];

    //读取云盾表中该ip的云盾配置
    $mitigation = Mitigation::find_ip($ip);
    if (!$mitigation) {
        //找不到记录，属：默认免费版
        Log::note('IP：%s，计费方案：默认免费版', $ip);

        //由ip确定实例记录
        $instance = Instance::find_ip($ip);

        //由实例确定数据中心
        $datacenter = Instance::datacenter($instance);

        //确定数据中心的价格规则(按月)的数组
        $price_rules = DataCenter::price_rules($datacenter, 'month');

        //找出规则中属免费的防护规格
        $free_mbps = array_search(0, $price_rules, true);
        $free_pps = Mitigation::Mbps_to_pps($free_mbps);

        if (!$free_mbps && !$free_pps) {
            Log::note('系统不提供免费防护规格，正在牵引...');
            BlackHole::block($ip); continue;
        }

        Log::note('系统提供免费防护规格为：%sMbps/%spps', $free_mbps, $free_pps);

        if ($item['mbps'] > $free_mbps) {
            Log::note('当前攻击值：%sMbps，超过免费防护值，正在牵引...', $item['mbps']);
            BlackHole::block($ip); continue;
        } elseif ($item['pps'] > $free_pps) {
            Log::note('当前攻击值：%spps，超过免费防护值，正在牵引...', $item['pps']);
            BlackHole::block($ip); continue;
        } else {
            Log::note('当前攻击值：%sMbps/%spps，在免费防护范围内，继续清洗...', $item['mbps'], $item['pps']);
        }
    } else {
        //根据所购买的云盾的支付方式进行相关操作
        switch ($mitigation['billing']) {
            case 'month' : //按月计费：仅防护，由另一CrontabB进行计费
                Log::note('IP：%s，计费方案：按月计费', $ip);
                Log::note('当前购买防护值：%sMbps / %spps', $mitigation['ability_mbps'], $mitigation['ability_pps']);

                if ($item['mbps'] >= $mitigation['ability_mbps']) {
                    Log::note('当前攻击速率%sMbps到达所购买防护值，正在牵引...', $item['mbps']);
                    BlackHole::block($ip); continue;
                } else if ($item['pps'] >= $mitigation['ability_pps']) {
                    Log::note('当前攻击包数%spps到达所购买防护值，正在牵引...', $item['pps']);
                    BlackHole::block($ip); continue;
                } else {
                    Log::note('当前攻击值：%sMbps/%spps 未达到购买防护值，继续清洗...', $item['mbps'], $item['pps']);
                }
                break;

            case 'hour' : //按需计费：先防护后计前1小时的费用
                Log::note('IP：%s，计费方案：按需计费', $ip);

                //由ip确定实例记录
                $instance = Instance::find_ip($ip);
                Log::instance_detail($instance);

                //由实例确定所在数据中心：（中心最高防护能力、中心的价格规则）
                $datacenter = Instance::datacenter($instance);
                // $max_mbps = $datacenter['hour-max-mbps'];
                // $max_pps = $datacenter['hour-max-pps'];

                //当前IP的云盾配额
                $max_mbps = $mitigation['ability_mbps'];
                $max_pps = $mitigation['ability_pps'];

                //由实例确定用户余额
                $uid = $instance['uid'];
                $user = User::find($uid);
                Log::user_detail($user);

                //获取当前ip所在的数据中心的价格规则(元/小时)的数组
                $price_rules = DataCenter::price_rules($datacenter, 'hour');

                //由IP确定攻击历史记录
                Log::note('由IP确定攻击历史记录：');
                $history = History::find_ip_attacking($ip);
                if (!$history) {
                    $msg = sprintf('未找到该IP正在被攻击中的历史记录');
                    Notify::developer($msg);
                    continue;
                }
                $history_id = $history['id'];
                Log::note('#tab当前攻击的所属历史记录ID：%s', $history_id);

                //确定[最后节点时间]和[当前时间]
                $last_break_time = HistoryFee::find_last_time($history_id) or $last_break_time = $history['begin_time'];
                $now_time = time();

                //当前攻击是否超过用户设定的最高防护能力
                if ($item['mbps'] > $max_mbps || $item['pps'] > $max_pps) {
                    //超过?是
                    if ($item['mbps'] > $max_mbps) {
                        Log::note('当前攻击速率：%sMbps', $item['mbps']);
                        //Log::note('到达数据中心%s最高防护值：%sMbps', $datacenter['name'], $max_mbps);
                        Log::note('到达用户最高承受支付能力防护值：%sMbps', $max_mbps);
                    } else {
                        Log::note('当前攻击包率：%spps', $item['pps']);
                        //Log::note('到达数据中心%s最高防护值：%spps', $datacenter['name'], $max_pps);
                        Log::note('到达用户最高承受支付能力防护值：%spps', $max_pps);
                    }

                    //强制牵引
                    Log::note('需要对IP：%s作强制牵引处理', $ip);
                    BlackHole::block($ip);

                    //先小计
                    Log::note('写入此段不足1小时的小计记录：');
                    HistoryFee::create_fee($history, $price_rules, $last_break_time, $now_time);

                    //后总计
                    Log::note('结算本次攻击总费用：');
                    $bool = HistoryFee::deduction($uid, $history);
                    if ($bool) {
                        Log::note('#tab总计结算成功');
                    } else {
                        Log::warn('#tab总计结算失败');
                        Notify::developer('IP:%s 总计结算失败', $ip);
                    }
                } else {
                    //超过?否
                    Log::note('当前攻击：%sMbps/%spps ', $item['bps'], $item['pps']);
                    // Log::note('均未到达数据中心%s最高防护值：%sMbps/%spps ，继续清洗中...', $datacenter['name'], $max_mbps, $max_pps);
                    Log::note('未到达用户最高承受支付能力防护值：%sMbps/%spps', $max_mbps, $max_pps);

                    if ( $now_time - $last_break_time >= 3600 ) {
                        //满1小时小计一次
                        Log::note('离上一轮攻击时间：%s，已到达或超过1小时，需进行小计：', date('Y-m-d H:i:s', $last_break_time));
                        HistoryFee::create_fee($history, $price_rules, $last_break_time, $now_time);
                        Log::note('继续清洗中...');
                    } else {
                        //不足1小时，无需小计
                        Log::note('最近攻击发生在%s，本轮暂不足1小时', date('Y-m-d H:i:s', $last_break_time));

                        //模拟本次攻击总计，检查余额是否足以支付
                        $total_fee = HistoryFee::total_fee($history_id);
                        if ( $total_fee > $user['money'] ) {
                            Log::note('模拟总计费用：%s，已超出用户余额：%s，需立即处理：', $total_fee, $user['money']);

                            //产生的总费用超过用户余额，强制牵引
                            BlackHole::block($ip);

                            //先小计
                            Log::note('#tab写入此段不足1小时的小计记录');
                            HistoryFee::create_fee($history, $price_rules, $last_break_time, $now_time);

                            //清算本次攻击 事务处理：扣除用户费用，费用日志
                            Log::note('#tab强制总计结算本次攻击总费用');
                            HistoryFee::deduction($uid, $history);
                        } else {
                            Log::note('当前余额足够，继续清洗中...');
                        }
                    }
                }
                break;
        }
    }
}

System::continues();
?>
