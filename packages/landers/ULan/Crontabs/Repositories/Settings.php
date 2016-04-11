<?php
use Landers\Framework\Core\Repository;

class Settings extends Repository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_settings';
    protected static $DAO;

    public static function get($key) {
        $ret = parent::find($key, 'value');
        if (is_numeric($ret)) $ret = (float)$ret;
        return $ret;
    }
}
Settings::init();
?>