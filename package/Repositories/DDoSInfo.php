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

    private static $repo;
    public static function init() {
        self::$repo = repository('dDoSInfo');
        parent::init();
    }


    /**
     * 过滤掉已被牵引的IP
     * @param  [type] $pack [description]
     * @return [type]       [description]
     */
    public static function filteBlocked($pack) {
        if (!$pack) return array();
        $blocked_ips = IPBase::getByStatus('BLOCK');

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
                Response::reply('#tab已过滤掉“处于牵引中，却还存在流量”的IP：%s', implode(', ', $filte_ips));
            } else {
                Response::reply('#tab没有IP处于被牵引中，无需过滤');
            }
        } else {
            Response::note('#tab没有IP存在于牵引中，无需过滤');
        }

        return $pack;
    }

    //保存来自防火墙的源数据
    public static function save($pack) {
        $ret_ips = []; //返回合所有组的包
        if ($pack) { //确定是二维数组列表
            foreach ($pack as $dc_id => $mitigations) {
                foreach ($mitigations as $mit_id => $mitigation) {
                    $data = &$mitigation['ddosinfos'];
                    $data = Arr::pick($data, 'dest, bps0, bps1, pps0, pps1');
                    $bool = self::import($data);

                    //存储数据
                    if (!$bool) {
                        $message = '攻击数据导入到DDoS表时失败';
                        reportDevException($message, array('context' => $data));
                        Notify::developer($message);
                        System::halt();
                    } else {
                        $ips = Arr::pick($data, 'dest');
                        $ret_ips = array_merge($ret_ips, $ips);
                    }

                    unset($data);
                }
            }
        } else {
            Response::warn('#tab'.colorize('空数据包，无需导入', 'yellow'));
        }

        return $ret_ips;
    }

    /**
     * 取得某IP在某段时间内的峰值
     * @param  [type] $in_hours [description]
     * @return [type]           [description]
     */
    public static function peak($dest_ip, $begin, $end) {
        return self::$repo->peakInfo($dest_ip, $begin, $end);
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