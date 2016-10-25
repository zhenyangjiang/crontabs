<?php
use Landers\Framework\Core\StaticRepository;
use Landers\Substrate\Utils\Arr;

class Mitigation extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_mitigations';
    protected static $DAO;

    private static $repo;
    public static function init() {
        self::$repo = repository('mitigation');
        parent::init();
    }

    public static function attachs($mitigation) {
        if ($mitigation && is_array($mitigation)){
            $mitigation['ability_mbps'] = self::Gbps_to_Mbps($mitigation['ability']);
            $mitigation['ability_pps'] = self::Gbps_to_pps($mitigation['ability']);
        }
        return $mitigation;
    }

    public static function billingText($billing_key) {
        $a = [
            'month' => '包年包月',
            'hour' => '按需计费'
        ];
        return $a[$billing_key];
    }

    public static function listByIps($ips) {
        $ipbases = IPBase::lists([
            'awhere' => ['ip' => $ips]
        ]);
        if (!$ipbases) return [];

        return IPBase::getMitigations($ipbases);
    }

    /**
     * 取得IP的云盾配置
     * @param  String $ip IP
     * @return String $fields 字段列表
     * @return Mixed
     */
    public static function findByIp($ip, $fields = NULL) {
        $ipbase = IPBase::findByIP($ip);
        if (!$ipbase) throw new \Exception(sprintf('IP库中不存在%s'));

        $mitigation = parent::find($ipbase['mit_id'], $fields);
        if (!$mitigation) throw new \Exception(sprintf('未找到ID：%s的云盾记录', $ipbase['mit_id']) );

        return $mitigation;
    }


    public static function Mbps_to_Gbps($Mbps) {
        if (is_array($Mbps)) {
            foreach ($Mbps as &$item) $item = self::Mbps_to_Gbps($item);
            unset($item); return $Mbps;
        } else return (float)$Mbps / 1000;
    }

    public static function Gbps_to_Mbps($Gbps) {
        if (is_array($Gbps)) {
            foreach ($Gbps as &$item) $item = self::Gbps_to_Mbps($item);
            unset($item); return $Gbps;
        } else return (float)$Gbps * 1000;
    }


    public static function Gbps_to_pps($Gbps) {
        if (is_array($Gbps)) {
            foreach ($Gbps as &$item) $item = self::Gbps_to_pps($item);
            unset($item); return $Gbps;
        } else return (float)$Gbps * 1000 * 1000;
    }

    public static function Mbps_to_pps($Mbps) {
        if (is_array($Mbps)) {
            foreach ($Mbps as &$item) $item = self::Mbps_to_pps($item);
            unset($item); return $Mbps;
        } else return (float)$Mbps * 1000;
    }

    /**
     * 给实例的包年包月云盾强制降级（降为所属数据中心的最低防护力）
     * @param  [type] $instance [description]
     * @return [type]           [description]
     */
    public static function downgrade($instance) {
        //找出最低级别的云盾方案
        $datacenter = DataCenter::find($instance['datacenter']);
        $case = DataCenter::lowestPriceCase($datacenter, 'month');
        $info = self::findByIp($instance['mainipaddress']);
        $case['ability'] = self::Mbps_to_Gbps($case['ability']);
        if (!$info) { //找不到云盾记录
            return self::create([
                'ip' => $instance['mainipaddress'],
                'ability' => $case['ability'],
                'price' => $case['price'],
                'fw_sets' => '{}'
            ]);
        } else {
            if ( $case['ability'] != $info['ability']) {
                return self::update([
                    'ability' => $case['ability'],
                    'price' => $case['price']
                ], [
                    'id' => $info['id']
                ]);
            } else {
                return true;
            }
        }
    }



    /**
     * IP对应的服务状态是否正常
     * @return [type] [description]
     */
    public static function checkServiceStatus($ip) {
        return self::$repo->checkServiceStatusByIp($ip);
    }

    /**
     * 是否处于试用期
     * @param  [type]  $mitigation [description]
     * @return boolean             [description]
     */
    public static function isTrial($mitigation) {
        return $mitigation['trial_expire'] > time();
    }

    /**
     * 牵引云盾中所有IP
     * @param  [type] &$mitWithDDoSInfos [description]
     * @param  [type] $reason            [description]
     * @param  [type] $onBlockSuccess    [description]
     * @return [type]                    [description]
     */
    public static function block(&$mitWithDDoSInfos, $reason, $onBlockSuccess) {
        $mit = &$mitWithDDoSInfos;
        foreach ($mit['ddosinfos'] as $item) {
            $dest_ip = $item['dest'];
            if (IPBase::block($dest_ip, $item['mbps'], 'force')) {
                $onBlockSuccess && $onBlockSuccess($item);
                Alert::ipBlock($dest_ip, compact('reason'));
            }
        }
    }

    /**
     * 根据所给的ip列表过滤出免费防护的ip
     * @param  Array  $ips [description]
     * @return [type]      [description]
     */
    // 暂用不上
    // public static function filteFree(Array $ips) {
    //     $lists = parent::lists([
    //         'fields' => 'ip',
    //         'awhere' => [
    //             'ip' => $ips,
    //             'price' => 0,
    //             'billing' => 'month'
    //         ]
    //     ]);
    //     if ($lists) {
    //         $lists = Arr::pick($lists, 'ip');
    //     }
    //     return $lists;
    // }


    /**
     * 取得按需计费的云盾
     * @return [type] [description]
     */
    public static function hourMits()
    {
        $sql = "
            SELECT
                m.uid,
                m.ip,
                m.ability,
                d.price_rules
            FROM
                ulan_mitigations AS m
            LEFT JOIN ulan_datacenter AS d ON d.id = m.datacenter_id
            LEFT JOIN ulan_instances AS i ON i.mainipaddress = m.ip
            WHERE
                billing = 'hour'
            AND i.expire > " . time();
        return self::query($sql);
    }
}
Mitigation::init();
?>