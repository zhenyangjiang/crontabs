<?php
return [
    //云主机（未设置自动续费）过期，数据仍保留，并尽快手工续费
    'INSTANCE_EXPIRE_WITH_RETAIN_DATA' => [
        'inner' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，相关数据将继续保留{retain_days}天后被清空，请您尽快并续费。',
        ],
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，相关数据将继续保留{retain_days}天后被清空，请您尽快并续费。',
        ],
        'sms' => '您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，相关数据将继续保留{retain_days}天后被清空，请您尽快并续费。',
    ],

    //余额不足，云主机（设置了自动续费）过期，数据仍保留，并尽快充值
    'instance_expire_retain_auto' => [
        'inner' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，由于余额不足，系统无法为您自动续费，相关数据将将继续保留{retain_days}天后被清空，请您尽快并充值后续费。',
        ],
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，由于余额不足，系统无法为您自动续费，相关数据将将继续保留{retain_days}天后被清空，请您尽快并充值后续费。',
        ],
        'sms' => '您的云主机：{instance_name}（IP：{instance_ip}）已过期{expire_days}天，由于余额不足，系统无法为您自动续费，相关数据将将继续保留{retain_days}天后被清空，请您尽快并充值后续费。'
    ],

    //自动续费成功
    'instance_auto_renew_success' => [
        'inner' => [
            'title' => '云主机自动续费成功',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}于{old_expire}成功自动续费一个月。续费金额：￥{renew_money}, 有效期：{new_expire}。'
        ],
        'email' => [
            'title' => '云主机自动续费成功',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}于{old_expire}成功自动续费一个月。续费金额：￥{renew_money}, 有效期：{new_expire}。'
        ],
        'sms' => '您的云主机：{instance_name} 于{old_expire}成功自动续费一个月。续费金额：￥{renew_money}, 有效期：{new_expire}。'
    ]
];
?>