<?php
use Landers\Substrate\Utils\Arr;
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;
        //$content = '[{"123.1.1.6": {"src": ["110.61.224.49", "242.204.209.124", "65.186.42.191"], "types": ["SYN", "ACK"], "syn": {"bps": [7034.32, 0 ], "pps": [7374170, 0 ] }, "ack": {"bps": [5387.41, 0.56 ], "pps": [5565760, 835 ] }, "udp": {"bps": [0, 0 ], "pps": [0, 0 ] }, "icmp": {"bps": [0, 0 ], "pps": [0, 0 ] }, "frag": {"bps": [0, 0 ], "pps": [0, 0 ] }, "other": {"bps": [0, 0 ], "pps": [0, 0 ] }, "dns": {"bps": [0, 0 ], "pps": [0, 0 ] }, "bps": [12421.74, 0.56 ], "pps": [12939931, 835 ], "links": [34496, 0 ], "tcplinks": [34496, 0 ], "udplinks": [0, 0 ], "time": 1464401143 } } ]';

        //$content = '{"123.1.1.6":{"src":["74.156.179.21","175.211.37.169","59.87.157.192"],"types":["SYN","ACK"],"syn":{"bps":[5541.41,0],"pps":[5811169,0]},"ack":{"bps":[4122.53,0.56],"pps":[4269565,839]},"udp":{"bps":[0,0],"pps":[0,0]},"icmp":{"bps":[0,0],"pps":[0,0]},"frag":{"bps":[0,0],"pps":[0,0]},"other":{"bps":[0,0],"pps":[0,0]},"dns":{"bps":[0,0],"pps":[0,0]},"bps":[9663.94,0.56],"pps":[10080735,839],"links":[34122,0],"tcplinks":[34122,0],"udplinks":[0,0],"time":1464422713}}'
Class Firewall {
    public static function get_attack() {
        $fwurl = Config::get('fwurl');

        if ( !$content = Http::get($fwurl) ) {
            System::halt('防火墙数据读取失败！');
        }

        if (!$data = json_decode($content, true)) {
            System::halt('读取无法解析的防火墙数据错误: '.$content);
        }

        // $mitigations = Mitigation::lists([
        //     'awhere' => ['ip' => array_keys($data)],
        //     'askey' => 'ip'
        // ]);

        $ret = [];
        foreach ($data as $dest_ip => &$item) {


            if ($item['bps'][0] <= 1 ||
                $item['pps'][0] <= 1
            ) {
                unset($data[$dest_ip]);
                continue;
            }
            $item = [
                'dest'      => $dest_ip,
                'bps0'      => $item['bps'][0],
                'bps1'      => $item['bps'][1],
                'pps0'      => $item['pps'][0],
                'pps1'      => $item['pps'][1],
            ];
            // $ret[$dc_id][$dest_ip] = $item;
        }

        return $data;
    }

    /**
     * 给攻击数据分组，并给每个被攻ip附上mitigation数据
     * @param  [type] $data        [description]
     * @param  [type] $mitigations [description]
     * @return [type]              [description]
     */
    public static function groupBy($data, $mitigations) {
        $ret = []; $dc_ids = [];

        //分组并附上mitigation
        foreach ($data as $dest_ip => $item) {
            $mitigation = $mitigations[$dest_ip];
            if ( $mitigation ) {
                $dc_id = $mitigation['datacenter_id'];
                $dc_ids[] = $dc_id;
            } else {
                $dc_id = 0;
            }
            $ret[$dc_id] or $ret[$dc_id] = [];
            $item['mitigation'] = $mitigation;
            $ret[$dc_id][$dest_ip] = $item;
        }

        //附上数据中心
        $dc_ids = array_unique($dc_ids);
        $dcs = DataCenter::listById($dc_ids);
        $dcs = Arr::rekey($dcs, 'id');
        foreach ($ret as $dc_id => &$items) {
            foreach ($items as &$item){
                $item['datacenter'] = $dcs[$dc_id];
            }; unset($item);
        }; unset($items);
        return $ret;
    }

    public static function make_attacks($data){
        $ips = [
            ['ip' => '123.1.1.2', 'bps' => 3024.66],
            ['ip' => '172.31.52.244', 'bps' => 3024.66],
            ['ip' => '172.31.52.243', 'bps' => 6521.55],
            ['ip' => '172.31.52.242', 'bps' => 8744.15],
            // ['ip' => '172.31.52.239', 'bps' => 4424.66],
            ['ip' => '172.31.52.240', 'bps' => 2324.66],
            // ['ip' => '172.31.52.238', 'bps' => 5541.66],
            // ['ip' => '192.168.237.188', 'bps' => 65844.66],
            // ['ip' => '172.31.52.237', 'bps' => 1254.66],
            // ['ip' => '172.31.52.236', 'bps' => 5511.66],
            // ['ip' => '172.31.52.235', 'bps' => 3322.55],
        ];
        foreach ($data as &$item1) {
            foreach ($ips as $item2) {
                $ip = $item2['ip'];
                $bps = $item2['bps'];

                $data[$ip] = array_slice($item1, 0);
                $data[$ip]['dest'] = $ip;
                $data[$ip]['bps0'] = $bps;
            }
        }
        return $data;
    }
}