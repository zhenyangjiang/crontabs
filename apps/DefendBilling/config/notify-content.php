<?php
return [
    //云主机（未设置自动续费）过期，数据仍保留，并尽快手工续费
    'DDOS-BEGIN' => [
        'email' => [
            'title'   => '云服务遭受攻击',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的IP：{ips} 正在遭受DDoS攻击',
        ],
        'sms' => '尊敬的用户{user_name}：您的IP：{ips} 正在遭受DDoS攻击',
    ],

    'DDOS-END' => [
        'email' => [
            'title'   => '云主机遭受结束',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）攻击已结束，本次攻击流量峰值：{bps_peak}Mbps / {pps_peak}pps',
        ],
        'sms' => '您的云主机：{instance_name}（IP：{instance_ip}）攻击已结束，本次攻击流量峰值：{bps_peak}Mbps / {pps_peak}pps',
    ],

    'BLOCKIP' => [
        'email' => [
            'title'   => '云主机被牵引',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）被系统黑洞牵引',
        ],
        'sms' => '您的云主机：{instance_name}（IP：{instance_ip}）被系统黑洞牵引',
    ],

    'UNBLOCKIP' => [
        'email' => [
            'title'   => '云主机被牵引',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）被系统黑洞牵引',
        ],
        'sms' => '您的云主机：您的云主机：{instance_name}（IP：{instance_ip}）被系统黑洞牵引',
    ],
];
?>