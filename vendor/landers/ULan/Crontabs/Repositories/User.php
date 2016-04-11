<?php
use Landers\Framework\Core\Repository;

class User extends Repository {
    protected static $connection = 'oauth';
    protected static $datatable = 'ulan_users';
    protected static $DAO;

    /**
     * 取得用户余额
     * @param  int      $uid        用户ID
     * @return float                余额
     */
    public static function get_money($uid) {
        return self::find($uid, 'money');
    }

    /**
     * 设置用户余额
     * @param int       $uid        用户ID
     * @param [type]   $money     [description]
     * @param boolean
     */
    public static function set_money($uid, $money) {
        return self::update(
            ['money' => $money], ['id' => $uid]
        );
    }

    /**
     * 支付
     * @param  [type] $uid    [description]
     * @param  [type] $amount [description]
     * @return [type]         [description]
     */
    public static function pay_money($uid, $amount) {
        return self::update(
            ['money' => "`money`-$amount"], ['id' => $uid]
        );
    }

    /**
     * 取得用户信息
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public static function get($uid, $fields = NULL) {
        $info = self::find($uid, $fields);
        $name = $info['realname'] or $name = $info['username'];
        $info['user_name'] = $name;
        return $info;
    }

}
User::init();
?>