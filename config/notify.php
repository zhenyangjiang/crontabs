<?php
return [
    'email' => [
        'host'          => 'smtp.qq.com',
        'port'          => '465',
        'username'      => '2144576175',
        'password'      => 'wykylypduvdwdgdg',
        'from_email'    => 'notify-server@qq.com',
        'from_name'     => ENV_system_name,
        'retries'       => 2, //重试次数
    ],
    'sms'   => [
        'apikey'        => '271f9c48e96a10d62ffb5b8da8f56176',
        'sign'          => '【壹云云计算】'
    ]
];