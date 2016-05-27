<?php
use Landers\Framework\Core\Repository;

class InstanceDomain extends Repository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_instance_domains';
    protected static $DAO;
}
InstanceDomain::init();
?>