<?php
return [
    //云主机（未设置自动续费）过期，数据仍保留，并尽快手工续费
    'instance_expire_retain_manual' => [
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，相关数据将继续保留{retain_days}天后被清空，请您尽快并续费。',
        ],
        'sms' => '您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，相关数据将继续保留{retain_days}天后被清空，请您尽快并续费。',
    ],

    //余额不足，云主机（设置了自动续费）过期，数据仍保留，并尽快充值
    'instance_expire_retain_auto' => [
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，由于余额不足，系统无法为您自动续费，相关数据将将继续保留{retain_days}天后被清空，请您尽快并充值后续费。',
        ],
        'sms' => '您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，由于余额不足，系统无法为您自动续费，相关数据将将继续保留{retain_days}天后被清空，请您尽快并充值后续费。'
    ]
];
?>