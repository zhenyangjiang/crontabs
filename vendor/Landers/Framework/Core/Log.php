<?php
namespace Landers\Framework\Core;

Class Log {
    private static $data = array();   //带有Linux终端格式，用于屏幕输出
    private static $text = array();   //不带格式纯文本，用于屏幕输出
    private static $savepath = 'logs';

    public static function __callStatic($type, $args) {
        $color = $type == 'error_notify' ? 'error' : $type;
        $items = self::parse($args);
        $datas = &self::$data;
        $texts = &self::$text;
        $L = 3;
        foreach ($items as $item) {
            $i = count($datas) + 1;
            $i = substr(str_repeat('0', $L).$i, -$L, $L);
            $prefix = '【'.$i.' => '.date('Y-m-d H:i:s').'】';
            $text = $prefix.$item;
            $data = $prefix.self::colorize($item, $color);
            echo ($data."\n");
            $datas[] = $data;
            $texts[] = $text;
            if ($type == 'error_notify') Notify::developer($text);
        }
    }

    /**
     * 去除格式
     * @param  [type] $val [description]
     * @return [type]      [description]
     */
    private static function strip_format($val) {
        if (is_string($val)) {
            $val = str_replace(array('[0m', '[37;40m', '[33;40m', '[33;40;1m', '[32;40;1m'), '', $val);
            $val = str_replace(array('#tab'), str_repeat('　', 2), $val);
        } else $val = self::strip_format($val);
        return $val;
    }

    /**
     * 导出
     * @param  boolean $is_clear [description]
     * @return [type]            [description]
     */
    public static function export($is_clear = false) {
        $html = implode("<br/>", self::$text);
        $html = self::strip_format($html);
        if ($is_clear) {
            self::$text = array();
            self::$data = array();
        }
        return $html;
    }

    /**
     * 保存到文件
     * @return [type] [description]
     */
    private static function save() {
        $file = System::app('path').'/'.self::$savepath.'/'.date('Ymd/H/i').'.log';
        $content = implode("\n", self::$data);
        return @file_put_contents($file, $content.PHP_EOL, true);
    }

    /**
     * 解析参数
     * @param  [type] $args [description]
     * @return [type]       [description]
     */
    private static function parse($args){
        $data = &self::$data;
        $args_count = count($args);
        $ret = array();
        if ( $args_count == 1) {
            $arr = (array)$args[0];
            foreach ($arr as $item) {
                switch ($item) {
                    case '#line' : $item = colorize(str_repeat('-', 70), 'gray'); break;
                    case '#dbline': $item = colorize(str_repeat('=', 70), 'gray'); break;
                    case '#blank': $item = ''; break;
                }
                $ret[] = $item;
            }
        } else if ( $args_count == 2) {
            $tpl = (string)$args[0];
            $dat = (array)$args[1];
            $ret[] = vsprintf($tpl, $dat);
        } else {
            $tpl = (string)$args[0];
            $dat = array_slice($args, 1);
            $ret[] = vsprintf($tpl, $dat);
        }
        return $ret;
    }

    /**
     * 颜色格式化
     * @param  [type] $text  [description]
     * @param  [type] $color [description]
     * @return [type]        [description]
     */
    private static function colorize($text, $color) {
        $a = explode('#tab', $text);
        foreach ($a as &$item) {
            $item = colorize($item, $color);
        }; unset($item);
        $text = implode('#tab', $a);
        $rep = array('#tab' => str_repeat(' ', 4));
        return str_replace(array_keys($rep), array_values($rep), $text);
    }

    /**
     * 输出实例详情
     * @param  array  $instance [description]
     * @return [type]           [description]
     */
    public static function instance_detail(array $instance){
        self::note('实例详情如下：');
        self::note(
            '#tabID=%s，名称=%s，标识=%s，自动续费=%s',
            $instance['id'],
            $instance['hostname'],
            $instance['hostkey'],
            $instance['is_auto_renew'] ? '开启' : colorize('未', 'yellow').'开启'
        );
        self::note('#blank');
    }

    /**
     * 输出用户详情
     * @param  array  $user [description]
     * @return [type]       [description]
     */
    public static function user_detail(array $user) {
        $user_name = $user['name'] or $user_name = $user['username'];
        self::note('实例所属用户详情如下：');
        self::note('#tabID=%s，名称=%s，余额=%s元', $user['id'], $user_name, $user['money']);
        self::note('#tab手机=%s，邮箱=%s', $user['mobile'], $user['email']);
        self::note('#blank');
    }
}
