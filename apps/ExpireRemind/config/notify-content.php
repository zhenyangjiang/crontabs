<?php
return [
    //自动续费、余额将不足
    'REMIND-NOT-ENOUGH-BALANCE-FOR-AUTO-RENEW' => [
        'inner' => [
            'title'   => '云主机过期续费提醒',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，由于您的余额不足以自动续费下个月的费用，请尽快充值，以免服务受影响。',
            ],
        ],
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，由于您的余额不足以自动续费下个月的费用，请尽快充值，以免服务受影响。',
            ],
        ],
        'sms' => [
            'content' => '您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，由于您的余额不足以自动续费下个月的费用，请尽快充值，以免服务受影响。'
        ]
    ],

    //手工续费、余额将不足
    'REMIND-NOT-ENOUGH-BALANCE-FOR-MANUAL-RENEW' => [
        'inner' => [
            'title'   => '云主机过期续费提醒',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，请尽快充值并续费，以免服务受影响。',
            ]
        ],
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，请尽快充值并续费，以免服务受影响。',
            ],
        ],
        'sms' => [
            'content' => '您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，请尽快充值并续费，以免服务受影响。',
        ]
    ],

    //手工续费、余额足够
    'REMIND-ENOUGH-BALANCE-FOR-MANUAL-RENEW' => [
        'inner' => [
            'title'   => '云主机过期续费提醒',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})
            到期，您的余额足够支付下个月的费用，请尽快登录系统进行续费操作，以免服务受影响。',
            ]
        ],
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => [
                '尊敬的用户{$user_name}：',
                '　　您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，您的余额足够支付下个月的费用，请尽快登录系统进行续费操作，以免服务受影响。',
            ],
        ],
        'sms' => [
            'content' => '您的云主机：{$instance_name}（IP：{$instance_ip}）将于{$days}天后({$expire_date})到期，您的余额足够支付下个月的费用，请尽快登录系统进行续费操作，以免服务受影响。',
        ]
    ],
];