<?php
// namespace Ulan\Modules;

// use Ulan\Classes\module;
// use Landers\Classes\utils;
use Landers\Framework\Core\Repository;

class InstanceOrderDetail extends Repository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_instance_order_detail';
    protected static $DAO;
}
InstanceOrderDetail::init();
?>