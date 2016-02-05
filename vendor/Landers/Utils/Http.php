<?php
namespace Landers\Utils;

class Http {
    private static $statuses = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    /**
     * 输出http错误头
     * @param  [type] $err [description]
     * @return [type]      [description]
     */
    public static function status($status){
        $status_text = self::$statuses[$status];
        $description = "$status $status_text";
        header("HTTP/1.1 $description");
        header("Status: $description");
        switch (true) {
            case $status >= 400 && $status < 599 :
                return 'HTTP '.$description.'错误';
                break;
        }
    }

    private static function conv_array_format($data, $str_split = NULL){
        if (!is_array($data)) return $data;
        $a = array(); foreach($data as $k => $v){
            $a[] = $k.'='.$v;
        };
        return $str_split ? implode($str_split, $a) : $a;
    }

    public static function parse($content) {
        $ret = array('status' => '', 'body' => '', 'cookie' => array());
        $arr = explode(PHP_EOL, $content);
        foreach ($arr as $i => $str) {
            if ( ord($str) == 13 ) break;
        }
        $header = array_slice($arr, 0, $i);
        $body = array_slice($arr, $i+1);
        array_pop($body);
        $ret['body'] = implode(PHP_EOL, $body);

        foreach (self::$statuses as $code => $status) {
            if (preg_match('/'.$code.'/', $header[0])) {
                $ret['status'] = $status;
            }
        }
        foreach($header as $line) {
            if (preg_match('/Set-Cookie: (.*)=(.*)/', $line, $matches)) {
                $ret['cookie'][$matches[1]] = $matches[2];
            }
        }
        return $ret;
    }

    public static function post($url, $data, $opts = array()) {
		$opts = array_merge(array(
			'build'			=> true,
			'header'		=> array(),
            'respone-header'=> 0,
			'timeout'		=> 30,
			'referer'		=> '',
            'cookie'        => '',
			'cookie_file'	=> '',
			'nosae'			=> false,
            'scheme'        => 'http',
		), (array)$opts);
        if (is_array($data)) $data = http_build_query($data);

        $ch = curl_init(); $t = parse_url($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $t['scheme'] == 'https' ? 1 : 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if ($opts['cookie_file']) curl_setopt($ch, CURLOPT_COOKIEJAR, $opts['cookie_file']);
        if ($opts['cookie']) {
            $opts['cookie'] = self::conv_array_format($opts['cookie'], ';');
            curl_setopt($ch, CURLOPT_COOKIE, $opts['cookie']);
        };
        if ($opts['referer']) curl_setopt($ch, CURLOPT_REFERER, $opts['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
        if ($opts['header']) {
            $opts['header'] = self::conv_array_format($opts['header']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['header']);
        }
        curl_setopt($ch, CURLOPT_HEADER, $opts['respone-header']);  // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);                // 获取的信息以文件流的形式返回
        if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }
        // curl_setopt($ch, CURLOPT_PORT, 8000);
        //cur_getinfo();
        $ret = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch); // 关闭CURL会话

		return $ret;
	}

	public static function get($url, $opts = array()){
		$opts = array_merge(array(
			'header'	=> array(),
            'respone-header' => 0,
			'timeout'	=> 30,
			'referer'	=> '',
            'cookie'    => '',
			'nosae'		=> false,
            'scheme'    => 'http',
		), (array)$opts);
        $ch = curl_init(); $t = parse_url($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $t['scheme'] == 'https'); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
        if ($opts['cookie']) {
            $opts['cookie'] = self::conv_array_format($opts['cookie'], ';');
            curl_setopt($ch, CURLOPT_COOKIE, $opts['cookie']);
        };
        if ($opts['referer']) curl_setopt($ch, CURLOPT_REFERER, $opts['referer']);
        if ($opts['header']) {
            $opts['header'] = self::conv_array_format($opts['header']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['header']);
        }
        curl_setopt($ch, CURLOPT_HEADER, $opts['respone-header']);                // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $ret = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch); // 关闭CURL会话
		return $ret;
	}
}
?>