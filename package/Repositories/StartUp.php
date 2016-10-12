<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;

class StartUp {
    public static function check() {
        exec('dmidecode -t 1', $output, $return);
        if ($return > 0) Response::error('UUID生成失败！');
        $output = implode("\n", $output);
        preg_match('/UUID: (.*)/i', $output, $match);
        $uuid = $match[1];

        $message = serialize([
            'name' => System::app('name'),
            'uuid' => $uuid
        ]);
        $config = explode(':', config('socket-verify')['host']);
        $host = $config[0];
        $port = $config[1];

        // Response::note('Message To server :'.$message);
        // create socket
        $socket = socket_create(AF_INET, SOCK_STREAM, 0) or System::halt('Could not create socket');
        // connect to server
        $result = socket_connect($socket, $host, $port) or System::halt('Could not connect to server');
        // send string to server
        socket_write($socket, $message, strlen($message)) or System::halt('Could not send data to server');
        // get server response
        $result = (string)socket_read ($socket, 1024);
        if (strlen($result) == 0) System::halt('Could not read server response');
        socket_close($socket);

        if (!(int)$result) {
            Response::error('同类脚本已启动，无需执行');
            System::complete();
        }

        return $result;
    }
}