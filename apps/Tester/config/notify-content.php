<?php
return [
    'test' => [
        'email' => [
            'title'   => '[test]云主机过期续费提醒',
            'content' => '[test]尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）将{days}天后({expire_date})到期，由于您的余额不足以自动续费下个月的费用，请尽快充值，以免服务受影响。',
        ],
        'message' => [
            'title'   => '[test]云主机过期续费提醒',
            'content' => '[test]尊敬的用户{user_name}：'.PHP_EOL.'　　您的云主机：{instance_name}（IP：{instance_ip}）将{days}天后({expire_date})到期，由于您的余额不足以自动续费下个月的费用，请尽快充值，以免服务受影响。',
        ],
        'sms' => '[test]亲爱的用户，您的验证码是251423。有效期为10分钟，请尽快验证'
    ],
];
?>