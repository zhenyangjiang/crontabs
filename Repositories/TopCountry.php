<?php
use Landers\Framework\Core\Repository;

class TopCountry extends Repository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'country_statistic';
    protected static $DAO;
}
TopCountry::init();
?>