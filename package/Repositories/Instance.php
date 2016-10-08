<?php
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;
use Landers\Substrate\Classes\StatusCtrl;
use Services\OAuthClientHttp;

class Instance extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_instances';
    protected static $DAO;

    private static $repoInstance;

    public static function init() {
        self::$repoInstance = repository('instance');
        parent::init();
    }

    /**
     * 由实例确定其所在数据中心
     * @param  array    $instance   实例
     * @return array                数据中心
     */
    public static function datacenter($instance) {
        //返回数据中心信息
        $dc_id = $instance['datacenter_id'];
        $ret = DataCenter::find($dc_id);
        return $ret ? $ret : NULL;
    }

    /**
     * 取得IP所属实例
     * @param  String $ip IP
     * @return Mixed
     */
    public static function findByIp($ip, $fields = NULL) {
        return self::find([
            'fields' => $fields,
            'awhere' => ['mainipaddress' => $ip]
        ]);
    }

    /**
     * 是否处于试用期
     * @param  [type]  $instance [description]
     * @return boolean           [description]
     */
    public static function isTrial($instance) {
        return $instance['trial_expire'] > time();
    }

    /**
     * 离云主机过期剩余天数
     * @param  InstanceModel $instance [description]
     * @return [type]                  [description]
     */
    public static function expireDays($instance) {
        $expire = $instance['expire'];
        return - (int) Datetime::diff_now_days($expire);
    }

    /**
     * 挂起主机
     * @param  array      $instance    实例
     * @return booean
     */
    public static function suspend($mix_instance) {
        return self::$repoInstance->suspend($mix_instance);
    }

    /**
     * 挂起主机
     * @param  array      $instance    实例
     * @return booean
     */
    public static function unsuspend($mix_instance) {
        return self::$repoInstance->unsuspend($mix_instance);
    }

    /**
     * 取得状态控制对象
     * @return [type] [description]
     */
    private static $StatusCtrl;
    private static function getStatusController(){
        if (!$ret = &self::$StatusCtrl) {
            $statuses = [
                'NORMAL'    => [
                    'text'  => '正常',
                    'action'=> [
                    ]
                ],
                'SUSPENDED'    => [
                    'text'  => '挂起',
                    'action'=> [
                    ]
                ]
            ];

            $actions = [
                'toNormal'    => [
                    'verify'    => ['SUSPENDED'],
                    'status'    => 'NORMAL',
                    'text'      => '更新为正常',
                ],
                'toSuspend'   => [
                    'verify'    => 'NORMAL',
                    'status'    => 'SUSPENDED',
                    'text'      => '挂起',
                ]
            ];
            $ret = new StatusCtrl(self::$DAO, $statuses, $actions);
        }
        return $ret;
    }

    /**
     * 执行改变状态动态
     * @param  [type]       $mix_instance      [description]
     * @param  [type]       $action_key     [description]
     * @param  [type]       $callback       [description]
     * @param  boolean      $is_force       是否强制
     * @return boolean
     */
    public static function changeStatus($mix_instance, $action_key, $is_force = false, $callback = NULL) {
        $id = self::info($mix_instance, 'id');
        $StatusCtrl = self::getStatusController();
        return $StatusCtrl->doaction($id, $action_key, $is_force, $callback);
    }

    /**
     * 取得实例状态
     * @param  mix      $mix_instance      [description]
     * @return mix
     */
    public static function status($mix_instance) {
        $status_key = self::info($mix_instance, 'status');
        $StatusCtrl = self::getStatusController();
        return $StatusCtrl->status($status_key);
    }

    /**
     * 销毁实例
     * @param  [type] $mix_instance [description]
     * @return [type]            [bool, message]
     */
    public static function destroy($mix_instance, &$error = NULL) {
        return repository('instance')->destroy($mix_instance);
    }
}
Instance::init();
?>