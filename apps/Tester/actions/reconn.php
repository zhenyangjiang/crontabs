<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试数据库断开后重连');


Response::note('首次连接上数据库并读取数据...');
$db = Instance::db();

$ret = $db->tables();
Response::echoBool(!!$ret);

while ( true ) {

    Response::note('关闭数据库连接...');
    $bool = $db->close();
    Response::echoBool($bool);

    Response::note('再次读取数据...');
    $ret = $db->tables();
    Response::echoBool(!!$ret);

    sleep(2);
}

// dp(DDoSInfo::db()->connection->conns);
System::complete();