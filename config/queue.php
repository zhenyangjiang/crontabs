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
    ],

    'ddoscollecter' => [
        'name'      => 'DDos攻击源收集',
        'host'      => '172.31.50.7',
        'queue'     => 'DDosCollecter',
        'ttr'       => 60,
    ]
];