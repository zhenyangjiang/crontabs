<?php
namespace Landers\Substrate\Apps\Tasks;

use Landers\Substrate\Interfaces\TaskInterface;
use PHPMailer;

class SendEmailNotify implements TaskInterface {
    /**
     * 构造方法
     */
    private $phpemailer;
    public function __construct(PHPMailer $phpemailer) {
        $this->phpemailer = $phpemailer;
    }

    /**
     * 执行任务
     * @return void
     */
    public function execute(&$retmsg = NULL) {
        // $retmsg = '虚拟邮件发送成功。';
        // return true;

        if( $this->phpemailer->Send()) {
            $retmsg = '邮件发送成功。';
            return true;
        } else {
            $error = ob_get_contents();
            $error or $error = $this->phpemailer->ErrorInfo;
            $retmsg = '邮件发送失败：'.$error;
            return false;
        }
    }
}