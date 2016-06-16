<?php
return [
    'notify'    => [
        'name'      => '通知队列',
        'host'      => '172.31.66.132',
        'queue'     => 'Crontab.Notify',
        'ttr'       => 60,
    ],

    'blackhole'    => [
        'name'      => '黑洞牵引队列',
        'host'      => '172.31.66.132',
        'queue'     => 'Crontab.BlackHole',
        'ttr'       => 60,
    ],

    'report-exception' => [
        'name'      => '异常上报',
        'host'      => '172.31.66.132',
        'queue'     => 'Crontab.ReportException',
        'ttr'       => 60,
    ],
];