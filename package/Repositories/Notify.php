<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;
use Landers\Framework\Core\Redis;
use Landers\Substrate\Apps\Tasks\SendEmailNotify;
use Landers\Substrate\Apps\Tasks\SendSmsNotify;
use Landers\Substrate\Classes\Tpl;
use Ender\YunPianSms\SMS\YunPianSms;
use Landers\Substrate\Classes\EMail;
use Landers\Substrate\Apps\ThirdApis\SendCloud;

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
            $bool = self::send_email('phpmailer', [
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

    public static function client($content_key, $uid, array $data, array $ways = []) {
        if (self::isDoneToday($content_key, $uid)) {
            // Response::note('#tab今天（%s）已经发过邮件通知了', date('Y-m-d'));
            // return false;
        }
        $uinfo = User::get($uid, 'username, mobile, email');
        $data = array_merge($uinfo, $data);
        $notify_contents = Config::get('notify-content');
        $notify_contents = Arr::get($notify_contents, $content_key);
        $message = $notify_contents['message'];
        $email = $notify_contents['email'];
        $sms = $notify_contents['sms'];

        $ways = array_merge([
            'insite' => true,
            'email' => false,
            'sms' => false,
        ], $ways);

        // 发送站内消息
        $bool1 = true;
        if ($ways['insite'] && $message && $message['content']) {
            $message['content'] = Tpl::replace($message['content'], $data);
            $bool1 = Message::sendTo($uid, $message['title'], $message['content']);
            // Response::bool($bool1, '#tab站内消息通知%s！');
        }

        // 发送邮件
        $bool2 = true;
        if ($ways['email'] && $email && $email['content']) {
            if ($uinfo['email']) {
                $email['content'] = Tpl::replace($email['content'], $data);
                $to = ['name' => $uinfo['user_name'], 'email' => $uinfo['email']];
                $bool2 = self::send_email('sendcloud', [
                    'to'        => $to,
                    'content'   => $email['content'],
                    'subject'   => $email['title']
                ]);
                // Response::bool($bool2, '#tab客户邮件通知%s！');
            }
        }

        //发送短信
        $bool3 = true;
        if ($ways['sms'] && $sms) {
            $sms = Tpl::replace($sms, $data);
            $mobile = $uinfo['mobile'];
            $bool3 = Notify::send_sms($mobile, $sms);
            // Response::bool($bool3, '#tab客户短信通知%s！');
        }

        return $bool1 && $bool2 && $bool3;
    }

    /**
     * 发送短信
     * @param  [type]  $mobile   [description]
     * @param  [type]  $content  [description]
     * @param  boolean $is_queue [description]
     * @return [type]            [description]
     */
    public static function send_sms($mobile, $content, $is_queue = true) {
        $config = Config::get('notify');
        $sign = Arr::get($config, 'sms.sign');
        $content = $sign . $content;
        if ( $is_queue ) {
            $task = new SendSmsNotify($config, $mobile, $content);
            return Queue::singleton('notify')->push($task);
        } else {
            $apikey = Arr::get($config, 'sms.apikey');
            $yunpianSms = new YunPianSms($apikey);
            $ret = $yunpianSms->send($mobile, $content);
            return $ret['status'] == 200;
        }
    }

    private static $retry = [];
    private static function retry($uniq_key) {
        $t = &self::$retry[$uniq_key];
        if (is_null($t)) $t = 0;
        return ++$t;
    }

    /**
     * 发送邮件
     * @param  [type] $opts to, content, subject, is_queue
     * @return [type]       [description]
     */
    public static function send_email($driver = 'phpemail', array $opts = array(), &$retdat = NULL) {
        //读取配置
        $configs = Config::get('notify');
        $config = $configs['email'][$driver];

        //创建任务
        switch ($driver) {
            case 'phpmailer' :
                $mailer = new EMail($config, $opts);
                break;
            case 'sendcloud' :
                $mailer = new sendcloud($config, $opts);
                break;
        }

        $job = new SendEmailNotify($mailer);

        //入队
        try {
            $retdat = Queue::singleton('notify')->push($job);
            return true;
        } catch (\Exception $e) {
            $uniq_key = md5(serialize($opts));
            $retry = self::retry($uniq_key);
            $reties = $config['retries'];
            if ( $retry <= $reties) {
                Response::warn('入队失败，第%s次重试中...', $retry);
                sleep(1);
                self::send_email($driver, $optsm, $retdat);
            }
        }
    }
}