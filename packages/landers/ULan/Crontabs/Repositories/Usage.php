<?php
use Landers\Framework\Core\Repository;

class Usage extends Repository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'usage';
    protected static $DAO;
}
Usage::init();
?>