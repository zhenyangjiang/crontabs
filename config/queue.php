<?php
return [
    'notify'    => [
        'name'      => '通知开发者队列',
        'host'      => env('hosts.queue', '172.31.66.132'),
        'queue'     => 'Crontab.Notify',
        'ttr'       => 60,
    ],

    'blackhole'    => [
        'name'      => '黑洞牵引队列',
        'host'      => env('hosts.queue', '172.31.66.132'),
        'queue'     => 'Crontab.BlackHole',
        'ttr'       => 60,
    ],
];