<?php
return [
    //云主机（未设置自动续费）过期，数据仍保留，并尽快手工续费
    'DDOS-BEGIN' => [
        'inner' => [
            'title'   => '云盾遭受攻击',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ips} 开始遭到DDoS攻击',
            ],
        ],
        'email' => [
            'title'   => '云盾遭受攻击',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ips} 开始遭到DDoS攻击',
            ],
        ],
        'sms' => [
            'content' => '尊敬的用户{$user_name}：您的IP：{$ips} 正在遭受DDoS攻击',
        ]
    ],

    'DDOS-END' => [
        'inner' => [
            'title'   => '云盾被攻击结束',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ip} 已被结束攻击，本次攻击流量峰值：{$bps_peak}Mbps / {$pps_peak}pps',
            ],
        ],
        'email' => [
            'title'   => '云盾被攻击结束',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ip} 已被结束攻击，本次攻击流量峰值：{$bps_peak}Mbps / {$pps_peak}pps',
            ],
        ],
        'sms' => [
            'content' => '尊敬的用户{$user_name}：您的IP：{$ip} 已被结束攻击，本次攻击流量峰值：{$bps_peak}Mbps / {$pps_peak}pps',
        ]
    ],

    'IP-BLOCKED' => [
        'inner' => [
            'title'   => '云盾IP被牵引',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ip} 由于{$reason}，被黑洞牵引',
            ],
        ],
        'email' => [
            'title'   => '云盾IP被牵引',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ip} 由于{$reason}，被黑洞牵引',
            ],
        ],
        'sms' => [
            'content' => '您的IP：{$ip} 由于{$reason}，被黑洞牵引',
        ]
    ],

    'IP-UNBLOCK' => [
        'inner' => [
            'title'   => '云盾IP被解除牵引',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ips} 被解除牵引',
            ],
        ],
        'email' => [
            'title'   => '云盾IP被解除牵引',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的IP：{$ips} 被解除牵引',
            ],
        ],
        'sms' => [
            'content' => '您的IP：{$ips} 被解除牵引',
        ]
    ],
];
?>