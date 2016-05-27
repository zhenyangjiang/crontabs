<?php
use Landers\Framework\Core\Repository;

class CCSource extends Repository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'ccsource';
    protected static $DAO;
}
CCSource::init();
?>