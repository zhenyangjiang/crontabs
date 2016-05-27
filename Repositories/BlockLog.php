<?php
use Landers\Framework\Core\Repository;

class BlockLog extends Repository {
    protected static $connection = 'blackhole';
    protected static $datatable  = 'ulan_block_logs';
    protected static $DAO;
}
BlockLog::init();
?>