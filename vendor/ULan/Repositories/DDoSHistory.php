<?php
// namespace Ulan\Modules;

use Landers\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Response;

class DDoSHistory extends Repository {
    protected static $connection = 'mitigation';
    protected static $datatable = 'ddoshistory';
    protected static $DAO;

    // 不可以采用分表，它的id与DDoSHistoryFee有关联
    // protected static $dt_parter = ['type' => 'datetime', 'mode' => 'ym'];

    /**
     * 取得当前正在被攻击的ips
     * @return Array 被攻中的或被牵引中（攻击未结束）的ip集合
     */
    public static function get_attacking_ips(){
        $ret = Instance::lists([
            'fields' => 'mainipaddress as ip',
            'awhere' => ['net_state' => 1]
        ]);
        if ($ret) $ret = Arr::flat($ret);

        return $ret;
    }

    /**
     * 插入ip攻击开始时间
     * @param  string   $ip     ip
     * @return array            新增的记录集合
     */
    public static function save_start_attack($ips) {
        $ips = (array)$ips; if (!$ips) return false;

        //获取实例表中：1)在ips范围内的IP， 2)标为“正常(0)”的IP
        $pack = Instance::lists([
            'fields' => 'id, mainipaddress as ip',
            'awhere' => ['mainipaddress' => $ips, 'net_state' => 0]
        ]);

        $ret_ips = [];
        if ($pack) {
            //准备导入history表
            $data = [];
            foreach ($pack as $item) $data[] = ['ip' => $item['ip'], 'begin_time' => time()];

            //事务处理：写入攻击开始、更新实列表为被攻击中
            $bool = self::transact(function() use ($data) {
                if (!$bool = self::import($data)) {
                    Response::note('#tab写入“攻击开始”失败');
                    return false;
                }

                $ips = Arr::clone_inner($data, 'ip');
                if ( !Instance::update_net_status($ips, 1, true) ) {
                    Response::note('更新“实列表为被攻击中”失败');
                    return false;
                }

                return true;
            });

            if ($bool) {
                $ret_ips = Arr::clone_inner($pack, 'ip');
                foreach ($ret_ips as $ip) Response::note('#tab%s ', $ip);
                Response::note('#tab共计%sIP正在开始被攻击，开始时间已存入攻击历史', colorize(count($ret_ips), 'yellow', 1));
            } else {
                $msg = '事务处理失败: “写入攻击开始、更新实列表状态” ';
                Response::error('#tab'.$msg);
                Notify::developer($msg);
                System::halt();
            }
        } else {
            Response::note('#tab所有IP实例均%s或%s，无需记录攻击开始', colorize('攻击中', 'yellow'), colorize('牵引中', 'yellow'));
        }

        return $ret_ips;
    }

    /**
     * 更新ip攻击结束时间
     * @param  array|string   $ips      ip
     * @return bool                     数据更新成功否
     */
    public static function save_end_attack($ip, $on_event) {
        $net_state_updtime = Instance::find_ip($ip, 'net_state_updtime');

        $history = self::find([
            'awhere' => ['ip' => $ip, 'end_time' => NULL],
            'order'  => 'id desc',
        ]);
        if (!$history) {
            Response::note('#tabIP：%s 原本已经攻击结束', $ip);
            return true;
        }

        //更新历史记录的结束相关信息
        $end_time = time();
        $peak = DDoSInfo::get_attack_peak($history['ip'], $history['begin_time'], $end_time);
        $peak or $peak = [];
        $data = [
            'end_time' => time(),
            'bps_peak' => Arr::get($peak, 'mbps.value'),
            'pps_peak' => Arr::get($peak, 'pps.value'),
            'on_event' => $on_event,
        ];
        $awhere = ['id' => $history['id']];
        $bool = self::update($data, $awhere);
        Response::noteSuccessFail('#tab更新“攻击结束的相关信息”%s', $bool);
        if (!$bool) {
            Notify::developer('更新攻击结束信息失败');
            return false;
        } else {
            return true;
        }
    }

    /**
     * 取得指定IP，且在攻击中历史记录
     * @param  [type] $ip [description]
     * @return [type]     [description]
     */
    public static function find_ip_attacking($ip) {
        return self::find([
            'awhere' => ['end_time' => NULL, 'ip' => $ip],
            'order'  => 'begin_time desc',
        ]);
    }

    /**
     * 确定每小时单价
     * @param  string $ip          IP
     * @param  array  $price_rules 价格规则
     * @param  int    $begin_time  开始时间
     * @param  int    $end_time    结束时间
     * @param  array  &$peak_info  返回峰值信息
     * @return float
     */
    private static function hour_price($ip, $price_rules, $begin_time, $end_time, &$peak_info = array()) {
        Response::note('#tab确定%s ~ %s内的每小时单价：', date('Y-m-d H:i:s', $begin_time), date('Y-m-d H:i:s', $end_time));

        //确定[begin_time]和[end_time]之间的峰值
        $peak_info = DDoSInfo::get_attack_peak($ip, $begin_time, $end_time);
        if ( !$peak_info ) return 0;
        $peak_bps = $peak_info['mbps'];
        $peak_pps = $peak_info['pps'];

        $ret_price = 0; //返回单价数据
        $price_keys = array_keys($price_rules);
        $price_key_bps = (int)array_search_less_that($price_keys, $peak_bps['value']);
        $price_key_pps = (int)array_search_less_that($price_keys, $peak_pps['value'], function($item){
            return mitigation::Gbps_to_pps($item);
        });
        $price_bps = $price_rules[$price_key_bps];
        $price_pps = $price_rules[$price_key_pps];
        if ($price_bps == $price_pps) {
            $ret_price = $price_bps;
            if ( $ret_price ) {
                Response::note('#tab在%s峰值%sMbps/%spps最接近规格%sMbps的价格为：%s元/小时', $peak_bps['time'], $peak_bps['value'], $peak_pps['value'], $price_key_bps, $ret_price);
            } else {
                Response::note('#tab免费防护规格，无需扣款');
            }
        } else {
            if ( (int)$price_bps > (int)$price_pps ) {
                $ret_price = $price_bps;
                Response::note('#tab在%s峰值%sMbps最接近规格%sMbps的价格为：%s元/小时', $peak_bps['time'], $peak_bps['value'], $price_key_bps, $ret_price);
            } else {
                $ret_price = $price_pps;
                Response::note('#tab在%s峰值%spps最接近规格%sMbps的价格为：%s元/小时', $peak_bps['time'], $peak_pps['value'], $price_key_pps, $ret_price);
            }
        };

        return $ret_price;
    }

    /**
     * 计算费用
     * @param  [type] $ip          [description]
     * @param  [type] $price_rules [description]
     * @param  [type] $begin_time  [description]
     * @param  [type] $end_time    [description]
     * @return [type]              [description]
     */
    public static function calcFee($history, $price_rules, &$peak_info = array(), &$duration = 0) {
        $ip = $history['ip'];
        $begin_time = $history['begin_time'];
        $end_time = time();

        // 确定此时间段内的单价：由此时间段的峰值决定
        $hour_price = self::hour_price($ip, $price_rules, $begin_time, $end_time, $peak_info);

        // 确定此时间段的时长，由秒转换成小时
        $duration_seconds = $end_time - $begin_time;
        $duration = round($duration_seconds / 3600, 2);

        return round($hour_price * $duration);
    }

    /**
     * 实际计费扣费
     * @return [type] [description]
     */
    public static function billing($uid, $history, $price_rules) {
        $ip = $history['ip'];

        $fee = self::calcFee($history, $price_rules, $peak_info, $duration);

        // if (!$fee) return true;

        //按需防护扣费日志数据包
        $feelog_mitigation_data = [
            'typekey' => 'pay_mitigation_hour',
            'balance' => User::get_money($uid) - $fee,
            'instance_ip' => $ip,
            'uid' => $uid,
            'amount' => $fee,
            'title' => sprintf(
                'IP：%s 按需防护费用, 持续%s小时, 峰值：%sMbps/%spps',
                $ip, $peak_info['mbps']['value'], $peak_info['pps']['value'], $duration
            ),
        ];

        //写入总计费用日志
        $bool = !!Feelog::create($feelog_mitigation_data);
        Response::note('#tab本次攻击共持续：%s小时，费用：￥%s', $duration, $fee);
        Response::noteSuccessFail('#tab云盾合计扣费日志写入%s', $bool);
        return $bool;
    }
}
DDoSHistory::init();
?>