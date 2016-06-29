<?php
use Landers\Framework\Core\StaticRepository;

use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;

class Alert extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_alerts';
    protected static $DAO;

    private static function execute(Array $alert, $data) {
        $event = $alert['event'];
        $uid = $alert['uid'];
        $ways = Arr::slice($alert, 'sms, email');
        Response::note('#tab对用户ID:%s 告警通知 %s...', $uid, $event);
        $bool = Notify::client($event, $uid, $data, $ways);
        Response::echoBool($bool);
        return $bool;
    }

    private static function initUser($uid) {
        $apiurl = Config::get('hosts', 'api').'/intranet/alert/init';
        $ret = \OAuthHttp::post($apiurl, ['uid' => $uid]);
        return \OAuthHttp::parse($ret);
    }

    private static function getAlerts($uids, $event) {
        $uids = (array)$uids;

        //读取用户告警设置
        $alerts = Alert::lists([
            'awhere' => ['uid' => $uids, 'event' => $event]
        ]);
        $alerts_uids = Arr::pick($alerts, 'uid');


        //还有哪些用户没有告警设置的进行初始化操作
        if ( $diff_uids = array_diff($uids, $alerts_uids) ) {
            foreach ($diff_uids as $uid) self::initUser($uid);

            //重新读取一次
            $alerts = Alert::lists([
                'awhere' => ['uid' => $uids, 'event' => $event]
            ]);
        }

        return $alerts;
    }

    /**
     * 告警通知：DDoS开始
     * @param  [type] $datas : 以uid为key的值为 ips数组  [ $uid1 => ['ip11', 'ip12'], $uid2 => ['ip1', 'ip2']]
     * @return [type]          [description]
     */
    public static function beginDDoS($datas) {
        $event = 'DDOS-BEGIN';

        //获取所有用户ids
        $uids = array_keys($datas);

        //读取所有用户关于$event的alert设置
        $alerts = self::getAlerts($uids, $event);

        //对用户发送通知
        foreach ($alerts as $alert) {
            $uid = $alert['uid'];
            $ips = array_values($datas[$uid]);
            $ips = implode('，', $ips);
            self::execute($alert, array('ips' => $ips));
            unset($datas[$uid]);
        }
    }

    /**
     * 告警通知：DDoS结束
     * @param  [type] $ip   [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function endDDoS($ip, Array $data) {
        $event = 'DDOS-END';

        $mitigation = Mitigation::find_ip($ip);
        $uid = $mitigation['uid'];

        //读取所有用户关于$event的alert设置
        $alerts = self::getAlerts($uid, $event);
        $alert = pos($alerts);

        $data = array_merge($data, ['ip' => $ip]);

        self::execute($alert, $data);
    }

    /**
     * 牵引告警通知
     * @param  [type] $ip   [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function ipBlock($ip, $data) {
        $event = 'BLOCKIP';

        $mitigation = Mitigation::find_ip($ip);
        $uid = $mitigation['uid'];

        //读取所有用户关于$event的alert设置
        $alerts = self::getAlerts($uid, $event);
        $alert = pos($alerts);

        $data = array_merge($data, ['ip' => $ip]);

        self::execute($alert, $data);
    }

    /**
     * 解除牵引告警通知
     * @param  array $ip    [description]
     * @return [type]       [description]
     */
    public static function ipUnblock(Array $ips) {
        $event = 'UNBLOCKIP';

        if ($ips) {
            $mitigations = Mitigation::lists([
                'awhere' => ['ip' => $ips]
            ]);
            $usersIp = Arr::groupBy($mitigations, 'uid');
            foreach ($usersIp as $uid => &$items) {
                $items = Arr::pick($items, 'ip');
                $items = implode(',', $items);
            }; unset($items);

            //读取所有用户关于$event的alert设置
            $uids = array_keys($usersIp);
            $alerts = self::getAlerts($uids, $event);

            foreach ($alerts as $alert) {
                $uid = $alert['uid'];
                $ips = $usersIp[$uid];
                self::execute($alert, array('ips' => $ips));
            }
        }
    }

}
Alert::init();
?>