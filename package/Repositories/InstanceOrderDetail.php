<?php
use Landers\Framework\Core\StaticRepository;

class InstanceOrderDetail extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_instance_order_detail';
    protected static $DAO;
}
InstanceOrderDetail::init();
?>