<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;

class DDoSHistory extends StaticRepository {
    protected static $connection = 'mitigation';
    protected static $datatable = 'ddoshistory';
    protected static $DAO;

    // 不可以采用分表，它的id与DDoSHistoryFee有关联
    // protected static $dt_parter = ['type' => 'datetime', 'mode' => 'ym'];

    /**
     * 插入ip攻击开始时间
     * @param  string   $ip     ip
     * @return array            新增的记录集合
     */
    public static function save_start_attack($ips) {
        $ips = (array)$ips; if (!$ips) return false;

        //获取实例表中：1)在ips范围内的IP， 2)标为“正常”的IP
        $mitigations = Mitigation::lists([
            'fields' => 'ip, uid',
            'awhere' => ['ip' => $ips, 'status' => 'NORMAL']
        ]);

        $ret_ips = [];
        if ($mitigations) {
            $transacter = '写入攻击开始、更新实列状态为被攻击中';
            Response::transactBegin($transacter);
            $result = self::transact(function() use ($mitigations, &$ret_ips) {
                //准备导入history表
                $data = [];
                foreach ($mitigations as $item) $data[] = ['ip' => $item['ip'], 'begin_time' => System::nowTime()];

                Response::note('#tab即将对以下IP写入攻击历史：');
                $ret_ips = Arr::clone_inner($mitigations, 'ip');
                Response::echoText(implode('，', $ret_ips));

                Response::note('#tab写入“攻击开始”...');
                $bool = self::import($data);
                Response::echoBool($bool);
                if (!$bool) return false;

                Response::note('#tab更新“实列表为被攻击中”...');
                $ips = Arr::clone_inner($data, 'ip');
                $bool = Mitigation::setStatus($ips, 'ATTACK', true);
                Response::echoBool($bool);
                if (!$bool) return false;

                return true;
            });

            if (!$result) {
                Notify::developer(sprintf('事务处理失败：%s', $transacter));
                System::halt();
            }

            Response::transactEnd($result);

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
        $peak = DDoSInfo::peak($history['ip'], $history['begin_time'], $end_time);
        if ( !$peak ) {
            $echo = Response::warn(sprintf('#tab攻击结束时发现%s ~ %s找不到峰值', $history['begin_time'], $end_time));
            Notify::developer($echo, compact('ip', 'history', 'peak'));
            return false;
        } else {
            $data = [
                'end_time' => $end_time,
                'bps_peak' => Arr::get($peak, 'mbps.value'),
                // 'bps_time' => Arr::get($peak, 'mbps.time'),
                'pps_peak' => Arr::get($peak, 'pps.value'),
                // 'pps_time' => Arr::get($peak, 'pps.time'),
                'on_event' => $on_event,
            ];
            $awhere = ['id' => $history['id']];
            $bool = self::update($data, $awhere);
            if (!$bool) {
                $echo = Response::warn('#tab更新攻击结束信息失败');
                Notify::developer($echo, compact('data', 'awhere'));
                return false;
            } else {
                Response::echoBool(true);

                // 自然结束时才告警
                if ($on_event == 'STOP') Alert::endDDoS($ip, $data);

                return true;
            }
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
    private static function hour_price($ip, $price_rules, &$peak_info = array()) {
        $begin_time = $peak_info['begin'];
        $end_time = $peak_info['end'];

        Response::note('确定%s ~ %s内的每小时单价：', $begin_time, $end_time);

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

        $price_key_bps = Mitigation::Mbps_to_Gbps($price_key_bps);

        if ($price_bps == $price_pps) {
            $ret_price = $price_bps;
            if ( $ret_price ) {
                Response::note('#tab在%s峰值%sMbps/%spps最接近规格%sGbps的价格为：%s元/小时', $peak_bps['time'], $peak_bps['value'], $peak_pps['value'], $price_key_bps, $ret_price);
            } else {
                Response::note('#tab单价为0，免费防护规格');
            }
        } else {
            if ( (int)$price_bps > (int)$price_pps ) {
                $ret_price = $price_bps;
                Response::note('#tab在%s峰值%sMbps最接近规格%sGbps的价格为：%s元/小时', $peak_bps['time'], $peak_bps['value'], $price_key_bps, $ret_price);
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
     * @param  [type] $peak_info   返回峰值信息
     * @param  [type] $duration    返回时长（小时数）
     * @return [type]              [description]
     */
    public static function calcFee($history, $price_rules, &$peak_info = array(), &$duration = 0) {
        $ip = $history['ip'];
        $begin_time = $history['begin_time'];
        $end_time = time();

        $peak_info or $peak_info = DDoSInfo::peak($ip, $begin_time, $end_time);
        if ( !$peak_info ) {
            $echo = Response::warn('#tab未找到峰值或峰值为0');
            reportDevException($echo, compact('ip', 'price_rules', 'begin_time', 'end_time'));
            return 0;
        }

        // 确定此时间段内的单价：由此时间段的峰值决定
        $hour_price = self::hour_price($ip, $price_rules, $peak_info);

        // 确定此时间段的时长，由秒转换成小时
        $duration_seconds = $end_time - $begin_time;
        // if ( $duration_seconds < 10 ) {
        //     $duration_seconds = 0;
        // } else {
            $duration_seconds = $duration_seconds < 60 ? 60 : $duration_seconds; //不足1分钟，按1分钟时长计算
        // }
        $duration = round($duration_seconds / 3600, 2);

        return round($hour_price * $duration);
    }

    /**
     * 实际计费扣费
     * @return [type] [description]
     */
    public static function billing($uid, $history, $price_rules, $peak_bps = NULL) {
        $ip = $history['ip'];

        if ( $peak_bps ) {
            $begin_time = $history['begin_time'];
            $end_time = time();
            Response::note('根据峰值bps：%sMbps, 构造峰值信息', $peak_bps);
            $peak_info = DDoSInfo::genealPeakByMBps($peak_bps, $begin_time, $end_time);
        }

        $fee = self::calcFee($history, $price_rules, $peak_info, $duration);

        if ( !$duration ) {
            Response::note('瞬间大流量攻击，时长过短，暂不计费');
            return true;
        }

        if (!$fee) {
            Response::note('未产生费用，无需扣费');
            return true;
        }

        //按需防护扣费日志数据包
        $feelog_data = [
            'typekey' => 'pay_mitigation_hour',
            'balance' => User::get_money($uid) - $fee,
            // 'service_ip' => $ip,
            'uid' => $uid,
            'amount' => $fee,
            'description' => sprintf(
                '支付按需防护费用：IP：%s, 持续%s小时, 峰值：%sMbps/%spps',
                $ip, $duration, $peak_info['mbps']['value'], $peak_info['pps']['value']
            ),
        ];

        Response::transactBegin('用户扣费、写入扣费日志');
        Response::note('#tab本次攻击共持续：%s小时，费用：￥%s', $duration, $fee);
        $result = User::transact(function() use ($uid, $feelog_data, $duration) {
            $fee_amount = $feelog_data['amount'];
            Response::note('#tab向用户扣除费用：%s...', $fee_amount);
            $bool = User::pay_money($uid, $fee_amount);
            Response::echoBool($bool);
            if (!$bool) return false;

            return Feelog::transact(function() use ($uid, $feelog_data, $duration) {
                // 写入总计费用日志
                Response::note('#tab写入云盾费用日志...');
                $bool = !!Feelog::create($feelog_data);
                Response::echoBool($bool);
                if (!$bool) return false;

                return $bool;
            });
        });

        return Response::transactEnd($result);

    }
}
DDoSHistory::init();
?>