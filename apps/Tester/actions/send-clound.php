<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试邮件发送...');

function send_mail() {
        $API_USER = 'SeedMssP_test_7atBep';
        $API_KEY = 'IgVG8uKcceFjD22i';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'http://api.sendcloud.net/apiv2/mail/send');

        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                                'apiUser' => $API_USER, # 使用api_user和api_key进行验证
                                'apiKey' => $API_KEY,
                                'from' => 'luhaixing@163.com', # 发信人，用正确邮件地址替代
                                'fromName' => 'LUHAIXING',
                                'to' => 'i@x1982.com', # 收件人地址，用正确邮件地址替代，多个地址用';'分隔
                                'subject' => 'Sendcloud php webapi example',
                                'html' => "你太棒了！你已成功的从SendCloud发送了一封测试邮件，接下来快登录前台去完善账户信息吧！",
                                // 'files' => '@./test.txt'
                                )
        ); #附件名称

        $result = curl_exec($ch);

        if($result === false) {
                echo curl_error($ch);
        }
        curl_close($ch);
        return $result;
}

echo send_mail();

Response::note('#line');
System::complete();