<?php
use Landers\Framework\Core\StaticRepository;

class DataCenter extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_datacenter';
    protected static $DAO;

    public static function parse(Array $dc) {
        if ( is_string($dc['instance_config'] )) {
            //给此两字段json解码
            $keys = ['instance_config', 'block_duration'];
            foreach ($keys as $key) $dc[$key] = json_decode($dc[$key], true);

            //从价格规则中找出最大

            $instance_config = &$dc['instance_config'];
            $keys = ['month', 'hour'];
            foreach ($keys as $key) {
                $rules = $instance_config["mitigation-$key"];
                foreach ($rules as $Gbps => $price) {
                    $Mbps = Mitigation::Gbps_to_Mbps($Gbps);
                    $rules[$Mbps] = $price;
                    unset($rules[$Gbps]);
                };
                krsort($rules, SORT_NUMERIC);
                $dc["$key-max-mbps"] = key($rules);
                $dc["$key-max-pps"] = Mitigation::Mbps_to_pps($dc["$key-max-mbps"]);
                $instance_config["mitigation-$key"] = $rules;
            }
        }
        return $dc;
    }

    public static function listById($id) {
        $lists = parent::lists([
            'awhere' => ['id' => $id]
        ]);
        if ($lists) {
            foreach ($lists as &$item) {
                $item = self::parse($item);
            }
        }
        return $lists;
    }

    /**
     * 通过IP确定数据中心
     * @param  [type] $id     [description]
     * @param  [type] $fields [description]
     * @return [type]         [description]
     */
    public static function find($id, $fields = NULL){
        $ret = parent::find($id, $fields);
        if ($ret && is_array($ret)) {
            $ret = self::parse($ret);
        }
        return $ret;
    }

    /**
     * 取得IP所属数据中心
     * @param  string   $ip             IP
     * @return mixed
     */
    public static function findByIp($ip) {
        //由ip确定实例记录
        $instance = Instance::findByIp($ip);

        //由实例记录确数据中心
        return self::find($instance['datacenter_id']);
    }

    /**
     * 取得IP的计费
     * @param  array   $datacenter     数据中心数据
     * @return [type]                   description]
     */
    public static function priceRules($datacenter, $billing) {
        $price_rules = $datacenter['price_rules'];
        $ret = $price_rules["mitigation-$billing"];
        return $ret;
    }

    /**
     * 最低价格方案
     * @param  array   $datacenter     数据中心数据
     * @return [type]                   description]
     */
    public static function lowestPriceCase($datacenter, $billing) {
        $price_rules = $datacenter['price_rules'];
        $ret = $price_rules["mitigation-$billing"];
        ksort($ret);
        return [
            'ability'   => key($ret),
            'price'     => pos($ret)
        ];
    }
}
DataCenter::init();
?>
