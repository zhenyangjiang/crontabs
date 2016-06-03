<?php
use Landers\Framework\Core\StaticRepository;

class Usage extends StaticRepository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'usage';
    protected static $DAO;
}
Usage::init();
?>