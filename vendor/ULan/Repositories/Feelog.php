<?php
// namespace Ulan\Modules;

use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;

class Feelog extends Repository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_fee_logs';
    protected static $DAO;

    public static function create(array $data) {
        $default = [
            'occur_way' => '余额',
            'client_ip' => System::app('name'),
            'inout' => 'out',
        ];
        $time = &$data['time'];
        if (is_string($time)) $time = strtotime($time);

        $data = array_merge($default, $data);
        return parent::create($data);
    }

    /**
     * 指定时间段的攻击
     * @param  [type]  $begin [description]
     * @param  [type]  $end   [description]
     * @return boolean        [description]
     */
    public static function has_pay_hour($ip, $begin, $end) {
        return !!self::count([
            'instance_ip' => $ip,
            'typekey'  => 'pay_mitigation',
            "time between $begin and $end"
        ]);
    }
}
Feelog::init();