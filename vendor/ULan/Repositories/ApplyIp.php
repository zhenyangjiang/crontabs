<?php
// namespace Ulan\Modules;

// use Ulan\Classes\module;
// use Landers\Classes\utils;
use Landers\Framework\Core\Repository;

class ApplyIp extends Repository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_apply_ip';
    protected static $DAO;
}
ApplyIp::init();
?>