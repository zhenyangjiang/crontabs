<?php
use Landers\Framework\Core\Repository;

class Mitigation extends Repository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_mitigations';
    protected static $DAO;

    /**
     * 取得IP的云盾配置
     * @param  String $ip IP
     * @return String $fields 字段列表
     * @return Mixed
     */
    public static function find_ip($ip, $fields = NULL) {
        $ret = self::find([
            'awhere' => ['ip' => $ip],
            'fields' => $fields
        ]);
        if ($ret && is_array($ret)){
            $ret['ability_mbps'] = self::Gbps_to_Mbps($ret['ability']);
            $ret['ability_pps'] = self::Gbps_to_pps($ret['ability'] );
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
    public static function down_grade($instance) {
        //找出最低级别的云盾方案
        $datacenter = DataCenter::find($instance['datacenter']);
        $case = DataCenter::lowest_price_case($datacenter, 'month');
        $info = self::find_ip($instance['mainipaddress']);
        $case['ability'] = self::Mbps_to_Gbps($case['ability']);
        if (!$info) { //找不到云盾记录
            return self::create([
                'ip' => $instance['mainipaddress'],
                'ability' => $case['ability'],
                'price' => $case['price'],
                'alert_sets' => '[]',
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
}
Mitigation::init();
?>