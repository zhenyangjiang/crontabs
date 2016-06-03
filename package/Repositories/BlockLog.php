<?php
use Landers\Framework\Core\StaticRepository;

class BlockLog extends StaticRepository {
    protected static $connection = 'blackhole';
    protected static $datatable  = 'ulan_block_logs';
    protected static $DAO;
}
BlockLog::init();
?>