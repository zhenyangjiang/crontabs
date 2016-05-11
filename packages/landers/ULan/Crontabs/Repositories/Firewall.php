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
            System::halt('读取无法解析的防火墙数据错误！');
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
                    'types'     => implode(',', $item['types']),
                    'src'       => implode(',', $item['src']),
                    'bps0'      => $item['bps'][0],
                    'bps1'      => $item['bps'][1],
                    'pps0'      => $item['pps'][0],
                    'pps1'      => $item['pps'][1],
                ];
            }
        }
        return $pack;
    }


    //确保大网安全
    public static function makeSafe($attack_pack) {
        dp($attack_pack);
    }

    public static function make_attacks($data){
        // $tpl = '{"123.1.1.2":{"dest":"123.1.1.2","type":"syn,ack","src":"90.74.202.5,106.51.71.58,51.74.180.35","bps0":45732.78,"bps1":2.15,"pps0":47786466,"pps1":3187}}';

        $ips = [];
        $ips[] = '123.1.1.10';
        $ips[] = '123.1.1.100';
        $ips[] = '123.1.1.101';
        foreach ($ips as $ip) {
            $data[$ip] = $data['123.1.1.2'];
            $data[$ip]['dest'] = $ip;
            $data[$ip]['src'] = self::make_by_src_random_ip($data[$ip]['src']);
        }
        return $data;
    }
    private static function make_by_src_random_ip($ips){
        $ips = array_map(function($ip){
            $nums = array_map(function() {
                return rand(1, 255);
            }, explode('.', $ip));
            return implode('.', $nums);
        }, explode(',', $ips));
        return implode(',', $ips);
    }
}