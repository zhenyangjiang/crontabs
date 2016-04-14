<?php
use Landers\Framework\Core\Repository;

class FwEmulator extends Repository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'fw_emulator';
    protected static $DAO;
}
FwEmulator::init();
?>