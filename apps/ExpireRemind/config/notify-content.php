<?php
$no_reply_tips = '<br/><div style="color:#cccccc">本邮件由系统自动发送，请勿回复</div>';
return [
    //自动续费、余额将不足
    'instance_is_about_to_expire_not_enough_balance_for_auto_renew' => [
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）将{days}天后({expire_date})到期，由于您的余额不足以自动续费下个月的费用，请尽快充值，以免服务受影响。'.$no_reply_tips,
        ]
    ],

    //手工续费、余额将不足
    'instance_is_about_to_expire_not_enough_balance_for_manual_renew' => [
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）将{days}天后({expire_date})到期，请尽快充值并续费，以免服务受影响。'.$no_reply_tips,
        ]
    ],

    //手工续费、余额足够
    'instance_is_about_to_expire_enough_balance_for_manual_renew' => [
        'email' => [
            'title'   => '云主机过期续费提醒',
            'content' => '尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）将{days}天后({expire_date})到期，您的余额还能支付下个月的费用，请尽快登录系统进行续费操作，以免服务受影响。'.$no_reply_tips,
        ]
    ],
];
?>