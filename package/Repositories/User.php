<?php
use Landers\Framework\Core\StaticRepository;

use ULan\Repository\Crypt;
use Landers\Substrate\Utils\Arr;

class User {
    private static $repoUser;
    private static $repoMoney;
    public static function init() {
        self::$repoUser = repository('user');
        self::$repoMoney = repository('money');
    }

    /**
     * 取得用户信息
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public static function find($uid, $fields = NULL) {
        return self::$repoUser->closure(function($q) use ($uid, $fields){
            $user = $q->find($uid)->toArray();
            return Arr::slice($user, $fields);
        });
    }

    /**
     * 用户支出
     * @param  [type] $uid    [description]
     * @param  [type] $money  [description]
     * @param  [type] $feelog [description]
     * @return [type]         [description]
     */
    public static function expend($uid, $money, $feelog) {
        return self::$repoMoney->lockAndExpend($uid, $money, $feelog);
    }

    /**
     * 取得用户可用余额
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public static function money($uid) {
        return self::$repoUser->closure(function($q) use ($uid){
            return $q->find($uid)->money;
        });
    }
}
User::init();
?>