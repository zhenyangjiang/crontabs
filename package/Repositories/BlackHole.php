<?php
use Landers\Framework\Core\Response;
use Landers\Framework\Core\Queue;
use Landers\Substrate\Utils\Arr;

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
    public static function block($ip, $bps, $blockway = '' ) {
        Response::note('正在牵引IP：%s...', $ip);
        try {
            $bool = self::$repoMitigation->blockByIp($ip, $bps, config('app.key'), $blockway);
            dp($bool);
        } catch (\Exception $e) {
            return true;
        }
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
        Response::note('正在解除牵引IP：%s...', $ip);
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
            Response::relay('#tab未找到牵引过期的IP', 'cyan');
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
}
BlackHole::init();
?>