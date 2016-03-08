<?php
namespace Tasks;

use Landers\Interfaces\TaskInterface;
use Landers\Utils\Http;
use Landers\Framework\Core\Response;

class CollectUpload implements TaskInterface {
    private $pack = array();
    private static $apiurl = 'http://collecter.ulan.com/ddossource/collect';

    function __construct($pack) {
        foreach ($pack as &$item) {
            $item['bps'] = $item['bps0'];
            $item['pps'] = $item['pps0'];
            unset($item['bps0']);
            unset($item['bps1']);
            unset($item['pps0']);
            unset($item['pps1']);
        }; unset($item);
        $this->pack = array_values($pack);
    }

    public function execute(&$retmsg = NULL) {
        $content = Http::post(self::$apiurl, $this->pack);
        Response::note('服务器返回:%s', strip_tags($content));
        if ( trim($content) == 'true' ) {
            $retmsg = 'DDosInfo发往收集器成功';
            return true;
        } else {
            $retmsg = 'DDosInfo发往收集器失败';
            return false;
        }
    }
}