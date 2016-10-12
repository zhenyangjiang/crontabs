<?php
return [
    'default' => 'localhost',

    'localhost' => [
        'scheme'   => 'tcp',
        'host'     => env('hosts.redis', '172.31.66.131'),
        'port'     => 6379,
        'database' => 15
    ]
];
?>