<?php
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\Repository;
use Landers\Framework\Core\Response;
use Landers\Substrate\Classes\StatusCtrl;
use ULan\SolusVM\Solusvm;

class Instance extends Repository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_instances';
    protected static $DAO;

    /**
     * 指定的IP更新到指定状态码
     * @param  String / Array   $ips 要修改的IP集合或单IP
     * @param  Int $status   状态代码：０(正常)  1(被攻击)  2(被牵引)
     * @param  Boolean $is_force 是否强制修改
     * @return Boolean 是否【更新成功且有记录被更新】
     */
    public static function update_net_status($ips, $status, $is_force = false) {
        $ips = (array)$ips;
        $status = (string)$status;
        $updata = ['net_state' => $status, 'net_state_updtime' => date('Y-m-d H:i:s')];
        $awhere = ['mainipaddress' => $ips];

        $statuses = [
            '1' => ['0'],
            '2' => ['1'],
            '0' => ['2', '1']
        ];
        if (!$is_force) {
            if (!$ori_statuses = $statuses[$status]) return false;
            if ( count($ori_statuses) == 1 ) $ori_statuses = pos($ori_statuses);
            $awhere['net_state'] = $ori_statuses;
        }

        return self::update($updata, $awhere);
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
    public static function find_ip($ip, $fields = NULL) {
        return self::find([
            'fields' => $fields,
            'awhere' => ['mainipaddress' => $ip]
        ]);
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
     * 取得已过期的实例列表
     * @return [type] [description]
     */
    public static function timeout_expire(){
        $time = time();
        return self::lists([
            'awhere' => ["expire<$time"]
        ]);
    }

    /**
     * 取将即将到期的实例列表
     * @return [type] [description]
     */
    public static function be_about_to_expire($days){
        /*
        $now = time(); $now = strtotime('2015-10-6 20:20:20');
        $begin = sprintf('`expire`-%s', $days * 3600 * 24);
        $end = '`expire`';
        return self::lists([
            'awhere' => ["$begin<=$now", "$end>=$now"]
        ]);
        */

        $begin = time(); $end = Datetime::add('days', $days, $begin);
        return self::lists([
            'awhere' => ["`expire` between $begin and $end"]
        ]);
    }

    /**
     * 执行 solusvm 动作
     * @param  [type] $xinstance [description]
     * @param  [type] $callback  [description]
     * @return [type]            [description]
     */
    private static $solusvm;
    private static function solusvm_action($xinstance, $callback) {
        if (!$solusvm = &self::$solusvm) {
            $config = Config::get('solusvm');
            $solusvm = new Solusvm($config['id'], $config['key'], $config['host'], $config['port']);
        }
        $vserverid = self::info($xinstance, 'vserverid');
        $ret = $callback($solusvm, $vserverid);
        $ret = json_decode($ret['data'], true);
        return $ret['status'] == 'success';
    }

    /**
     * 挂起主机
     * @param  array      $instance    实例
     * @return booean
     */
    public static function suspend($xinstance, &$error = NULL) {
        $instance = self::info($xinstance);
        if ($instance['status'] !== 'SUSPENDED') {
            $bool = self::solusvm_action($xinstance, function($solusvm, $vserverid) use (&$error){
                return $solusvm->suspend($vserverid);
            });
            if (!$bool) return false;
            return Instance::change_status($instance, 'suspend', true);
        } else {
            return true;
        }
    }

    /**
     * 检查本次通知是否已经通知过了
     * @param  [type] $xinstance [description]
     * @return [type]            [description]
     */
    public static function check_is_notified($xinstance) {
        $notify_at = self::info($xinstance, 'notified_at');
        if ( $notify_at && !Datetime::diff($notify_at, time()) ) {
            $begin = Datetime::format($notify_at, 'Y-m-d H:i:s');
            $end = Datetime::add('days', 1, $notify_at, 'Y-m-d H:i:s');
            Response::note('此实例在%s ~ %s已通知，已需重复通知', colorize($begin, 'yellow'), colorize($end, 'yellow'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新实例（由于某些原因需要通知时的）通知时间，以便不在某时间段内重复通知
     * @param  [type]   $xinstance  [description]
     * @return [type]               [description]
     */
    public static function update_notify_time($xinstance) {
        $id = self::info($xinstance, 'id');
        $msg = '实例最后通知时间更新';
        if ($bool = self::update(['notified_at' => time()], ['id' => $id])) {
            Response::note($msg . '成功。');
        } else {
            Response::note($msg . '失败！');
        }
        return $bool;
    }

    /**
     * 取得状态控制对象
     * @return [type] [description]
     */
    private static $StatusCtrl;
    private static function get_status_controller(){
        if (!$ret = &self::$StatusCtrl) {
            $statuses = [
                'NORMAL'    => [
                    'text'  => '正常',
                    'action'=> [
                        'action_key1', 'action_key2'
                    ]
                ],
                'SUSPENDED'    => [
                    'text'  => '挂起',
                    'action'=> [
                        'action_key3', 'action_key4'
                    ]
                ],
                'TODELETE'    => [
                    'text'  => '待删除',
                    'action'=> [
                        'action_key3', 'action_key4'
                    ]
                ],
            ];

            $actions = [
                'tonormal'    => [
                    'verify'    => ['SUSPENDED', 'TODELETE'],
                    'status'    => 'NORMAL',
                    'text'      => '更新为正常',
                ],
                'suspend'   => [
                    'verify'    => 'NORMAL',
                    'status'    => 'SUSPENDED',
                    'text'      => '挂起',
                ],
                'delete'    => [
                    'verify'    => 'SUSPENDED',
                    'status'    => 'TODELETE',
                    'text'      => '删除',
                ],
            ];
            $ret = new StatusCtrl(self::$DAO, $statuses, $actions);
        }
        return $ret;
    }

    /**
     * 执行改变状态动态
     * @param  [type]       $xinstance      [description]
     * @param  [type]       $action_key     [description]
     * @param  [type]       $callback       [description]
     * @param  boolean      $is_force       是否强制
     * @return boolean
     */
    public static function change_status($xinstance, $action_key, $is_force = false, $callback = NULL) {
        $id = self::info($xinstance, 'id');
        $StatusCtrl = self::get_status_controller();
        return $StatusCtrl->doaction($id, $action_key, $is_force, $callback);
    }

    /**
     * 取得实例状态
     * @param  mix      $xinstance      [description]
     * @return mix
     */
    public static function status($xinstance) {
        $status_key = self::info($xinstance, 'status');
        $StatusCtrl = self::get_status_controller();
        return $StatusCtrl->status($status_key);
    }

    /**
     * 销毁实例
     * @param  [type] $xinstance [description]
     * @return [type]            [description]
     */
    public static function destroy($xinstance) {
        $instance = self::info($instance);
        if (!$instance) return true;
        $instance_id = $instance['id'];
        $instance_ip = $instance['mainipaddress'];

        $relates = [
            'instance_id' => [
                'match' => $instance_id,
                'models' => [
                    ApplyIp::class,
                    InstanceDomain::class,
                    InstanceOrderDetail::class,
                ]
            ],
            'instance_ip' => [
                'match' => $instance_ip,
                'models' => [
                    FirewallRule::class,
                ]
            ],
            'ip' => [
                'match' => $instance_ip,
                'models' => [
                    Mitigation::class,
                    BlackHole::class,
                    DDoSHistory::class,
                ]
            ],
            'dest' => [
                'match' => $instance_ip,
                'models' => [
                    DDoSInfo::class,
                ]
            ]
        ];

        $unions = [
            DDoSInfo::class => 'ALL'
        ];

        $results = [];

        //事务嵌套处理
        return Instance::transact(function() use ($instance, $relates, $unions, $instance_id, $instance_ip, &$results){
            return DDoSInfo::transact(function() use ($instance, $relates, $unions, $instance_id, $instance_ip, &$results){
                // DDoSHistoryFee关联了DDoSHistory
                $ids = DDoSHistory::ids(['awhere' => ['ip' => $instance_ip]]);

                // 执行删除关联模型数据
                foreach ($relates as $field => $item) {
                    $match = $item['match'];
                    $models = $item['models'];
                    foreach ($models as $model) {
                        $awhere = [$field => $match];
                        $opts = [];
                        if ($union = Arr::get($unions, $model)) {
                            $opts['unions'] = $union;
                        }
                        $results[$model] = $model::delete($awhere, $opts);
                    }
                }

                // 执行删除实例记录
                $model = self::class;
                $results[$model] = $model::delete(['id' => $instance_id]);

                // 执行删除实例主机
                return self::solusvm_action($instance, function($solusvm, $vserverid) use (&$error){
                    return $solusvm->terminate($vserverid);
                });
            });
        });
    }
}
Instance::init();
?>