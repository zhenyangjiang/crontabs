<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Response;

class DDoSInfo extends Repository {
    protected static $connection = 'mitigation';
    protected static $datatable = 'ddosinfo';
    protected static $DAO;
    protected static $dt_parter = ['type' => 'datetime', 'mode' => 'ymd'];
    // protected static $dt_parter = 'special';

    public static function filte_blocked_attack($pack) {
        if (!$pack) return array();
        $ips = array_keys($pack);
        $lists = Instance::lists([
            'fields' => 'mainipaddress as ip',
            'awhere' => ['net_state' => 2, 'mainipaddress' => $ips]
        ]);
        if ($lists) {
            $exception_ips = [];
            foreach ($lists as $item) {
                $exception_ips[] = $item['ip'];
                unset($pack[$item['ip']]);
            }

            //已被牵引的IP还存在防火墙被攻击列表中
            if ($exception_ips) {
                $message = sprintf('已被牵引的IP："%s"，还存在防火墙被攻击列表中', implode(', ', $exception_ips));
                reportOptException($message);
            }
        }
        return $pack;
    }

    //保存来自防火墙的源数据
    public static function save_attack($pack) {
        if ($pack) { //确定是二维数组列表
            //存储数据
            $bool = self::import($pack);
            if (!$bool) {
                $message = '攻击数据导入到DDoS表时失败';
                reportDevException($message, array('context' => $pack));
                Notify::developer($message);
                System::halt();
            }
        } else {
            Response::warn(colorize('空数据包，无需导入', 'yellow'));
        }

        return $pack;
    }


    /**
     * 取得某IP在某段时间内的峰值
     * @param  [type] $in_hours [description]
     * @return [type]           [description]
     */
    public static function get_attack_peak($dest_ip, $begin, $end) {
        $awhere = ['dest' => $dest_ip, "created_at between $begin and $end"];

        $info_bps = self::find([
            'unions' => [$begin, $end],
            'awhere' => $awhere,
            'order'  => 'bps0 desc'
        ]);
        if (!$info_bps) return NULL;
        $info_bps = [
            'time'  => date('Y-m-d H:i:s', $info_bps['created_at']),
            'value' => $info_bps['bps0']
        ];

        $info_pps = self::find([
            'unions' => [$begin, $end],
            'awhere' => $awhere,
            'order'  => 'pps1 desc'
        ]);
        if (!$info_pps) return NULL;
        $info_pps = [
            'time'  => date('Y-m-d H:i:s', $info_pps['created_at']),
            'value' => (int)$info_pps['pps1']
        ];

        return [
            'begin' => date('Y-m-d H:i:s', $begin),
            'end'   => date('Y-m-d H:i:s', $end),
            'mbps'  => $info_bps,
            'pps'   => $info_pps
        ];
    }
}
DDoSInfo::init();