<?php
namespace Landers\Substrate\Apps\Tasks;

use Landers\Substrate\Interfaces\TaskInterface;
use Ender\YunPianSms\SMS\YunPianSms;
use Landers\Substrate\Utils\Arr;

class SendSmsNotify implements TaskInterface {
    private $config, $mobile, $content;
    /**
     * 构造方法
     */
    public function __construct($config, $mobile, $content) {
        $this->config = $config;
        $this->mobile = $mobile;
        $this->content = $content;
    }

    /**
     * 执行任务
     * @return void
     */
    public function execute(&$retmsg = NULL) {
        // $retmsg = '虚拟短信发送成功。';
        // return true;

        $apikey = Arr::get($this->config, 'sms.apikey');
        $sms = new YunPianSms($apikey);
        $ret = $sms->sendMsg($this->mobile, $this->content);
        if( $ret['status'] == 200 && $ret['data']['code'] == 0 ) {
            $retmsg = '短信发送成功。';
            return true;
        } else {
            $retmsg = '短信发送失败：'.$ret['data']['msg'];
            return false;
        }
    }
}