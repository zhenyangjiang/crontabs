<?php
return [
    'notify'    => [
        'name'      => '通知队列',
        'host'      => '172.31.50.7',
        'queue'     => 'Notify',
        'ttr'       => 60,
    ],

    'blackhole'    => [
        'name'      => '黑洞牵引队列',
        'host'      => '172.31.50.7',
        'queue'     => 'BlackHole',
        'ttr'       => 60,
    ]
];