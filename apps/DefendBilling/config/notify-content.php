<?php
return [
    //云主机（未设置自动续费）过期，数据仍保留，并尽快手工续费
    'attack_begin' => [
        'email' => [
            'title'   => '云主机开始遭受攻击',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）正在遭受攻击， 攻击流量：{mbps}，攻击报文：{pps}',
        ],
        'sms' => '您的云主机：您的云主机：{instance_name}（IP：{instance_ip}）正在遭受攻击， 攻击流量：{mbps}，攻击报文：{pps}',
    ],

    'attack_end' => [
        'email' => [
            'title'   => '云主机遭受结束',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）攻击已结束',
        ],
        'sms' => '您的云主机：您的云主机：{instance_name}（IP：{instance_ip}）攻击已结束',
    ],

    'attack_block' => [
        'email' => [
            'title'   => '云主机被牵引',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）正在遭受攻击',
        ],
        'sms' => '您的云主机：您的云主机：{instance_name}（IP：{instance_ip}）攻击已结束',
    ],
];
?>