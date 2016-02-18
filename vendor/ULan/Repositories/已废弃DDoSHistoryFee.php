<?php

use Landers\Utils\Arr;
use Landers\Utils\Datetime;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Response;

Class DDoSHistoryFee extends Repository {
    protected static $connection = 'mitigation';
    protected static $datatable = 'ddoshistoryfee';
    protected static $DAO;

    /**
     * 找出最后一个节点时间
     * @param  [type] $history_id [description]
     * @return [type]             [description]
     */
    public static function find_last_time($history_id) {
        return self::find([
            'awhere' => ['history_id' => $history_id],
            'fields' => 'breaktime',
            'order'  => 'breaktime desc',
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
    public static function hour_price($ip, $price_rules, $begin_time, $end_time, &$peak_info = NULL) {
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
     * 增加小计费用记录
     * @param  int    $history_id  所属历史记录ID
     * @param  array  $price_rules 价格规则
     * @param  int    $begin_time  开始时间
     * @param  int    $end_time    结束时间
     * @return boolean             操作是否成功
     */
    public static function create($uid, $history, $price_rules, $begin_time, $end_time) {
        $minutes = 0.0167;

        //确定间隔时间时长
        $duration_seconds = $end_time - $begin_time;
        $duration_hours = $duration_seconds / Datetime::$hour;  //不能作四舍五入或其它类似函数运算，后果影响很大

        //确定单组或多组时长的始终
        $arr_times = [];
        if ( $duration_hours < (1 + $minutes) ) {
            //时长在1小时零1分内的，按上一个小时峰值 单价进行计费
            $arr_times[] = [
                'begin' => $begin_time,
                'end'   => $end_time
            ];
        } else {
            $int = (int)$duration_hours;
            $float = $duration_hours - $int;
            $last = $begin_time;
            for ($i = 1; $i <= $int; $i++) {
                $arr_times[] = [
                    'begin' => $last,
                    'end'   => $last + Datetime::$hour,
                ];
                $last = $last + 1 + Datetime::$hour; //last需要加上1秒
            }
            if ($float) {
                $arr_times[] = [
                    'begin' => $last,
                    'end'   => $end_time,
                ];
            }
        }

        //写入时间点
        $new_fee_ids = [];
        $bool = self::transact(function() use ($uid, $history, $price_rules, $arr_times, &$new_fee_ids){
            $total_fee = 0;
            $bool = User::transact(function() use ($uid, $history, $price_rules, $arr_times, &$new_fee_ids, &$total_fee){
                $history_ip = $history['ip'];
                $history_id = $history['id'];
                foreach ($arr_times as $item) {
                    $hour_price = self::hour_price($history_ip, $price_rules, $item['begin'], $item['end']);
                    if ( !$hour_price ) {
                        Response::note('#tab时间段%s~%s内无峰值。', date('Y-m-d H:i:s', $item['begin']), date('Y-m-d H:i:s', $item['end']));
                        continue;
                    }
                    $duration = ($item['end'] - $item['begin']) / Datetime::$hour;
                    $fee = round($hour_price * $duration, 2);
                    $fee_data = [
                        'history_id' => $history_id,
                        'breaktime' => $item['end'],
                        'price' => $hour_price,
                        'duration' => $duration,
                        'fee'  => $fee,
                    ];
                    if ( !$ret = self::insert($fee_data) ) {
                        $msg = '#tab按需防护小计费用写入失败';
                        Notify::developer($msg, '断点小计费用数据包：'.var_export($fee_data, true));
                        return false;
                    } else {
                        Response::note('#tab本次小计费用：'.$fee);
                        $total_fee += $fee;
                        $new_fee_ids[] = $ret;
                    }
                }

                return true;
            });

            if (!$bool) return false;

            if (User::pay_money($uid, $total_fee)) {
                Response::note('#blank');
                Response::note('#tab以上共计%s次小计费用：%s，扣费成功', count($new_fee_ids), $total_fee);
                return true;
            } else {
                $msg = '#tab小计扣费失败';
                Notify::developer($msg);
                return false;
            }

        });

        if ($bool && count($new_fee_ids)) {
            Response::note('#tab增加了%s条的小计费用，时长：%s小时，',  count($new_fee_ids), $duration_hours);
        }

        return $bool;
    }


    /**
     * 计算攻击总费用
     * @param  [type] $history_id [description]
     * @return [type]             [description]
     */
    public static function total_fee($history_id) {
        return self::sum('fee', ['history_id' => $history_id]);
    }


    /**
     * 扣除本次攻击完整费用（总计扣费)
     * @param  int      $uid    用户编号
     * @return boolean  操作成功
     */
    public static function deduction($uid, $history) {
        //计算总费用
        $total_fee = self::total_fee($history['id']);

        //找出历史详细记录信息
        $history = DDoSHistory::find($history_id);

        $instance_ip = $history['ip'];

        //按需防护扣费日志数据包
        $feelog_mitigation_data = [
            'typekey' => 'pay_mitigation',
            'balance' => User::get_money($uid) - $total_fee,
            'instance_ip' => $instance_ip,
            'uid' => $uid,
            'amount' => $total_fee,
            'title' => sprintf(
                '实例：%s, IP：%s, 按需防护费用',
                $instance_ip, $instance['hostname'],
                date('Y-m')
            ),
        ];

        //写入总计费用日志
        $bool = !!FeeResponse::create($feelog_mitigation_data);
        Response::note('#tab本次共合计扣费：%s', $total_fee);
        Response::noteSuccessFail('#tab云盾合计扣费日志写入%s', $bool);

        //TO DO 清除小计记录

        return $bool;
    }
}
DDoSHistoryFee::init();