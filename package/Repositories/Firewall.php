<?php
use Landers\Substrate\Utils\Arr;
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Framework\Modules\Log;


        //$content = '[{"123.1.1.6": {"src": ["110.61.224.49", "242.204.209.124", "65.186.42.191"], "types": ["SYN", "ACK"], "syn": {"bps": [7034.32, 0 ], "pps": [7374170, 0 ] }, "ack": {"bps": [5387.41, 0.56 ], "pps": [5565760, 835 ] }, "udp": {"bps": [0, 0 ], "pps": [0, 0 ] }, "icmp": {"bps": [0, 0 ], "pps": [0, 0 ] }, "frag": {"bps": [0, 0 ], "pps": [0, 0 ] }, "other": {"bps": [0, 0 ], "pps": [0, 0 ] }, "dns": {"bps": [0, 0 ], "pps": [0, 0 ] }, "bps": [12421.74, 0.56 ], "pps": [12939931, 835 ], "links": [34496, 0 ], "tcplinks": [34496, 0 ], "udplinks": [0, 0 ], "time": 1464401143 } } ]';




Class Firewall {
    public static function getAttacks() {
        $fwurl = config('fwurl');
        Response::note('#tab数据来源：%s ', $fwurl);

        // $content = include( __DIR__ . '/Firewall-data3.php');
        if ( (!isset($content)) && (!$content = Http::get($fwurl)) ) {
            System::halt('#tab防火墙数据读取失败！');
        }

        if (!$data = json_decode($content, true)) {
            if (is_array($data)) {
                Response::warn('#tab获取到空数据');
            } else {
                System::halt('#tab读取无法解析的防火墙数据错误: '.$content);
            }
        } else {
            if ( env('log.firewall') ) {
                Response::note('#tab截取防火墙数据到日志中...');
                $file = Log::trace('防火墙数据', $data);
                Response::echoBool( !!$file );
            }
        }

        //额外加一个调用用的IP
        if ($data && env('debug')) {
            $tmp = $data[array_rand($data)];
            // $data = [ '123.1.1.11' => $tmp ];
            $data['123.1.1.16'] = $tmp;
            $tmp = $data[array_rand($data)];
            $data['123.1.1.15'] = $tmp;
            // if ( $xip = System::argv(3)) {
            //     $xbps = System::argv(4);
            //     if ($xbps === 'NONE') {
            //         unset($data[$xip]);
            //     } else {
            //         $data[$xip]['bps'][0] = $xbps;
            //         $data[$xip]['pps'][0] = 19000000;
            //     }
            // }
        }

        $ret = []; $filte1_count = 0;
        foreach ($data as $dest_ip => &$item) {
            // 过滤掉 小于1 的攻击数据
            if ($item['bps'][0] <= 1 ||
                $item['pps'][0] <= 1
            ) {
                unset($data[$dest_ip]);
                $filte1_count++;
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

        if ( $filte1_count ) {
            Response::note('#tab已忽略 %s 项攻击数据', $filte1_count);
        }



        return $data;
    }


    public static function makeAttacks($data){
        $ips = [
            ['ip' => '123.1.1.2', 'bps' => 3024.66],
            ['ip' => '123.1.1.254', 'bps' => 3024.66],
            ['ip' => '123.1.1.253', 'bps' => 6521.55],
            ['ip' => '123.1.1.252', 'bps' => 8744.15],
            // ['ip' => '123.1.1.239', 'bps' => 4424.66],
            ['ip' => '123.1.1.240', 'bps' => 2324.66],
            // ['ip' => '123.1.1.238', 'bps' => 5541.66],
            // ['ip' => '192.168.237.188', 'bps' => 65844.66],
            // ['ip' => '123.1.1.237', 'bps' => 1254.66],
            // ['ip' => '123.1.1.236', 'bps' => 5511.66],
            // ['ip' => '123.1.1.235', 'bps' => 3322.55],
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