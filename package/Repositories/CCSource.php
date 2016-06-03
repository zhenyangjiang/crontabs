<?php
use Landers\Framework\Core\StaticRepository;

class CCSource extends StaticRepository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'ccsource';
    protected static $DAO;
}
CCSource::init();
?>