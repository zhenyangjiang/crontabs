<?php
namespace Landers\Framework\Adapters\System;

use Landers\Classes\MySQL;
use Landers\Utils\Str;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;
use Landers\Interfaces\SystemClass;

class LcliSystem extends SystemClass {
    private static $app = array();
    private static $params = array();

    private static $argv;
    private static $root;
    private static $current_command;

    /**
     * 系统初始化
     */
    public static function init(){
        //设置异常捕获
        set_exception_handler(function($e){
            $class = get_class($e);
            if (class_exists($class) && method_exists($class, 'handle')) {
                $class::handle($e);
            }
            $message = $e->getMessage().sprintf('位于 (%s : %s行)', $e->getFile(), $e->getLine());
            $code = $e->getCode();
            if ($code < 2000) {
                Response::note('异常捕获：%s', $message);
            } else {
                Response::warn('异常捕获：%s', $message);
            }
        });

        global $argv; self::$argv = &$argv;
        self::$current_command = 'php '.implode(' ', $argv);

        //系统根目录
        self::$root = $_SERVER['DOCUMENT_ROOT'];

        //取出应用参数
        if ( count(self::$argv) > 2) {
            $params = &self::$params;
            for($i = 2; $i < count(self::$argv); $i++) {
                $param = $argv[$i];
                if (substr($param, 0, 1) != '-') continue;
                $arr = explode(':', $param);
                $params[ltrim($arr[0], '-')] = $arr[1];
                unset(self::$argv[$i]);
            }
            self::$argv = array_values(self::$argv);
        }

        //应用目录
        if (!$name = self::$argv[1]) {
            Response::error('应用名称未指定！');
            self::halt();
        }
        self::$app['name'] = $name;

        $action = &self::$argv[2];
        $action or $action = 'default';
        $path = self::$root.'/apps/'.$name;
        self::$app['path'] = $path;

        //初始Config路径
        Config::init(array(
            $path.'/config/',
            self::$root.'/config/'
        ));

        //加载脚本
        $script_file = $path.'/actions/'.$action.'.php';
        if (is_file($script_file)) include($script_file);
        else Response::warn('应用文件'.$script_file.'不存在！');
    }

    /**
     * 取得应用名称 (即cli/apps/子目录名)
     * @return [type] [description]
     */
    public static function app($field = NULL) {
        return $field ? self::$app[$field] : self::$app;
    }

    /**
     * 取得运行时的根路径 (即cli所在的路径)
     * @return [type] [description]
     */
    public static function root() {
        return self::$root;
    }

    /**
     * 取得命令行参数
     * @param  int  $i 索引值
     * @return
     */
    public static function argv($i) {
        return self::$argv[$i];
    }


    /**
     * 取得命令运行时的参数值
     * @param  string   $key    [description]
     * @return mixed            [description]
     */
    public static function param($key) {
        return self::$params[$key];
    }

    /**
     * 设置应用的默认命令为应用的目录名称，从而shell命令中无需指定命令
     * @param String
     */
    /*
    private static function set_to_default_command(){
        //$cmd = pathinfo(dirname(__DIR__), PATHINFO_BASENAME);
        $cmd = 'default';
        $p = &$_SERVER['argv']; $p[] = '';
        for ($i=count($p)-1; $i>1; $i--) $p[$i] = $p[$i-1];
        $p[2] = self::$argv[1] = $cmd;
        return $cmd;
    }
    */


    /**
     * 连接数据库
     * @param  String 连接名
     * @return Object 连接对象
     */
    private static $dbs = array();
    public static function db($connection) {
        $db = &self::$dbs[$connection];
        if (!$db) {
            $connections = Config::get('database');
            $config = $connections[$connection];
            $db = new MySQL($config);
        }
        if ($cfg['log-path']) {
            $db->set_log_path($cfg['log-path']);
        }
        return $db;
    }

    public static function halt($msg = '任务出错，结束任务') {
        Response::error($msg); exit();
    }

    /**
     * 任务终止回调
     */
    public static function continues($sleep = false){
        $msg = '本轮任务结束';
        if ($sleep) $msg .= sprintf('，等待%s秒后继续...', $sleep);
        Response::note(array('#line', '#blank', "$msg\n\n"));
        if (!$sleep) exit; else sleep($sleep);
    }
}