<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;
use Tasks\BlackholeAction;
use Landers\Framework\Core\Queue;
use Landers\Framework\Core\StaticRepository;

// Class BlackHole extends StaticRepository {
//     protected static $connection = 'mitigation';
//     protected static $datatable  = 'blackhole';
//     protected static $DAO;

Class BlackHole {
    public static function exists($ip) {
        return Mitigation::count(['ip' => $ip, 'status' => 'BLOCK']);
    }

    public static function doBlock($ip, $bps, $is_force) {
        $from = 'Crontab';
        $blockway = $is_force ? 'force' : '';
        return repository('blackHole')->block($ip, $bps, $from, $blockway);
    }

    /**
     * 封禁IP
     * @param  array        $ips        ip数组
     * @return array                    成功牵引的ip数组
     */
    public static function block($ip, $bps, $is_force){
        if (!$is_force && self::exists($ip)) {
            Response::note('#tab该IP尚处于牵引中，无需再次牵引', $ip);
            return;
        }

        // 牵引动作入队列、写入攻击结束、更新实例网络状态为“牵引中”';
        Response::transactBegin();
        $result = Mitigation::transact(function() use ($ip, $bps, $is_force){
            //牵引动作入队列
            Response::note('#tab执行牵引动作...');
            list($bool, $message) = self::doBlock($ip, $bps, $is_force);
            Response::echoBool(!!$bool);
            if (!$bool) return false;

            //更新ip的攻击历史为结束攻击
            Response::note('#tab写入由牵引所致的攻击结束...');
            $bool = DDoSHistory::saveAttackEnd($ip, 'BLOCK');
            if (!$bool) return false;

            //更新云盾IP状态为（牵引中）
            Response::note('#tab更新云盾IP状态为“已牵引”...');
            $bool = Mitigation::setStatus($ip, 'BLOCK', true);
            Response::echoBool($bool);
            if (!$bool) return false;

            return true;
        });

        return Response::transactEnd($result);
    }

    public static function doUnblock($ip) {
        return repository('blackHole')->unblock($ip);
    }

    /**
     * 解除牵引IP
     * @param  array    $ips        解除牵引的ips
     * @return array                被成功解除牵引的ips
     */
    public static function unblock(){
        //找出未解除，且牵引过期的ids
        $lists = Mitigation::lists([
            'awhere' => ['status' => 'BLOCK', "block_expire<=".time()],
            'fields' => 'ip',
            'order'  => 'block_expire asc'
        ]);

        if (!$lists) {
            Response::note('#tab未找到牵引过期的IP');
            return [];
        }

        $ips = [];

        // 解除牵引更新“标志值为已解除”、实例状态更新为“正常”;
        Response::transactBegin();
        $result = Mitigation::transact(function() use (&$lists, &$ips){
            //解除牵引动作入队列
            Response::note('#tab执行解除牵引动作...');
            foreach ($lists as $item) {
                list($bool, $message) = self::doUnblock($item['ip']);
                if ( !$bool) continue;
                $ips[] = $item['ip'];
            }
            if (!count($ips)) {
                Response::echoBool(false);
                return false;
            }
            Response::echoSuccess('%s 请求入队成功', count($ips));

            //将牵引过期的ips所在的云盾的状态改为正常
            Response::note('#tab更新云盾IP为“正常”...');
            $bool = Mitigation::setStatus($ips, 'NORMAL');
            Response::echoBool($bool);
            if (!$bool) return false;

            //最终返回true
            return true;
        });
        Response::transactEnd($result);

        if ($result) Alert::ipUnblock($ips);

        return $ips;
    }
}
?>