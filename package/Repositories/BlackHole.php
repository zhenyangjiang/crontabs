<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Response;
use Tasks\BlackholeAction;
use Landers\Framework\Core\Queue;
use Landers\Framework\Core\StaticRepository;

Class BlackHole extends StaticRepository {
    protected static $connection = 'mitigation';
    protected static $datatable  = 'blackhole';
    protected static $DAO;

    public static function exists($ip) {
        return self::count(['ip' => $ip, 'is_unblock' => 0, 'expire > '.time()]);
    }

    public static function doBlock($ip, $bps, $is_force) {
        $opts = [
            'ip' => $ip, 'bps' => $bps, 'from' => 'Crontab'
        ];
        if ($is_force) $opts['blockway'] = 'force';

        //牵引动作入队列
        $task = new BlackholeAction('block', $opts);
        return Queue::singleton('blackhole')->push($task);
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

        $transacter = '牵引动作入队列、插入牵引记录、写入攻击结束、更新实例网络状态为“牵引中”';
        Response::note('事务处理：%s', $transacter);
        $result = self::transact(function() use ($ip, $bps, $is_force){
            return Instance::transact(function() use ($ip, $bps, $is_force){
                //牵引动作入队列
                Response::note('#tab牵引请求入队...');
                $ret = self::doBlock($ip, $bps, $is_force);
                Response::echoBool(!!$ret);
                if (!$ret) return false;

                //确定牵引时长
                $block_duration = DataCenter::block_duration(DataCenter::find_ip($ip));

                //插入牵引记录
                Response::note('#tab写入牵引记录...');
                $hours = $block_duration;
                $data = ['ip' => $ip, 'expire' => strtotime("+$hours hours"), 'bps' => $bps];
                $bool = self::insert($data);
                Response::echoBool($bool);
                if (!$bool) return false;

                //更新ip的攻击历史为结束攻击
                Response::note('#tab写入由牵引所致的攻击结束...');
                $bool = DDoSHistory::save_end_attack($ip, 'block');
                Response::echoBool($bool);
                if (!$bool) return false;

                //更新实例的网络状态为2（牵引中）
                Response::note('#tab更新实例的网络状态为“牵引中”...');
                $bool = Instance::update_net_status($ip, 2, true);
                Response::echoBool($bool);
                if (!$bool) return false;

                return true;
            });
        });

        return Response::transactEnd($result);
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
        $lists = self::lists([
            'awhere' => ["expire<=".time(), 'is_unblock' => 0],
            'fields' => 'id, ip',
            'order'  => 'id desc'
        ]);

        if (!$lists) {
            Response::note('#tab未找到牵引过期的记录');
            return [];
        }

        $ips = []; $ids = [];

        //事务处理：解除牵引动作入队列、解除牵引更新“标志值为已解除”、实例状态更新为“正常”成功
        $transacter = '解除牵引更新“标志值为已解除”、实例状态更新为“正常';
        $result = self::transact(function() use (&$lists, &$ids, &$ips){

            //解除牵引动作入队列
            Response::note('#tab解除牵引请求入队...');
            foreach ($lists as $item) {
                $queueid = self::doUnblock($item['ip']);
                if ( !$queueid ) continue;

                $ids[] = $item['id'];
                $ips[] = $item['ip'];
            }
            if (!count($ids)) {
                Response::echoBool($bool);
                return false;
            }
            Response::echoSuccess('%s 请求入队成功', count($ids));
            $ips = array_unique($ips);

            //更新标志
            Response::note('#tab解除牵引更新“标志值”为已解除...');
            $bool = parent::update(['is_unblock' => 1], ['id' => $ids]);
            Response::echoBool($bool);
            if (!$bool) return false;

            //将牵引过期的ips所在的实例的net_state字段为正常(0)
            Response::note('#tab更新实例为“正常”...');
            $bool = Instance::update_net_status($ips, 0);
            Response::echoBool($bool);
            if (!$bool) return false;

            //最终返回true
            return true;
        });

        Response::transactEnd($result);

        if (!$ips) {
            Notify::developer(sprintf('事务处理失败：%s', $transacter));
            System::halt();
        } else {
            return $ips;
        }
    }
}
BlackHole::init();
?>