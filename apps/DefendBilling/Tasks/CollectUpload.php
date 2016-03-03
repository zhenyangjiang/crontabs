<?php
namespace Apps\DefendBilling\Tasks;

use Landers\Interfaces\TaskInterface;
use Landers\Utils\Http;

class CollectUpload implements TaskInterface {
    private $data = array();
    private static $apiurl = 'http://collecter.ulan.com/ddossource/collect';

    function __construct($data) {
        $this->data = array_values($data);
    }

    public function execute(&$retmsg = NULL) {
        $content = Http::post(self::$apiurl, $this->data);
        if ( trim($content) == 'true' ) {
            $retmsg = 'DDosInfo发往收集器成功';
            return true;
        } else {
            $retmsg = 'DDosInfo发往收集器失败';
            return false;
        }
    }
}