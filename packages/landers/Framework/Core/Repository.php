<?php
namespace Landers\Framework\Core;

use Landers\Substrate\Classes\DBModel;

/**
 * 模块基础类
 * @author Landers
 */
abstract class Repository {
    public static function init(){
        $_class_ = get_called_class();
        if (property_exists($_class_, 'connection')) {
            $connection = static::$connection;
        } else {
            $connection = 'default';
        }
        $db = System::db($connection);
        if ( ! property_exists($_class_, 'appkey') && !property_exists($_class_, 'datatable')) {
            exit($_class_.'的 appkey 和 datatable 参数不能同时为空！');
        }

        $dt_parter = property_exists($_class_, 'dt_parter') ? static::$dt_parter : NULL;

        if (property_exists($_class_, 'appkey') && !static::$DAO) {
            if ( !property_exists($_class_, 'DAO_class') ) {
                exit($_class_ .' 的 DAO_class 未定义！');
            }
            static::$DAO_class or static::$DAO_class = 'Landers\Framework\Core\ArchiveModel';
            static::$DAO = new static::$DAO_class(static::$appkey);
        }
        if (property_exists($_class_, 'datatable') && !static::$DAO) {
            static::$DAO = new DBModel($db, static::$datatable, '记录', $dt_parter);
        }
    }

    public static function __callStatic($method, $args) {
        // if (!static::$DAO) dp(static::$datatable);
        switch (count($args)) {
            case 0: return static::$DAO->$method();
            case 1: return static::$DAO->$method($args[0]);
            case 2: return static::$DAO->$method($args[0], $args[1]);
            case 3: return static::$DAO->$method($args[0], $args[1], $args[2]);
            case 4: return static::$DAO->$method($args[0], $args[1], $args[2], $args[3]);
            default: return call_user_func_array(array(static::$DAO, $method), $args);
        }
    }
}