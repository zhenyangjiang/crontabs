<?php
use Landers\Framework\Core\StaticRepository;
use Landers\Substrate\Utils\Arr;

class Mitigation extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_mitigations';
    protected static $DAO;

    public static function attachs($mitigation) {
        if ($mitigation && is_array($mitigation)){
            $mitigation['ability_mbps'] = self::Gbps_to_Mbps($mitigation['ability']);
            $mitigation['ability_pps'] = self::Gbps_to_pps($mitigation['ability']);
        }
        return $mitigation;
    }

    /**
     * 取得IP的云盾配置
     * @param  String $ip IP
     * @return String $fields 字段列表
     * @return Mixed
     */
    public static function findByIp($ip, $fields = NULL) {
        $ret = self::find([
            'awhere' => ['ip' => $ip],
            'fields' => $fields
        ]);
        if ($ret && is_array($ret)){
            $ret = self::attachs($ret);
        }
        return $ret;
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
     * 取得指定状态的ips
     * @param  String $status
     * @return Array 被攻中的或被牵引中（攻击未结束）的ip集合
     */
    public static function getIpsByStatus($status) {
        $ret = parent::lists([
            'fields' => 'ip',
            'awhere' => ['status' => $status]
        ]);
        if ($ret) $ret = Arr::flat($ret);

        return $ret;
    }

    /**
     * 设定云盾状态
     * @param  String / Array   $ips 要修改的IP集合或单IP
     * @param  String $status
     * @param  Boolean $is_force 是否强制修改
     * @return Boolean 是否【更新成功且有记录被更新】
     **/
    public static function setStatus($ips, $status = NULL, $is_force = NULL) {
        $ips = (array)$ips;
        $status = (string)$status;
        $updata = ['status' => $status];
        $awhere = ['ip' => $ips];

        $statuses = [
            'ATTACK' => ['NORMAL'],
            'BLOCK' => ['ATTACK'],
            'NORMAL' => ['ATTACK', 'BLOCK']
        ];
        if (!$is_force) {
            $awhere['status'] = $statuses[$status];
        }

        if ( $status == 'BLOCK' ) {
            //确定牵引时长，并更新牵引过期时间
            $block_duration_hours = DataCenter::blockDuration(DataCenter::findByIp($ip));
            $updata['block_expire'] = strtotime("+$block_duration_hours hours");
        } else {
            $updata['block_expire'] = NULL;
        }

        return self::update($updata, $awhere);
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
            AND i.expire >
        " . time();
        return self::query($sql);
    }
}
Mitigation::init();
?>