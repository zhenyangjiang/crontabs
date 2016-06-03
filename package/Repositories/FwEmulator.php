<?php
use Landers\Framework\Core\StaticRepository;

class FwEmulator extends StaticRepository {
    protected static $connection = 'collecter';
    protected static $datatable  = 'fw_emulator';
    protected static $DAO;
}
FwEmulator::init();
?>