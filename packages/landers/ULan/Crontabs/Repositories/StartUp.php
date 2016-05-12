<?php
use Landers\Framework\Core\Config;

class StartUp {
    public static function check() {
        $socket_config = Config::get('socket-verify');
        $tcp_server = sprintf('tcp://%s:%s', $socket_config['host'], $socket_config['port']);
        $fp = stream_socket_client($tcp_server , $errno, $errstr, 30);
        if (!$fp) Response::error('TCP 连接失败！');

        fwrite($fp, "**\r\n***\r\n****\r\r");
        while (!feof($fp)) {
            echo fgets($fp, 1024);
        }
        fclose($fp);
        echo 'exit';
    }
}