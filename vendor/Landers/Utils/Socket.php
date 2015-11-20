<?php
namespace Landers\Utils;

class Socket {
	private $host	= '172.16.8.10';
	private $port	= '9000';
	private $debug	= false;

	public function __construct($host, $port){
		$this->host = $host;
		$this->port = $port;
	}

	public function execute($str_pack, $is_conv_utf8 = false){
		$socket	= @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$socket) return array(10, 'Could not create  socket！');
		$conn = @socket_connect($socket, $this->host, $this->port);
		if (!$conn) return array(10, 'Could not connet server！');
		$ret = @socket_write($socket, $str_pack);
		if (!$ret) return array(10, 'Write failed！');
		$ret = ''; while ($buff = @socket_read($socket, 1024)) {
			$ret .= $buff . "\n";
		};
		if ($is_conv_utf8 && $ret)
			$ret = iconv('gbk', 'utf-8', $ret);
		socket_close($socket);
		if (this->debug) debug($ret, false);
		return $ret;
	}
?>