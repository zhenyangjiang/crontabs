<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
include 'inc-headline.php';

Response::note('测试邮件发送...');
$bool = Notify::sendEmail('sendcloud', [
    'tos'       => [
        'luhaixing' => [
            'name'   => 'LANDEDS',
            'email'  => 'luhaixing@qq.com'
        ]
    ],
    'subject'   => '这是邮件标题',
    'content'   => '<meta charset="UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
<div class="email" style="width:700px;padding-bottom: 50px;">
<div class="hd" style="height:40px;padding-left: 30px;">&nbsp;</div>

<div class="bd" style="padding:10px 55px 0 100px;">
<div style="margin-top: 25px;font:bold 16px/40px arial;">请点击以下按钮 <span style="color: #cccccc">(有效期为1小时)：</span></div>

<div style="font:bold 18px/36px arial; width: 170px;height: 36px;background-color:#00BFF3;text-align: center;margin:25px 0 0 140px; "><a href="http://console.ulan.com" style="color: #fff;text-decoration:none;" target="_blank">继续下一步</a></div>

<div style="color: #ccc;margin-top: 40px;font:bold 16px/26px arial;">如果亲看不到上方的按钮<br />
可点击下面的链接以完成注册或复制下面的链接到浏览器地址栏中完成更改：<br />
<a href="%url%" target="_blank">http://console.ulan.com</a></div>
</div>
</div>'
], $retdat);
Response::bool($bool, '邮件发送任务入队%s');
Response::note('#line');
System::complete();