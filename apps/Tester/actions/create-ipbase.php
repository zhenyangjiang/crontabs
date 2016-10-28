<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Datetime;

include 'inc-headline.php';

$mitigations = Mitigation::lists();
Response::note('从云盾表导入到IPBase...');
$data = [];
foreach ($mitigations as $item) {
    if (!$item['ip']) continue;
    $data[] =  [
        'uid' => $item['uid'],
        'ip' => $item['ip'],
        'mit_id' => $item['id'],
        'status' => $item['status'],
        'block_expire' => $item['block_expire'],
    ];
}

$bool = IPBase::import($data);
Response::echoBool($bool);

Response::note('#line');
System::complete();