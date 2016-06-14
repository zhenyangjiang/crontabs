<?php
namespace Tasks;

use Landers\Substrate\Interfaces\TaskInterface;
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\Config;

class BlackholeAction implements TaskInterface {
    private $action;
    private $params;

    function __construct($action, $params) {
        $this->action = $action;
        $this->params = $params;
    }

    private static function apiurl($path) {
        return Config::get('hosts', 'api') . '/intranet/blackhole'. $path;
    }

    private function block(){
        $apiurl = self::apiurl('/block');
        $ret = \OAuthHttp::post($apiurl, $this->params);
        return \OAuthHttp::parse($ret);
    }

    private function unblock(){
        $apiurl = self::apiurl('/unblock');
        $ret = \OAuthHttp::post($apiurl, $this->params);
        return \OAuthHttp::parse($ret);
    }

    public function execute(&$retmsg = NULL) {
        if ( $this->action == 'block') {
            $bool = $this->block();
            $retmsg = sprintf('ip“%s”牵引%s', $this->params['ip'], $bool ? '成功' : '失败');
            return $bool;
        }

        if ( $this->action == 'unblock') {
            $bool = $this->unblock();
            $retmsg = sprintf('ip“%s”解除牵引%s', $this->params['ip'], $bool ? '成功' : '失败');
            return $bool;
        }
    }
}