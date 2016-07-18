<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

function parseTemplate($tpl, $data) {
    extract($data); $a = 'AAAAAAAAAAAAA';
    $tpl2 = eval("return \"$tpl\";");
    return $tpl2;
}


$tpl = '{$username},你好';
Response::note( parseTemplate($tpl, [
    'username' => '"eval( return 11111);"'
]));

Response::note('#line');
System::complete();