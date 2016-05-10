<?php
return [
    'notify'    => [
        'name'      => '通知队列',
        'host'      => '172.31.66.132',
        'queue'     => 'Notify',
        'ttr'       => 60,
    ],

    'blackhole'    => [
        'name'      => '黑洞牵引队列',
        'host'      => '172.31.66.132',
        'queue'     => 'BlackHole',
        'ttr'       => 60,
    ],

    'report-ddossource' => [
        'name'      => '上报DDos攻击源',
        'host'      => '172.31.66.132',
        'queue'     => 'ReportDDosSource',
        'ttr'       => 60,
    ],

    'report-exception' => [
        'name'      => '异常上报',
        'host'      => '172.31.66.132',
        'queue'     => 'ReportException',
        'ttr'       => 60,
    ],
];