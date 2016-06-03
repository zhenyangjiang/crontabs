<?php
use Landers\Framework\Core\StaticRepository;

class DDoSSource extends StaticRepository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'ddossource';
    protected static $DAO;
}
DDoSSource::init();
?>