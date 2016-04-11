<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;
use Landers\Framework\Core\Redis;
use Landers\Apps\Tasks\SendEmailNotify;
use Landers\Substrate\Classes\Tpl;

class Notify {
    private static $mail_suffix = '<br/><div style="color:#cccccc">本邮件由系统自动发送，请勿回复</div>';

    private static function isDoneToday($uid, $content_key) {
        $key = $content_key . $uid. date('Y-m-d');
        $md5_key = md5($key);
        if ( Redis::get($md5_key) ) {
            return true;
        } else {
            Redis::set($md5_key, $key);
            return false;
        }
    }

    public static function developer($title, $content = '', $context = NULL) {
        $is_sended = self::isDoneToday(md5($title), 'developer');
        $title_bak = $title;
        Response::note('#tab'.$title_bak.'，需电邮开发者');
        if (!$is_sended) {
            $developers = Config::get('developer'); $contents = [];
            $contents[] = Response::export();
            if ($content) $contents[] = $content;
            if ($context) {
                if ($context === true) $context = debug_backtrace();
                if (is_array($context)) $context = Arr::to_html($context);
                if ($context) $contents[] = '上下文数据包：<br/>'.$context;
            }
            $contents = implode('<hr/>', $contents);
            $title = System::app('name').($title ? '：'.$title : '');
            $bool = self::send_email([
                'tos'       => $developers,
                'subject'   => $title,
                'content'   => $contents.$mail_suffix
            ], $retdat);
            if ($bool) {
                $log_content = sprintf('已电邮开发者，队列ID：'.$retdat);
                $log_content = colorize($log_content, 'pink');
                Response::note("#tab$log_content");
            } else {
                $log_content = '通知开发者失败。错误：'.$retdat;
                Response::error("#tab$log_content");
            }
        } else {
            Response::note('#tab今天（%s）已经发过邮件通知了', date('Y-m-d'));
            $bool = true;
        }

        return $bool;
    }

    public static function client($content_key, $uid, array $data) {
        if (self::isDoneToday($content_key, $uid)) {
            Response::note('#tab今天（%s）已经发过邮件通知了', date('Y-m_d'));
            return false;
        }
        $uinfo = User::get($uid, 'realname, username, mobile, email');
        $data = array_merge($uinfo, $data);
        $notify_contents = Config::get('notify-content');
        $notify_contents = Arr::get($notify_contents, $content_key);
        $message = $notify_contents['message'];
        $email = $notify_contents['email'];

        // 发送站内消息
        if ($message && $message['content']) {
            $message['content'] = Tpl::replace($message['content'], $data);
            $ret = Message::sendTo($uid, $message['title'], $message['content']);
        }

        // 发送邮件
        $email['content'] = Tpl::replace($email['content'], $data);
        $to = ['name' => $uinfo['user_name'], 'email' => $uinfo['email']];
        $bool = self::send_email([
            'to'        => $to,
            'content'   => $email['content'],
            'subject'   => $email['title']
        ]);
        if (!$bool) {
            $error = '#tab客户邮件通知失败！';
            Response::warn($error);
        } else {
            Response::note('#tab通知成功');
        }
        return $bool;
    }

    /**
     * 发送邮件
     * @param  [type] $opts to, content, subject, is_queue
     * @return [type]       [description]
     */
    public static function send_email(array $opts = array(), &$retdat = NULL) {
        $to = Arr::get($opts, 'to');
        $tos = Arr::get($opts, 'tos', []);
        if ($to) $tos[] = $to;
        if (!$tos) {
            $retdat = '缺少收件人';
            return false;
        }
        foreach ($tos as &$to) {
            if (is_string($to)) $to = array('email' => $to, 'name' => '');
        }; unset($to);

        $opts['subject'] or $opts['subject'] = '【无标题邮件】';
        $opts['content'] or $opts['content'] = '【无内容邮件】';

        //读取配置
        $configs = Config::get('notify');
        $config = $configs['email'];

        //实例化对象
        $o = new \PHPMailer();
        $o->IsSMTP();                           // 启用SMTP
        $o->Host        = $config['host'];    // SMTP服务器
        $o->SMTPAuth    = true;                 // 开启SMTP认证
        $o->Username    = $config['username'];     // SMTP用户名
        $o->Password    = $config['password'];     // SMTP密码

        $o->From        = $config['from_email'];    //发件人地址
        $o->FromName    = $config['from_name'];     //发件人
        $o->WordWrap    = 50;                    //设置每行字符长度
        $o->IsHTML(true);                       // 是否HTML格式邮件
        $o->SetLanguage('ch');


        //发送邮件
        foreach ($tos as $to) {
            $o->AddAddress($to['email'], $to['name']);     //添加收件人
        }
        $o->Subject = $opts['subject'].' - '.ENV_system_name;        //邮件主题
        $o->Body    = str_replace(PHP_EOL, '<br/>', $opts['content']);        //邮件内容

        $opts['is_queue'] = Arr::get($opts, 'is_queue', true);
        if (!$opts['is_queue']) {
            if($o->Send()) {
                Response::note('实时邮件通知成功。');
                return true;
            } else {
                $retdat = ob_get_contents();
                $retdat or $retdat = $o->ErrorInfo.'！';
                Response::error('实时邮件通知失败：'.$retdat);
                return false;
            }
        } else {
            if ($retdat = $o->ErrorInfo) {
                return false;
            } else {
                $retdat = Queue::singleton('notify')->push(new SendEmailNotify($o));
                return true;
            }
        }
     }

    public static function send_sms(){

    }
}