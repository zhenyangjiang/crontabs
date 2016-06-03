<?php
use Landers\Framework\Core\StaticRepository;

class DataCenter extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_datacenter';
    protected static $DAO;

    public static function parse(Array $dc) {
        if ( is_string($dc['price_rules'] )) {
            //给此两字段json解码
            $keys = ['price_rules', 'block_duration'];
            foreach ($keys as $key) $dc[$key] = json_decode($dc[$key], true);

            //从价格规则中找出最大
            $price_rules = &$dc['price_rules'];
            $keys = ['month', 'hour'];
            foreach ($keys as $key) {
                $rules = $price_rules["mitigation-$key"];
                foreach ($rules as $Gbps => $price) {
                    $Mbps = Mitigation::Gbps_to_Mbps($Gbps);
                    $rules[$Mbps] = $price;
                    unset($rules[$Gbps]);
                };
                krsort($rules, SORT_NUMERIC);
                $dc["$key-max-mbps"] = key($rules);
                $dc["$key-max-pps"] = Mitigation::Mbps_to_pps($dc["$key-max-mbps"]);
                $price_rules["mitigation-$key"] = $rules;
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
        $ret = parent::find($id, $field);
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
    public static function find_ip($ip) {
        //由ip确定实例记录
        if (!$instance = Instance::find_ip($ip)) return NULL;

        //由实例记录确数据中心
        if (!$datacenter = self::find($instance['datacenter_id'])) return NULL;

        return $datacenter;
    }

    /**
     * 取得IP的计费
     * @param  array   $datacenter     数据中心数据
     * @return [type]                   description]
     */
    public static function price_rules($datacenter, $billing) {
        $price_rules = $datacenter['price_rules'];
        $ret = $price_rules["mitigation-$billing"];
        return $ret;
    }

    /**
     * 最低价格方案
     * @param  array   $datacenter     数据中心数据
     * @return [type]                   description]
     */
    public static function lowest_price_case($datacenter, $billing) {
        $price_rules = $datacenter['price_rules'];
        $ret = $price_rules["mitigation-$billing"];
        ksort($ret);
        return [
            'ability'   => key($ret),
            'price'     => pos($ret)
        ];
    }

    /**
     * 取得IP牵引的牵引时长
     * @param  array   $datacenter     数据中心数据
     * @return [type]     [description]
     */
    public static function block_duration($datacenter) {
        //读取数据中心全部套餐的牵引时长
        $block_duration = $datacenter['block_duration'];

        if (!$block_duration) {
            $ret = 4; $msg = 'IP:%s所在的数据中心%s的牵引时长未定义，暂且返回值为：%s';
            $msg = sprintf($msg, $ip, $datacenter['name'], $ret);
            //Notify::developer('未定义牵引时长', $msg);
            return $ret;
        }

        //读取当前IP所购买的防护能力值
        $ability = Mitigation::find_ip($ip, 'ability');
        if ( !$ability ) {
            //此IP为免费防护，未找到云盾记录，采用最低护规格的所对应的值
            ksort($block_duration);
            $ability = key($block_duration);
        }

        //返回结果
        $ret = $block_duration[$ability];
        return $ret;
    }
}
DataCenter::init();
?>
