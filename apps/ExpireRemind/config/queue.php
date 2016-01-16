<?php
return [
    'default'       => 'pheanstalk',

    'pheanstalk'    => [
        'host'      => '172.31.50.7',
        'queue'     => 'Crontabs',
        'ttr'       => 60,
    ]
];