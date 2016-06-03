<?php
use Landers\Framework\Core\StaticRepository;

class InstanceDomain extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_instance_domains';
    protected static $DAO;
}
InstanceDomain::init();
?>