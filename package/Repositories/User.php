<?php
use Landers\Framework\Core\StaticRepository;

use ULan\Repository\Crypt;

class User extends StaticRepository {
    protected static $connection = 'oauth';
    protected static $datatable = 'ulan_users';
    protected static $DAO;

    /**
     * 取得用户余额
     * @param  int      $uid        用户ID
     * @return float                余额
     */
    private static $num_crypt;
    public static function get_money($uid) {
        $money = self::find($uid, 'money');
        return (float)Crypt::decode($money, $uid);
    }

    /**
     * 设置用户余额
     * @param int       $uid        用户ID
     * @param [type]   $money     [description]
     * @param boolean
     */
    public static function set_money($uid, $money) {
        $money = Crypt::encode($money, $uid);
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
        $money = self::get_money($uid);
        $money -= (float)$amount;
        return self::set_money($uid, $money);
        // return self::update(
        //     ['money' => "`money`-$amount"], ['id' => $uid]
        // );
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

    /**
     * 用户余额
     * @return array
     */
    public static function balance()
    {
        $ret = self::listall(['fields' => ['id', 'money']]);
        $data = [];
        foreach ($ret as $v) {
            $data[$v['id']] = $v['money'];
        }
        return $data;
    }

}
User::init();
?>