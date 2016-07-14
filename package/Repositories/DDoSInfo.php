<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Arr;

class DDoSInfo extends StaticRepository {
    protected static $connection = 'mitigation';
    protected static $datatable = 'ddosinfo';
    protected static $DAO;
    protected static $dt_parter = ['type' => 'datetime', 'mode' => 'ymd'];
    // protected static $dt_parter = 'special';

    public static function filte_blocked($pack) {
        if (!$pack) return array();

        $blocked_ips = Mitigation::get_ips_by_status('BLOCK');

        if ($blocked_ips) {
            $filte_ips = [];
            foreach ($blocked_ips as $ip) {
                if (array_key_exists($ip, $pack)) {
                    $filte_ips[] = $ip;
                    unset($pack[$ip]);
                }
            }

            //已被牵引的IP还存在防火墙被攻击列表中
            if ($filte_ips) {
                Response::note('#tab以下IP还处于牵引中，却还在流量，已被过滤掉：%s', implode(', ', $filte_ips));
            } else {
                Response::note('#tab没有IP处于被牵引中，无需过滤');
            }
        } else {
            Response::note('#tab没有IP存在于牵引中，无需过滤');
        }

        return $pack;
    }

    //保存来自防火墙的源数据
    public static function save($pack) {
        $ret = []; //返回合所有组的包
        if ($pack) { //确定是二维数组列表
            foreach ($pack as $group) {
                //存储数据
                $data = Arr::pick($group, ['dest', 'bps0', 'bps1', 'pps0', 'pps1']);
                $bool = self::import($data);
                if (!$bool) {
                    $message = '攻击数据导入到DDoS表时失败';
                    reportDevException($message, array('context' => $group));
                    Notify::developer($message);
                    System::halt();
                } else {
                    $ips = array_keys($group);
                    $ret = array_merge($ret, $ips);
                }
            }
        } else {
            Response::warn(colorize('空数据包，无需导入', 'yellow'));
        }

        return $ret;
    }


    /**
     * 取得某IP在某段时间内的峰值
     * @param  [type] $in_hours [description]
     * @return [type]           [description]
     */
    public static function peak($dest_ip, $begin, $end) {
        $awhere = ['dest' => $dest_ip, "created_at between $begin and $end"];

        $list = self::lists([
            'unions' => [$begin, $end],
            'awhere' => $awhere,
        ]);
        if ( !$list ) return NULL;

        $list = Arr::sort($list, 'bps0');
        $first = $list[0];
        $info_bps = [
            'time'  => date('Y-m-d H:i:s', $first['created_at']),
            'value' => $first['bps0']
        ];

        $list = Arr::sort($list, 'pps0');
        $first = $list[0];
        $info_pps = [
            'time'  => date('Y-m-d H:i:s', $first['created_at']),
            'value' => $first['pps0']
        ];

        return [
            'begin' => date('Y-m-d H:i:s', $begin),
            'end'   => date('Y-m-d H:i:s', $end),
            'mbps'  => $info_bps,
            'pps'   => $info_pps
        ];
    }

    /**
     * 根据mbps生成峰值数据包
     * @param  [type] $mbps  [description]
     * @param  [type] $begin [description]
     * @param  [type] $end   [description]
     * @return [type]        [description]
     */
    public static function genealPeakByMBps($mbps, $begin, $end) {
        $begin = date('Y-m-d H:i:s', $begin);
        $end = date('Y-m-d H:i:s', $end);
        return [
            'begin' => $begin,
            'end'   => $end,
            'mbps'  => [
                'value' => $mbps,
                'time' => $begin
            ],
            'pps'   => [
                'value' => Mitigation::Mbps_to_pps($mbps),
                'time' => $begin
            ]
        ];
    }
}
DDoSInfo::init();