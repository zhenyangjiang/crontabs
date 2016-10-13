<?php
return [
    'url'   => 'tcp://'. env('hosts.hprose', '172.31.66.100:2016'),
    'async' => false,
];