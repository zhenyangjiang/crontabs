<?php
use Landers\Framework\Core\StaticRepository;

use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Arr;

class IPBase extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_ip_bases';
    protected static $DAO;

    private static $repo;
    public static function init() {
        self::$repo = repository('iPBase');
        parent::init();
    }

    public static function findByIp($ip) {
        return parent::find([
            'awhere' => ['ip' => $ip]
        ]);
    }

    /**
     * [getMitigations description]
     * @return [type] [description]
     */
    public static function getMitigations($ipbases) {
        $mit_ids = Arr::pick($ipbases, 'mit_id');
        $mit_ids = array_unique($mit_ids);
        return Mitigation::lists([
            'awhere' => ['id' => $mit_ids],
            'askey' => 'id'
        ]);
    }

    /**
     * 给攻击数据分组，并给每个被攻ip附上mitigation数据
     * @param  [type] $data        [description]
     * @param  [type] $mitigations [description]
     * @return [type]              [description]
     */
    public static function groupBy($data) {
        $ret = []; $dc_ids = [];
        $all_ip = array_keys($data);
        $list = self::lists([
            'awhere' => ['ip' => $all_ip],
            'askey' => 'ip'
        ]);

        //给存在的ip附上datacenter_id和mit_id
        if ($list) {
            // 组装mit_id与datacenter_id有对应关系表
            $relates = [];
            $mit_ids = Arr::pick($list, 'mit_id');
            $mit_ids = array_unique($mit_ids);
            $mitigations = Mitigation::lists([
                'awhere' => ['id' => $mit_ids],
            ]);
            foreach ($mitigations as $item) {
                $relates[$item['id']] = $item['datacenter_id'];
            }

            // 将datacenter_id和mit_id附到data上去
            foreach($list as $ip => $item) {
                $mit_id = $item['mit_id'];
                $data[$ip]['mit_id'] = $mit_id;
                $data[$ip]['datacenter_id'] = $relates[$mit_id];
            }
        }

        //给未启用的ip附上datacenter_id和mit_id，值为0
        foreach($data as &$item) {
            if (!array_key_exists('datacenter_id', $item)) {
                $item['mit_id'] = 0;
                $item['datacenter_id'] = 0;
            }
        }; unset($item);

        //分组
        $data = Arr::groupBy($data, 'datacenter_id');
        foreach ($data as $dc_id => &$item) {
            $item = Arr::groupBy($item, 'mit_id');
        }; unset($item);


        foreach ($data as $dc_id => &$group) {
            foreach ($group as $mit_id => &$items) {
                $mitigation = $mit_id ? $mitigations[$mit_id] : [];
                $mitigation['ddosinfos'] = array_slice($items, 0);
                $items = $mitigation;
            }; unset($items);
        }; unset($group);

        return $data;
    }

    /**
     * 设置IP安全状态
     * @param  String / Array   $ips 要修改的IP集合或单IP
     * @param  String $status
     * @param  Boolean $is_force 是否强制修改
     * @return Boolean 是否【更新成功且有记录被更新】
     **/
    public static function setStatus($ips, $status, $is_force = NULL) {
        return self::$repo->setStatus($ips, $status, $is_force);
    }

    /**
     * 牵引IP
     * @param  [type] $ip       [description]
     * @param  [type] $bps      [description]
     * @param  [type] $blockway [description]
     * @return [type]           [description]
     */
    public static function block($ip, $bps, $blockway = '' ) {
        Response::note('正在牵引IP：%s...', $ip);
        try {
            $bool = self::$repo->block($ip, $bps, config('app.key'), $blockway);
            Response::echoBool($bool);
            return $bool;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $e = parse_general_exception($e);
            if ($e->message) $message = $e->message;
            Response::warn($message.'，牵引失败');
            return true;
        }
    }

    /**
     * 解除牵引IP
     * @param  array    $ips        解除牵引的ips
     * @return array                被成功解除牵引的ips
     */
    public static function unblock($ip){
        Response::note('正在解除牵引IP：%s...', $ip);
        return self::$repo->unblock($ip);
    }

    /**
     * 释放牵引到期的IP
     * @return [type] [description]
     */
    public static function release() {
        //找出未解除，且牵引过期的ids
        $lists = self::lists([
            'awhere' => ['status' => 'BLOCK', "block_expire<=".time()],
            'fields' => 'ip',
            'order'  => 'block_expire asc'
        ]);

        if (!$lists) {
            Response::reply('#tab未找到牵引过期的IP');
            return [];
        }

        // 解除牵引更新“标志值为已解除”、实例状态更新为“正常”;
        $ips = Arr::pick($lists, 'ip');
        foreach ($ips as $ip) {
            $bool = self::unblock($ip);
            Response::echoBool($bool);
        }
        Alert::ipUnblock($ips);

        return $ips;
    }

    /**
     * 取得指定状态的ips
     * @param  String $status
     * @return Array 被攻中的或被牵引中（攻击未结束）的ip集合
     */
    public static function getByStatus($status) {
        $ret = parent::lists([
            'fields' => 'ip',
            'awhere' => ['status' => $status]
        ]);
        if ($ret) $ret = Arr::flat($ret);

        return $ret;
    }
}
IPBase::init();
?>