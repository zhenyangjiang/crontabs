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
        return IPBase::count(['ip' => $ip, 'status' => 'BLOCK']);
    }

    public static function block($ip, $bps, $is_force) {
        $from = 'Crontab';
        $blockway = $is_force ? 'force' : '';
        return self::$repo->block($ip, $bps, $from, $blockway);
    }

    public static function unblock($ip) {
        return self::$repo->unblock($ip);
    }
}
BlackHole::init();
?>