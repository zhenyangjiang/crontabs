<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;

class DDoSHistory extends StaticRepository
{
    protected static $connection = 'mitigation';
    protected static $datatable = 'ddoshistory';
    protected static $DAO;

    // 不可以采用分表，它的id与DDoSHistoryFee有关联
    // protected static $dt_parter = ['type' => 'datetime', 'mode' => 'ym'];

    private static $repo;

    public static function init()
    {
        self::$repo = repository('dDoSHistory');
        parent::init();
    }

    /**
     * 插入ip攻击开始时间
     * @param  string $ip ip
     * @return array            新增的记录集合
     */
    public static function saveAttackStart($ips)
    {
        $ips = (array)$ips;
        if (!$ips) return false;

        //获取IP表中：1)在ips范围内的IP， 2)标为“正常”的IP
        $ipbases = IPBase::lists([
            'fields' => 'ip, mit_id',
            'awhere' => ['ip' => $ips, 'status' => 'NORMAL']
        ]);

        $ips = [];
        if ($ipbases) {
            $ips = Arr::pick($ipbases, 'ip');
            $transacter = '写入攻击开始、更新实列状态为被攻击中';
            Response::transactBegin($transacter);
            $result = self::transact(function () use (&$ips, $ipbases) {
                //准备导入history表
                $data = [];

                // 把 Mitigation 中标记为正常的IP，不应出现在 “写攻击开始” 数据中
                $histories = self::lists([
                    'fields' => 'ip',
                    'awhere' => ['end_time' => NULL, 'ip' => $ips]
                ]);
                if ($histories) {
                    $his_ips = Arr::pick($histories, 'ip');
                    $message = sprintf('以下IP在攻击历史中为被攻击中，却在IP库中标记为正常');
                    reportDevException($message, compact('his_ips'));
                    Response::note('#tab' . implode(',', $his_ips));
                    Response::note('#tab对以上IP进行过滤，方可对剩下的IP写入攻击开始');
                    $ips = Arr::remove($ips, $his_ips);
                    if (!$ips) {
                        System::halt('#tab数据已过滤为空数据包，本次任务提前结束');
                    }
                }

                //批量导入数据
                foreach ($ipbases as $item) {
                    $data[] = [
                        'ip' => $item['ip'],
                        'mit_id' => $item['mit_id'],
                        'begin_time' => System::startTime()
                    ];
                }
                Response::note('#tab即将对以下IP写入攻击历史：');
                Response::echoText(implode('，', $ips));
                Response::note('#tab写入“攻击开始”...');
                $bool = self::import($data);
                Response::echoBool($bool);
                if (!$bool) return false;

                Response::note('#tab更新“IP表为被攻击中”...');
                $ips = Arr::pick($data, 'ip');
                $bool = IPBase::setStatus($ips, 'ATTACK', true);
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

        return $ips;
    }

    /**
     * 更新ip攻击结束时间
     * @param  array|string $ips ip
     * @return bool                     数据更新成功否
     */
    public static function saveAttackEnd($ip, $on_event)
    {
        list($bool, $data, $message) = self::$repo->saveAttackEnd($ip, $on_event);
        if (!$bool) {
            Notify::developer($message, $data);
        } else {
            Response::echoBool(true);
            if ($message) {
                Response::noteColor('yellow', $message);
            }
            if ($data) {
                // 自然结束时才告警
                if ($on_event == 'STOP') Alert::endDDoS($ip, $data);
            }
        }
        return $bool;
    }

    /**
     * 取得指定IP，且在攻击中历史记录
     * @param  [type] $ip [description]
     * @return [type]     [description]
     */
    public static function findByAttackingIp($ip)
    {
        return self::find([
            'awhere' => ['end_time' => NULL, 'ip' => $ip],
            'order' => 'begin_time desc',
        ]);
    }

    /**
     * 确定每小时单价
     * @param  string $ip IP
     * @param  array $price_rules 价格规则
     * @param  int $begin_time 开始时间
     * @param  int $end_time 结束时间
     * @param  array &$peak_info 返回峰值信息
     * @return float
     */
    private static function hourPrice($ip, $price_rules, &$peak_info = array())
    {
        $begin_time = $peak_info['begin'];
        $end_time = $peak_info['end'];

        Response::note('确定%s ~ %s内的每小时单价：', $begin_time, $end_time);

        $peak_bps = $peak_info['mbps'];
        $peak_pps = $peak_info['pps'];

        $ret_price = 0; //返回单价数据
        $price_keys = array_keys($price_rules);
        $price_key_bps = (int)array_search_less_that($price_keys, $peak_bps['value']);
        $price_key_pps = (int)array_search_less_that($price_keys, $peak_pps['value'], function ($item) {
            return mitigation::Gbps_to_pps($item);
        });
        $price_bps = $price_rules[$price_key_bps];
        $price_pps = $price_rules[$price_key_pps];

        $price_key_bps = Mitigation::Mbps_to_Gbps($price_key_bps);

        if ($price_bps == $price_pps) {
            $ret_price = $price_bps;
            if ($ret_price) {
                Response::note('#tab在%s峰值%sMbps/%spps最接近规格%sGbps的价格为：%s元/小时', $peak_bps['time'], $peak_bps['value'], $peak_pps['value'], $price_key_bps, $ret_price);
            } else {
                Response::note('#tab单价为0，免费防护规格');
            }
        } else {
            if ((int)$price_bps > (int)$price_pps) {
                $ret_price = $price_bps;
                Response::note('#tab在%s峰值%sMbps最接近规格%sGbps的价格为：%s元/小时', $peak_bps['time'], $peak_bps['value'], $price_key_bps, $ret_price);
            } else {
                $ret_price = $price_pps;
                Response::note('#tab在%s峰值%spps最接近规格%sMbps的价格为：%s元/小时', $peak_pps['time'], $peak_pps['value'], $price_key_pps, $ret_price);
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
    public static function calcFee($history, $price_rules, &$peak_info = array(), &$duration = 0)
    {
        $ip = $history['ip'];
        $begin_time = $history['begin_time'];
        $end_time = time();

        $peak_info or $peak_info = DDoSInfo::peak($ip, $begin_time, $end_time);
        if (!$peak_info) {
            $echo = Response::warn('#tab未找到峰值或峰值为0');
            reportDevException($echo, compact('ip', 'price_rules', 'begin_time', 'end_time'));
            return 0;
        }

        // 确定此时间段内的单价：由此时间段的峰值决定
        $hour_price = self::hourPrice($ip, $price_rules, $peak_info);

        // 确定此时间段的时长，由秒转换成小时
        $duration_seconds = $end_time - $begin_time;
        $duration_seconds = $duration_seconds < 60 ? 60 : $duration_seconds; //不足1分钟，按1分钟时长计算
        $duration = round($duration_seconds / 3600, 2);

        return round($hour_price * $duration);
    }

    /**
     * 实际计费扣费
     * @return [type] [description]
     */
    public static function billing($uid, $history, $price_rules, $use_bps = NULL)
    {
        $ip = $history['ip'];

        if (!Mitigation::checkServiceStatus($ip)) {
            Response::reply('IP:%s所对应的服务处于非正常状态，免计费！', $ip);
        }

        if ($use_bps) {
            $begin_time = $history['begin_time'];
            $end_time = time();
            Response::reply('根据指定值bps：%sMbps, 构造峰值信息', $use_bps);
            $peak_info = DDoSInfo::genealPeakByMBps($use_bps, $begin_time, $end_time);
        } else {
            $peak_info = array();
        }

        $fee = self::calcFee($history, $price_rules, $peak_info, $duration);

        if (!$duration) {
            Response::note('瞬间大流量攻击，时长过短，暂不计费');
            return true;
        }

        if (!$fee) {
            Response::note('未产生费用，无需扣费');
            return true;
        }

        //按需防护扣费日志数据包
        $feelog_data = [
            'description' => sprintf(
                '支付按需防护费用：IP：%s, 持续%s小时, 峰值：%sMbps/%spps',
                $ip, $duration, $peak_info['mbps']['value'], $peak_info['pps']['value']
            ),
            'amount' => $fee,
            'privilege' => 0,
            'occur_way' => '余额支付',
            'typekey' => 'pay_mitigation_hour',
        ];

        Response::transactBegin('用户扣费、写入扣费日志');
        Response::note('#tab本次攻击共持续：%s小时，费用：￥%s', $duration, $fee);
        //允许欠费
        $arrears = true;
        User::expend($uid, $fee, $feelog_data, $arrears);
        return Response::transactEnd(true);
    }
}

DDoSHistory::init();
?>