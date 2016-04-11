<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;
use Tasks\BlackholeAction;
use Landers\Framework\Core\Queue;

Class BlackHole extends Repository {
    protected static $connection = 'mitigation';
    protected static $datatable  = 'blackhole';
    protected static $DAO;

    public static function exists($ip) {
        return self::count(['ip' => $ip, 'is_unblock' => 0, 'expire > '.time()]);
    }

    public static function doBlock($ip, $bps) {
        //牵引动作入队列
        $task = new BlackholeAction('block', [
            'ip' => $ip, 'bps' => $bps,
        ]);
        return Queue::singleton('blackhole')->push($task);
    }

    /**
     * 封禁IP
     * @param  array        $ips        ip数组
     * @return array                    成功牵引的ip数组
     */
    public static function block($ip, $bps){
        if (self::exists($ip)) {
            Response::note('#tabIP：%s尚处于牵引中， 无需再次牵引', $ip);
            return;
        }

        Response::note('事务处理：牵引动作入队列、插入牵引记录、写入攻击结束、更新实例网络状态为“牵引中”');
        return self::transact(function() use ($ip, $bps){
            return Instance::transact(function() use ($ip, $bps){
                //牵引动作入队列
                $ret = self::doBlock($ip, $bps);
                Response::bool(!!$ret, '#tab牵引请求入队%s');
                if (!$ret) return false;

                //确定牵引时长
                $block_duration = DataCenter::block_duration(DataCenter::find_ip($ip));

                //插入牵引记录
                $hours = $block_duration;
                $data = ['ip' => $ip, 'expire' => strtotime("+$hours hours"), 'bps' => $bps];
                $bool = self::insert($data);
                Response::bool($bool, '#tab牵引记录写入%s');
                if (!$bool) return false;

                //更新ip的攻击历史为结束攻击
                $bool = DDoSHistory::save_end_attack($ip, 'block');
                if (!$bool) return false;

                //更新实例的网络状态为2（牵引中）
                $bool = Instance::update_net_status($ip, 2, true);
                Response::bool($bool, '#tab更新实例的网络状态为“牵引中”%s');
                if (!$bool) return false;

                return true;
            });
        });
    }

    public static function doUnblock($ip) {
        $task = new BlackholeAction('unblock', [
            'ip' => $ip
        ]);
        return Queue::singleton('blackhole')->push($task);
    }

    /**
     * 解除牵引IP
     * @param  array    $ips        解除牵引的ips
     * @return array                被成功解除牵引的ips
     */
    public static function unblock(){
        //找出未解除，且牵引过期的ids
        $ids = self::lists([
            'awhere' => ["expire<=".time(), 'is_unblock' => 0],
            'fields' => 'id',
            'order'  => ''
        ]);
        if (!$ids) {
            Response::note('#tab未找到牵引过期的记录');
            return [];
        }

        //根据ids更新“未解除”标志为“已解除”
        $ids = Arr::flat($ids);

        //由事务中更改其值
        $ips = [];

        //事务处理：解除牵引动作入队列、解除牵引更新“标志值为已解除”、实例状态更新为“正常”成功
        $bool = self::transact(function() use ($ids, &$ips){
            //解除牵引动作入队列
            foreach ($ips as $ip) {
                $ret = self::doUnblock($ip);
                Response::bool(!!$ret, '#tab解除牵引请求入队%s');
            }

            $awhere = ['id' => $ids];
            if ( self::update(['is_unblock' => 1], $awhere) ) {
                Response::note('#tab解除牵引更新“标志值为已解除”成功');
            } else {
                Response::note('#tab解除牵引更新“标志值为已解除”失败');
                return false;
            }

            //根据ids找出要应的ips
            $ips = self::lists(['awhere' => $awhere, 'fields' => 'ip']);
            $ips = array_unique(Arr::flat($ips));
            //将牵引过期的ips所在的实例的net_state字段为正常(0)
            if (Instance::update_net_status($ips, 0)) {
                Response::note('#tab实例状态更新为“正常”成功：%s', implode('，', $ips));
            } else {
                Response::note('#tabIP实例更新为正常状态失败');
                return false;
            }

            //最终返回true
            return true;
        });

        if (!$bool) {
            $msg = '事务处理失败：解除牵引更新“标志值为已解除”、实例状态更新为“正常”';
            Response::error('#tab$msg');
            Notify::developer($msg);
            System::halt();
        }

        //给ips解除牵引
        foreach ($ips as $ip) {
            if ($command = $config['unblock']) {
                $command = sprintf($command, $ip);
                exec($command, $output, $return);
            }
        }
        Response::note('#tab成功解除牵引：%s', implode('，', $ips));

        return $ips;
    }
}
BlackHole::init();
?>