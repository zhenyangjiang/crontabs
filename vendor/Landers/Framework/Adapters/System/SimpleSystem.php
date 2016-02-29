<?php
namespace Landers\Framework\Adapters\System;

use Landers\Classes\MySQL;
use Landers\Utils\Str;
use Landers\Framework\Core\Config;
use Landers\Interfaces\SystemClass;

class SimpleSystem extends SystemClass{
    /**
     * 系统初始化
     */
    public static function init(){

    }

    /**
     * 取得数据库配置
     * @param  string   连接名
     * @return array    配置信息
     */
    public static function db_config($connection = 'default') {
        $config = Config::get('database');
        if ( !$connection || $connection == 'default') {
            $connection = $config['default'];
        }
        return $config[$connection];
    }

    /**
     * 连接数据库
     * @param  string   连接名
     * @return object   连接对象
     */
    private static $dbs = array();
    public static function db($connection = 'default') {
        $db = &self::$dbs[$connection];
        if (!$db) {
            $config = self::db_config($connection);
            $db = new MySQL($config);
            if ($cfg['log-path']) {
                $db->set_log_path($cfg['log-path']);
            }
        }
        return $db;
    }
}