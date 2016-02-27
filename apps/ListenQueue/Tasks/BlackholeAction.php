<?php
namespace Apps\ListenQueue\Tasks;

use Landers\Interfaces\TaskInterface;
use Landers\Utils\Http;

class BlackholeAction implements TaskInterface {
    private $action;
    private $params;
    private static $apiurl = 'http://api.ulan.com/blackhole/action';

    function __construct($action, $params) {
        $this->action = $action;
        $this->params = $params;
    }

    public function block(){
        $ret = Http::post(self::$apiurl.'/block', $this->params);
        return strtolower(trim($ret)) === 'true';
    }

    public function unblock(){
        $ret = Http::post(self::$apiurl.'/unblock', $this->params);
        return strtolower(trim($ret)) === 'true';
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