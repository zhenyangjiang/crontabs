<?php
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Config;
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Response;
use Landers\Substrate\Classes\StatusCtrl;

class Instance extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_instances';
    protected static $DAO;

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
    public static function suspend($xinstance) {
        $instance = self::info($xinstance);
        if ($instance['status'] !== 'SUSPENDED') {
            $ret = Virt::suspend($instance['vpsid']);
            if  ( $ret['done'] ) {
                Instance::change_status($instance, 'toSuspend', true);
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * 挂起主机
     * @param  array      $instance    实例
     * @return booean
     */
    public static function unsuspend($xinstance) {
        $instance = self::info($xinstance);

        if ($instance['status'] === 'SUSPENDED') {
            $ret = Virt::unsuspend($instance['vpsid']);
            if  ( $ret['done'] ) {
                Instance::change_status($instance, 'toNormal', true);
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
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
        $instance = self::info($xinstance);
        if (!$instance) return true;

        $instance_id = $instance['id'];
        $instance_ip = $instance['mainipaddress'];

        $relates = [
            'ip' => [
                'match' => $instance_ip,
                'models' => [
                    Mitigation::class,
                    DDoSHistory::class,
                    BlockLog::class,
                    TopCountry::class,
                ]
            ],
            'instance_id' => [
                'match' => $instance_id,
                'models' => [
                    ApplyIp::class,
                ]
            ],
            'dest' => [
                'match' => $instance_ip,
                'models' => [
                    DDoSInfo::class,
                ]
            ],
            'id' => [
                'match' => $instance_id,
                'models' => [
                    self::class,
                ]
            ]
        ];

        $unions = [
            DDoSInfo::class => 'ALL'
        ];

        $collectid = [
            Mitigation::class => [],
        ];

        $results = [];

        //事务嵌套处理
        Response::transactBegin();
        $result = self::transact(function() use ($instance, $relates, $unions, &$collectid, $instance_id, $instance_ip, &$results){
            return DDoSInfo::transact(function() use ($instance, $relates, $unions, &$collectid, $instance_id, $instance_ip, &$results){

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
                        if (array_key_exists($model, $collectid)) {
                            $ids = $model::lists(array_merge($opts, [
                                'awhere' => $awhere,
                                'fields' => 'id',
                                'askey' => 'id'
                            ]));
                            $collectid[$model] = array_keys($ids);
                        }

                        Response::note('#tab正在删除模型【%s】数据...', $model);
                        $results[$model] = $model::delete($awhere, $opts);
                        Response::echoBool(!!$results[$model]);
                    }
                }

                // 由 collectid 中 Mitigation::class 找出FirewallRule
                if ( $mitigation_ids = $collectid[Mitigation::class] ) {
                    Response::note('#tab正在向接口提交删除防火墙规则...');
                    $FwRuleModel = FirewallRule::class;
                    $bool = $FwRuleModel::delete([
                        'mitigation_id' => $mitigation_ids
                    ]);
                    Response::echoBool($bool);
                    if (!$bool) reportDevException('销毁实例时防火墙规则删除失败', array('context' => compact($mitigation_id)));
                }

                //删除 cc 防护规则
                Response::note('#tab正在向接口提交删除CC防护...');
                $apiurl = Config::get('hosts', 'api') . '/intranet/firewall/close-cc-defend';
                $ret = OAuthHttp::post($apiurl, ['ip' => $instance_ip]);
                Response::echoBool($ret['success'], $ret['message']);
                if (!$ret['success']) {
                    reportDevException('销毁实例时CC防护删除失败', array('context' => compact($instance)));
                }

                // 执行删除实例主机
                Response::note('#tab销毁虚拟机...');
                $ret = Virt::delete_vs($instance['vpsid']);
                Response::echoBool($ret['done']);
                if ( !$ret['done'] ) return false;

                return true;
            });
        });

        return Response::transactEnd($result);
    }
}
Instance::init();
?>