<?php
// namespace Ulan\Modules;

use Landers\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Log;

class ResourceUsage extends Repository {
    protected static $connection = 'resource-usage';
    protected static $datatable = 'main';
    protected static $DAO;

    public static function test(){
        dp(self::$DAO);
    }
}
ResourceUsage::init();
?>