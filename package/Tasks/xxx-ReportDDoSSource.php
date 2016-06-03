<?php
namespace Tasks;

use Landers\Substrate\Interfaces\TaskInterface;
use Landers\Substrate\Utils\Http;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;

class ReportDDoSSource implements TaskInterface {
    private $pack = array();
    private $apiurl = '%s/ddossource/collect';

    function __construct($pack) {
        $hosts = Config::get('hosts', 'collecter');
        $this->apiurl = sprintf($this->apiurl, $hosts);
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
        $content = Http::post($this->apiurl, $this->pack);
        Response::note('服务器返回:%s', strip_tags($content));
        if ( trim($content) == 'true' ) {
            $retmsg = 'DDosSource发往收集器成功';
            return true;
        } else {
            $retmsg = 'DDosSource发往收集器失败';
            return false;
        }
    }
}