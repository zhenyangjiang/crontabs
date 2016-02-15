<?php
use Landers\Utils\Arr;
use Landers\Utils\Http;
use Landers\Framework\Core\Config;

Class Firewall {
    public static function get_attack() {
        $urls = Config::get('fwurls');
        if (!$urls) {
            System::halt('未设置防火墙数据源URL');
        }
        foreach ($urls as $url) {
            $content = Http::get($url);
            if (!$content) continue;

            $pack = json_decode($content, true);
            foreach ($pack as $dest_ip => $item) {
                if ($item['bps'][0] <= 1 ||
                    $item['pps'][0] <= 1
                ) continue;

                $ret[$dest_ip] = [
                    'dest'      => $dest_ip,
                    'type'      => implode(',', $item['types']),
                    'src'       => implode(',', $item['src']),
                    'bps0'      => $item['bps'][0],
                    'bps1'      => $item['bps'][1],
                    'pps0'      => $item['pps'][0],
                    'pps1'      => $item['pps'][1],
                ];
            }
        }
        return $ret;
    }

    public static function make_attack(){
        $tpl = '{"123.1.1.2":{"dest":"123.1.1.2","type":"syn,ack","src":"90.74.202.5,106.51.71.58,51.74.180.35","bps0":45732.78,"bps1":2.15,"pps0":47786466,"pps1":3187}}';
        $ips = [];
        $ips[] = '123.1.1.10';
        $ips[] = '123.1.1.100';
        $ips[] = '123.1.1.101';

        $data = json_decode($tpl, true);
        foreach ($ips as $ip) {
            $data[$ip] = $data['123.1.1.2'];
            $data[$ip]['dest'] = $ip;
        }
        unset($data['123.1.1.2']);
        return $data;
    }
}