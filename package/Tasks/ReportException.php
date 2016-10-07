<?php
namespace Tasks;

use Landers\Substrate\Interfaces\TaskInterface;
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;


class ReportException implements TaskInterface {
    private $postdata = array();
    private $apiurl = '%s/_api/collect';
    function __construct($message, $type, $extra_data = array()) {
        $host = config('hosts.collecter');
        $this->apiurl = sprintf($this->apiurl, $host);
        $this->postdata = array_merge(Array (
            'from' => 'Crontab.' . System::app('name'),
            'message' => $message,
            'context' => array(),
            'level' => '600',
            'level_name' => 'EMERGENCY',
            'extra' => array(),
            'type' => $type, // 0:开发者 1:运维
        ), $extra_data);
    }

    public function handle(&$retmsg = NULL) {
        $ret = Http::post($this->apiurl, $this->postdata);
        if ( trim($content) == '' ) {
            $retmsg = '异常上报成功';
            return true;
        } else {
            $retmsg = '异常上报失败';
            return false;
        }
    }
}