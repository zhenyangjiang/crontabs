<?php
use Landers\Substrate\Utils\Arr;
use Landers\Substrate\Utils\Str;
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
use Services\OAuthClientHttp;

class Notify {
    public static function developer($title, $content = '', $context = NULL) {
        $is_sended = System::isDoneToday($title, 'developer');
        $title_bak = $title;
        Response::note($title.'，需电邮开发者');
        if (!$is_sended) {
            $developers = config('developer'); $contents = [];
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

    public static function user($uid, $event, $data) {
        if (!env('debug') && System::isDoneToday($event, $uid)) {
            Response::note('#tab今天（%s）已经通知过了(UID:%s)', date('Y-m-d'), $uid);
            return false;
        }

        Response::note('#tab对用户ID:%s 告警通知 %s ... : ', $uid, $event);
        $uinfo = User::find($uid, 'username, mobile, email');
        $data = array_merge($uinfo, $data);

        $contents = config('notify-contents.' . $event);
        foreach ($contents as $key => &$item) {
            $content = $item['content'];
            $content = is_array($content) ? implode('<br/>', $content) : $content;
            $item['content'] = Str::parse($content, $data);
        }; unset($item);

        //回显结果
        $arrResult = repository('alert')->sendTo($uid, $event, $contents);
        if ($arrResult) {
            foreach ($arrResult as $way => $val) {
                if (is_null($val)) continue;
                $way = strtoupper($way);
                Response::echoBool($val, "$way ");
            }
            return true;
        } else {
            Response::echoBool(false);
            return false;
        }
    }

    /**
     * 发送短信
     * @param  [type]  $mobile   [description]
     * @param  [type]  $content  [description]
     * @param  boolean $is_queue [description]
     * @return [type]            [description]
     */
    public static function sendSms($mobile, $content, $is_queue = true) {
        $config = config('notify');
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
        $configs = config('notify');
        $config = $configs['email'][$driver];

        if ( $opts['subject'] && strpos(ENV_system_name, $opts['subject']) ) {
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
        // try {
            $retdat = Queue::singleton('notify')->push($job);
            return true;
        // } catch (\Exception $e) {
        //     $uniq_key = md5(serialize($opts));
        //     $retry = self::retry($uniq_key);
        //     $reties = $config['retries'];
        //     if ( $retry <= $reties) {
        //         Response::warn('入队失败，第%s次重试中...', $retry);
        //         sleep(1);
        //         self::sendEmail($driver, $opts, $retdat);
        //     }
        // }
    }
}