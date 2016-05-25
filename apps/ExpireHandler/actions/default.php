<?php
use Landers\Substrate\Utils\Datetime;
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;
require __DIR__.'/../include/Helper.php';

echo PHP_EOL;
Response::note(['【实例到期后，实例挂起、待删除、自动续费】（'.System::app('name').'）开始工作','#dbline']);

$instances = Instance::timeout_expire();
if ($instances) {
    Response::note('共找到期的实例：%s台', count($instances));
} else {
    Response::note('当前暂无过期的实例主机');
    System::continues();
}
Response::note(['#blank', '逐一对过期的实例进行相关操作...']);

foreach ($instances as $instance) {
    Response::note('#line');
    $instance_ip = $instance['mainipaddress'];

    Response::instance_detail($instance);

    //确定实例所属用户
    $uid = $instance['uid'];
    $user = User::get($uid);
    Response::user_detail($user);

    //过期天数
    $expire_days = -Instance::expireDays($instance);
    $expire_date = date('Y-m-d H:i:s', $instance['expire']);
    Response::note('此实例的过期时间：%s', $expire_date);

    //系统允许过期天数、剩余天数
    $retain_days = Settings::get('instance_expire_retain_days');
    $retain_days = $retain_days - $expire_days;

    $some_days = [
        'expire' => $expire_days,
        'allow' => $retain_days,
        'retain' => $retain_days
    ];

    //延长实例有效期
    $instance_update = [
        'data' => ['expire' => strtotime("+1 month", $instance['expire'])],
        'awhere' => ['id' => $instance['id']]
    ];

    //续费后的有效时间段
    $valid_times = [
        'begin' => date('Y-m-d H:i:s', $instance['expire'] + 1),
        'end'   => date('Y-m-d H:i:s', $instance_update['data']['expire'])
    ];

    //确定实例每月费用
    $instance_price = $instance['price'];
    $fee_renew_insntance = $instance_price;

    //确定实例所绑定的云盾每月费用
    $mitigation_info = Mitigation::find_ip($instance_ip);
    if ($mitigation_info) {
        $mitigation_price = $mitigation_info['price'];
        $mitigation_ability = $mitigation_info['ability'];

        $fee_renew_mitigation = $mitigation_price;
    } else {
        $msg = '实例到期自动续费发现无云盾记录';
        Response::error($msg);
        Notify::developer($msg);
        $fee_renew_mitigation = 0;
    };
    $fee_renew_total = $fee_renew_insntance + $fee_renew_mitigation;

    Response::note(['#blank', '本次所需扣费明细如下：']);
    if ( is_null($mitigation_info) ) {
        Response::note('#tab主机：%s元/月，未找到云盾记录，共计：%s元', $instance_price, $fee_renew_total);
    } else {
        Response::note('#tab主机：%s元/月，%sGbps云盾：%s/月，共计：%s元', $instance_price, $mitigation_ability, $mitigation_price, $fee_renew_total);
    }

    $status_text = Instance::status($instance);
    if (!$instance['is_auto_renew']) {  //未设置自动续费
        Response::note('#tab实例未设置自动续费');
        //过期天数是否超过系统允许
        if ( $retain_days < 0 ) {
            //执行删除操作
            destroy_instance($instace, $some_days);
        } else {
            //挂起实例、强制降级云盾
            $bool_transact = suspend_transact($instance, $user, $some_days, function() use ($instance, $uid, $some_days){
                Response::note('#tab将通知客户实例已被挂起，需充值后并手工续费');
                Notify::client('instance_expire_retain_manual', $uid, [
                    'instance_name' => $instance['hostname'],
                    'instance_ip'   => $instance['mainipaddress'],
                    'expire_days'   => $some_days['expire'],
                    'retain_days'   => $some_days['retain'],
                ]);
            });
        }
    } else {    //已设置自动续费
        Response::note('#tab实例已设置自动续费');
        if ( $user['money'] - $fee_renew_total > 0 ) {
            // 足够续费 云主机 + 云盾
            Response::note('#tab当前余额足够自动续费 云主机 + 云盾');
            //扣费日志数据包 改用 云主机+云盾扣费日志数据包
            if ($fee_renew_mitigation) {
                $feelog_data = [
                    'typekey' => 'renew_instance_mitigation',
                    'balance' => $user['money'] - $fee_renew_total,
                    'instance_ip' => $instance_ip,
                    'uid' => $uid,
                    'amount' => $fee_renew_total,
                    'description' => sprintf(
                        '云主机:%s (%s) + 云盾:%sGbps，自动续费%s',
                        $instance['hostname'], $instance_ip, $mitigation_ability,
                        $valid_times['begin'].' ~ '.$valid_times['end']
                    ),
                ];
            }

            $transaction_name = '实例扣费、实例扣费日志、延长实例有效期';
            Response::note('#blank');
            Response::note('执行事务处理：%s：', $transaction_name);
            $bool_transact = deduct_transact($uid, $instance_update, $feelog_data);
        } elseif ( $user['money'] - $fee_renew_insntance > 0 ) {
            // 仅够续费 云主机 ：强制降级云盾
            Response::note('#tab当前余额仅够自动续费 云主机，云盾将强制降级为免费或最低方案');

            // 实例扣费日志数据包
            $feelog_data = [
                'typekey' => 'renew_instance',
                'balance' => $user['money'] - $fee_renew_insntance,
                'instance_ip' => $instance_ip,
                'uid' => $uid,
                'amount' => $fee_renew_insntance,
                'description' => sprintf(
                    '云主机:%s (%s)，自动续费%s',
                    $instance['hostname'], $instance_ip,
                    $valid_times['begin'].' ~ '.$valid_times['end']
                ),
            ];
            $transaction_name = '实例扣费、实例扣费日志、延长实例有效期、强制降级云盾';
            Response::note('#blank');
            Response::note('执行事务处理：%s：', $transaction_name);
            $bool_transact = deduct_transact($uid, $instance_update, $feelog_data, [
                function() use ($instance){//强制降级云盾
                    $bool = Mitigation::down_grade($instance);
                    Response::bool($bool, '#tab云盾强制降级%s');
                    if ( !$bool) return false;

                    return true;
                }
            ]);
        } else {
            // 余额不足：
            Response::note('#tab实例所属用用余额不足');
            if ( $retain_days < 0 ) {
                //删除实例
                destroy_instance($instance, $some_days);
            } else {
                //挂起实例、实例改为非自动续费、强制降级云盾
                $bool_transact = suspend_transact($instance, $user, $some_days, function() use ($instance, $uid, $some_days){
                    Response::note('将通知客户实例已被挂起，需充值后并手工续费');
                    if (!Instance::check_is_notified($instance)) {
                        Notify::client('instance_expire_retain_auto', $uid, [
                            'instance_name' => $instance['hostname'],
                            'instance_ip'   => $instance['mainipaddress'],
                            'expire_days'   => $some_days['expire'],
                            'retain_days'   => $some_days['retain'],
                        ]);
                    }
                });
            }
        }

        if ( $bool_transact ) {
            Response::note('#tab事务成功完成');
        } else {
            if (!is_null($bool_transact )) {
                if ($feelog_data) {
                    $email_content = sprintf('扣费日志数据包：<br>%s', Arr::to_html($feelog_data));
                    Notify::developer(sprintf('事务【%s】处理失败', $transaction_name), $email_content);
                } else {
                    Notify::developer(sprintf('事务【%s】处理失败', $transaction_name));
                }
            }
        }
    }
}
System::continues();
?>