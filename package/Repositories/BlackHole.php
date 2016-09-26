<?php
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;

Class BlackHole {
    private static $repo, $repoMitigation;
    public static function init() {
        self::$repo = repository('blackHole');
        self::$repoMitigation = repository('mitigation');
    }

    public static function exists($ip) {
        return Mitigation::count(['ip' => $ip, 'status' => 'BLOCK']);
    }

    public static function doBlock($ip, $bps, $is_force) {
        $from = 'Crontab';
        $blockway = $is_force ? 'force' : '';
        return self::$repo->block($ip, $bps, $from, $blockway);
    }


    /**
     * 牵引IP
     * @param  [type] $ip       [description]
     * @param  [type] $bps      [description]
     * @param  [type] $blockway [description]
     * @return [type]           [description]
     */
    public static function block($ip, $bps, $blockway ) {
        return self::$repoMitigation->blockByIp($ip, $bps, ENV_appkey, $blockway);
    }


    public static function doUnblock($ip) {
        return self::$repo->unblock($ip);
    }

    /**
     * 解除牵引IP
     * @param  array    $ips        解除牵引的ips
     * @return array                被成功解除牵引的ips
     */
    public static function unblock($ip){
        return self::$repoMitigation->unblockByIp($ip);
    }

    /**
     * 释放牵引到期的IP
     * @return [type] [description]
     */
    public static function release() {
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
BlackHole::init();
?>