<?php
use Landers\Framework\Core\Repository;

class DDoSSource extends Repository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'ddossource';
    protected static $DAO;
}
DDoSSource::init();
?>