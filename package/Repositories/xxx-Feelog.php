<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\StaticRepository;

class Feelog extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_fee_logs';
    protected static $DAO;

    public static function create(array $data) {
        $default = [
            'occur_way' => '余额',
            'client_ip' => System::app('name'),
            'inout' => 'out',
            'terminal' => 'crontab',
        ];

        if ( $data['amount'] != 0 ) {
            $data['amount'] = - $data['amount'];
        }

        $time = &$data['time'];
        if (is_string($time)) $time = strtotime($time);
        $time or $time = time();

        $data = array_merge($default, $data);
        return parent::create($data);
    }
}
Feelog::init();