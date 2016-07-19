<?php
use Landers\Substrate\Utils\Arr;
use Landers\Substrate\Utils\Str;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;
use Landers\Substrate\Apps\Tasks\SendEmailNotify;
use Landers\Substrate\Apps\Tasks\SendSmsNotify;
use Landers\Substrate\Classes\Tpl;
use Ender\YunPianSms\SMS\YunPianSms;
use Landers\Substrate\Classes\EMail;
use Landers\Substrate\Apps\ThirdApis\SendCloud;

class Notify {
    public static function developer($title, $content = '', $context = NULL) {
        $is_sended = System::isDoneToday($title, 'developer');
        $title_bak = $title;
        Response::note($title.'，需电邮开发者');
        if (!$is_sended) {
            $developers = Config::get('developer'); $contents = [];
            $contents[] = Response::export();
            if ($content) $contents[] = $content;
            if ($context) {
                if ($context === true) $context = debug_backtrace();
                if (is_array($context)) $context = sprintf('<pre>%s</pre>', var_export($context, true));
                if ($context) $contents[] = '上下文数据包：<br/>'.$context;
            }
            $contents = implode('<hr/>', $contents);
            $title = System::app('name').($title ? '：'.$title : '');
            $bool = self::sendEmail('phpmailer', [
                'tos'       => $developers,
                'subject'   => $title,
                'content'   => $contents
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
            $text = sprintf('#tab今天（%s）已经发过邮件通知了', date('Y-m-d'));
            Response::note(colorize($text, 'yellow'));
            $bool = true;
        }

        return $bool;
    }

    public static function clientApi($uid, $event, $data) {
        if (System::isDoneToday($event, $uid)) {
            Response::note('#tab今天（%s）已经通知过了', date('Y-m-d'));
            return false;
        }

        Response::note('#tab对用户ID:%s 告警通知 %s ... : ', $uid, $event);
        $uinfo = User::get($uid, 'username, mobile, email');
        $data = array_merge($uinfo, $data);

        $host = Config::get('hosts', 'api');
        $apiurl = $host . '/intranet/alert/send';
        $contents = Config::get('notify-contents', $event);
        foreach ($contents as $key => &$item) {
            $content = $item['content'];
            $content = is_array($content) ? implode('<br/>', $content) : $content;
            $item['content'] = Str::parse($content, $data);
        }; unset($item);

        $result = \OAuthHttp::post($apiurl, [
            'uid' => $uid,
            'event' => $event,
            'msg' => $contents,
        ]);
        $result = \OAuthHttp::parse($result);

        if ($bool = $result->success) {
            foreach ($result->data as $way => $val) {
                if (is_null($val)) continue;
                $way = strtoupper($way);
                Response::echoBool($val, "$way ");
            }
        } else {
            Response::echoBool(false);
        }
        return $bool;
    }

    /**
     * 发送短信
     * @param  [type]  $mobile   [description]
     * @param  [type]  $content  [description]
     * @param  boolean $is_queue [description]
     * @return [type]            [description]
     */
    public static function sendSms($mobile, $content, $is_queue = true) {
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
    public static function sendEmail($driver = 'phpemail', array $opts = array(), &$retdat = NULL) {
        //读取配置
        $configs = Config::get('notify');
        $config = $configs['email'][$driver];

        if ( $opts['subject'] ) {
            $opts['subject'] .= ' - ' . ENV_system_name;
        }

        //创建任务
        switch ($driver) {
            case 'phpmailer' :
                $mailer = new EMail($config, $opts);
                break;
            case 'sendcloud' :
                $mailer = new SendCloud($config, $opts);
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
                self::sendEmail($driver, $optsm, $retdat);
            }
        }
    }

    // public static function client($content_key, $uid, array $data, array $ways = []) {
    //     if (System::isDoneToday($content_key, $uid)) {
    //         // Response::note('#tab今天（%s）已经发过邮件通知了', date('Y-m-d'));
    //         // return false;
    //     }
    //     $uinfo = User::get($uid, 'username, mobile, email');
    //     $data = array_merge($uinfo, $data);
    //     $notify_contents = Config::get('notify-content');
    //     $notify_contents = Arr::get($notify_contents, $content_key);
    //     $email = $notify_contents['email'];
    //     $sms = $notify_contents['sms'];
    //     $inner = $notify_contents['inner'] or $inner = $email;

    //     $ways = array_merge([
    //         'inner' => true,
    //         'email' => false,
    //         'sms' => false,
    //     ], $ways);

    //     // 发送站内消息
    //     $bool1 = true;
    //     if ($ways['inner'] && $inner && $inner['content']) {
    //         $inner['content'] = Tpl::replace($inner['content'], $data);
    //         $bool1 = Message::sendTo($uid, $inner['title'], $inner['content']);
    //         // Response::bool($bool1, '#tab站内消息通知%s！');
    //     }

    //     // 发送邮件
    //     $bool2 = true;
    //     if ($ways['email'] && $email && $email['content']) {
    //         if ($uinfo['email']) {
    //             $email['content'] = Tpl::replace($email['content'], $data);
    //             $to = ['name' => $uinfo['user_name'], 'email' => $uinfo['email']];
    //             $bool2 = self::sendEmail('phpmailer', [ //phpmailer | sendcloud
    //                 'to'        => $to,
    //                 'content'   => $email['content'],
    //                 'subject'   => $email['title']
    //             ]);
    //             // Response::bool($bool2, '#tab客户邮件通知%s！');
    //         }
    //     }

    //     //发送短信
    //     $bool3 = true;
    //     if ($ways['sms'] && $sms) {
    //         $sms = Tpl::replace($sms, $data);
    //         $mobile = $uinfo['mobile'];
    //         $bool3 = self::sendSms($mobile, $sms);
    //         // Response::bool($bool3, '#tab客户短信通知%s！');
    //     }

    //     return $bool1 && $bool2 && $bool3;
    // }
}