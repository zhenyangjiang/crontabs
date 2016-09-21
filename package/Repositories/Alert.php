<?php
use Landers\Framework\Core\StaticRepository;

use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Config;
use Services\OAuthClientHttp;

class Alert extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_alerts';
    protected static $DAO;

    private static $repoAlert;
    public static function init() {
        self::$repoAlert = repository('alert');
        parent::init();
    }

    private static function ip2userip($ips) {
        $mitigations = Mitigation::lists([
            'awhere' => ['ip' => $ips]
        ]);
        $usersIp = Arr::groupBy($mitigations, 'uid');
        foreach ($usersIp as $uid => &$items) {
            $items = Arr::pick($items, 'ip');
            $items = implode(',', $items);
        }; unset($items);

        return $usersIp;
    }

    /**
     * 告警通知：DDoS开始
     * @param  [type] $datas : 以uid为key的值为 ips数组  [ $uid1 => ['ip11', 'ip12'], $uid2 => ['ip1', 'ip2']]
     * @return [type]          [description]
     */
    public static function beginDDoS(array $ips) {
        $event = 'DDOS-BEGIN';

        if ($ips) {
            Response::note('执行DDoS开始告警通知：');
            $usersIp = self::ip2userip($ips);

            foreach ($usersIp as $uid => $ips) {
                if (is_array($ips)) $ips = implode(',', $ips);
                Notify::user($uid, $event, ['ips' => $ips]);
            }
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

        Response::note('执行攻击结束告警通知：');

        $mitigation = Mitigation::findByIp($ip);
        $uid = $mitigation['uid'];

        Notify::user($uid, $event, ['ip' => $ip]);
    }

    /**
     * 牵引告警通知
     * @param  [type] $ip   [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function ipBlock($ip, $data) {
        $event = 'IP-BLOCKED';

        Response::note('执行牵引告警通知：');

        $mitigation = Mitigation::findByIp($ip);
        $uid = $mitigation['uid'];

        $data = array_merge($data, ['ip' => $ip]);
        Notify::user($uid, $event, $data);
    }

    /**
     * 解除牵引告警通知
     * @param  array $ip    [description]
     * @return [type]       [description]
     */
    public static function ipUnblock(Array $ips) {
        $event = 'IP-UNBLOCK';

        if ($ips) {
            Response::note('执行解牵引告警通知：');

            $usersIp = self::ip2userip($ips);
            foreach ($usersIp as $uid => $ips) {
                if (is_array($ips)) $ips = implode(',', $ips);
                Notify::user($uid, $event, ['ips' => $ips]);
            }
        }
    }

}
Alert::init();
?>