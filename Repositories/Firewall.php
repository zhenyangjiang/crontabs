<?php
use Landers\Substrate\Utils\Arr;
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;

Class Firewall {
    public static function get_attack() {
        $fwurl = Config::get('fwurl');
        if ( !$content = Http::get($fwurl) ) {
            System::halt('防火墙数据读取失败！');
        }
        if (!$pack = json_decode($content, true)) {
            System::halt('读取无法解析的防火墙数据错误: '.$content);
        }

        if ( !count($pack[0]) ) {
            System::halt('防火墙数据异常！ ');
        }

        foreach ($pack as &$group) {
            foreach ($group as $dest_ip => &$item) {
                if ($item['bps'][0] <= 1 ||
                    $item['pps'][0] <= 1
                ) {
                    unset($group[$dest_ip]);
                    continue;
                }

                $item = [
                    'dest'      => $dest_ip,
                    // 'types'     => implode(',', $item['types']),
                    // 'src'       => implode(',', $item['src']),
                    'bps0'      => $item['bps'][0],
                    'bps1'      => $item['bps'][1],
                    'pps0'      => $item['pps'][0],
                    'pps1'      => $item['pps'][1],
                ];
            }
        }
        return $pack;
    }

    public static function make_attacks($data){
        // $tpl = '{"123.1.1.2":{"dest":"123.1.1.2","type":"syn,ack","src":"90.74.202.5,106.51.71.58,51.74.180.35","bps0":45732.78,"bps1":2.15,"pps0":47786466,"pps1":3187}}';

        $ips = [];
        $ips[] = ['ip' => '172.31.52.244', 'bps' => 3024.66];
        $ips[] = ['ip' => '172.31.52.243', 'bps' => 6521.55];
        $ips[] = ['ip' => '172.31.52.242', 'bps' => 8744.15];
        $ips[] = ['ip' => '172.31.52.239', 'bps' => 4424.66];
        $ips[] = ['ip' => '172.31.52.240', 'bps' => 2324.66];
        $ips[] = ['ip' => '172.31.52.238', 'bps' => 5541.66];
        $ips[] = ['ip' => '192.168.237.188', 'bps' => 65844.66];
        $ips[] = ['ip' => '172.31.52.237', 'bps' => 1254.66];
        $ips[] = ['ip' => '172.31.52.236', 'bps' => 5511.66];
        $ips[] = ['ip' => '172.31.52.235', 'bps' => 3322.55];
        foreach ($data as &$group) {
            foreach ($ips as $item) {
                $ip = $item['ip']; $bps = $item['bps'];
                $group[$ip] = $group['123.1.1.2'];
                $group[$ip]['dest'] = $ip;
                $group[$ip]['bps0'] = $bps;
                // $data[$ip]['src'] = self::make_by_src_random_ip($data[$ip]['src']);
            }
        }
        return $data;
    }
    // private static function make_by_src_random_ip($ips){
    //     $ips = array_map(function($ip){
    //         $nums = array_map(function() {
    //             return rand(1, 255);
    //         }, explode('.', $ip));
    //         return implode('.', $nums);
    //     }, explode(',', $ips));
    //     return implode(',', $ips);
    // }
}