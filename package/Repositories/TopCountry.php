<?php
use Landers\Framework\Core\StaticRepository;

class TopCountry extends StaticRepository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'country_statistic';
    protected static $DAO;
}
TopCountry::init();
?>