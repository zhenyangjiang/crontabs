<?php
use Landers\Framework\Core\StaticRepository;

class ApplyIp extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_apply_ip';
    protected static $DAO;
}
ApplyIp::init();
?>