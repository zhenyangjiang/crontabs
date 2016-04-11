<?php
use Landers\Framework\Core\Repository;

class FirewallRule extends Repository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_firewall_rules';
    protected static $DAO;
}
FirewallRule::init();
?>