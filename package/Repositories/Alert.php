<?php
use Landers\Framework\Core\StaticRepository;

use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Response;

class Alert extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_alerts';
    protected static $DAO;

    private static function execute(Array $alert, $data) {
        $event = $alert['event'];
        $uid = $alert['uid'];
        $ways = Arr::slice($alert, 'sms, email');
        Response::note('#tab正在执行告警通知...');
        $bool = Notify::client($event, $uid, $data, $ways);
        Response::echoBool($bool);
        return $bool;
    }

    /**
     * 告警通知：DDoS开始
     * @param  [type] $alertsIp [description]
     * @return [type]          [description]
     */
    public static function beginDDoS($alertsIp) {
        $event = 'DDOS-BEGIN';

        $uids = array_keys($alertsIp);

        //读取用户告警设置
        Alert::debug();
        $alerts = Alert::lists([
            'awhere' => ['uid' => $uids, 'event' => $event]
        ]);

        //对用户发送通知
        foreach ($alerts as $alert) {
            $uid = $alert['uid'];
            $ips = array_values($alertsIp[$uid]);
            $ips = implode('，', $ips);
            self::execute($alert, array('ips' => $ips));
        }
    }

}
Alert::init();
?>