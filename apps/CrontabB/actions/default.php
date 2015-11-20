<?php
use Landers\Utils\Datetime;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Log;

Log::note(['【实例到期后，实例挂起、待删除、自动续费】（'.System::app('name').'）开始工作','#dbline']);

$instances = Instance::timeout_expire();
if ($instances) {
    Log::note('共找到期的实例：%s台', count($instances));
} else {
    Log::note('当前暂无过期的实例主机');
    System::continues(); //continue;
}
Log::note(['#blank', '逐一对过期的实例进行相关操作...']);

foreach ($instances as $instance) {
    log::note('#line');
    $instance_ip = $instance['mainipaddress'];
    Log::instance_detail($instance);

    //确定实例所属用户
    $uid = $instance['uid'];
    $user = User::get($uid);
    Log::user_detail($user);

    //过期天数
    $expire_days = Datetime::diff_now_days($instance['expire']);
    $expire_date = date('Y-m-d H:i:s', $instance['expire']);
    log::note('此实例的过期日期：%s', $expire_date);

    //系统允许过期天数、剩余天数
    $allow_days = Settings::get('instance_timeout_days');
    $retain_days = $allow_days - $expire_days;

    $status_text = Instance::status($instance);
    if (!$instance['is_auto_renew']) {  //未设置自动续费
        //过期天数是否超过系统允许
        if ( $retain_days < 0 ) {
            //执行待删除操作
            log::note('已过期%s天，超过系统允许值%s天', $expire_days, $allow_days);
            if ( $instance['status'] == 'TODELETE' ) {
                Log::note('当前为%s状态，无需执行“待删除”操作', colorize($status_text, 'yellow'));
            } else {
                Log::note('当前为%s状态，需强制执行“待删除”操作', colorize($status_text, 'yellow'));
                $bool = Instance::change_status($instance, 'delete', NULL, true);
                if (!$bool) {
                    $msg = '实例更新为“待删除”失败';
                    log::error($msg);
                    Notify::developer($msg);
                } else {
                    Log::note('实例成功更新为“待删除”');
                }
            }
        } else {
            //执行挂起操作
            log::note('已过期第%s天，尚处于系统允许值%s天内，相关数据将继续保留%s天', $expire_days, $allow_days, $retain_days);
            if ( $instance['status'] == 'NORMAL' ) {
                Log::note('当前为%s状态，需执行挂起操作', colorize($status_text, 'yellow'));
                Instance::change_status($instance, 'suspend', function() use ($instance){
                    return Instance::suspend($instance['id']);
                });

                Log::note('实例未设置自动续费，将通知客户实例已被挂起，需充值后并手工续费');
                if (!Instance::check_is_notified($instance)) {
                    $error = Notify::client('instance_expire_retain_manual', $uid, [
                        'instance_name' => $instance['hostname'],
                        'instance_ip'   => $instance['mainipaddress'],
                        'expire_days'   => $expire_days,
                        'retain_days'   => $retain_days,
                    ]);
                    if (!$error) Instance::update_notify_time($instance);
                }
            } else {
                Log::note('当前为%s状态，无需执行挂起操作', colorize($status_text, 'yellow'));
            }
        }
    } else {    //已设置自动续费
        //续费费用
        $fee_renew = 0;

        //确定实例每月费用
        $price_instance = Instance::price($instance);
        $fee_renew += $price_instance;
        $balance1 = $user['money'] - $price_instance;

        //确定实例所绑定的云盾每月费用
        $mitigation_info = Mitigation::find_ip($instance_ip);
        if ($mitigation_info) {
            $price_mitigation = $mitigation_info['price'];
            $mitigation_ability = $mitigation_info['ability'];
            if ($price_mitigation) {
                $fee_renew += $price_mitigation;
                $balance2 = $balance1 - $price_mitigation;
            } else {
                $balance2 = $balance1;
            }
        }
        log::note('实例设置了自动续费，本次所需扣费明细如下：');
        if ( is_null($mitigation_info) ) {
            log::note('#tab主机：%s元/月，未购买云盾，共计：%s元', $price_instance, $fee_renew);
        } else {
            log::note('#tab主机：%s元/月，与之绑定的云盾%sGbps：%s/月，共计：%s元', $price_instance, $mitigation_ability, $price_mitigation, $fee_renew);
        }

        log::note('#blank');
        if ($balance2 < 0) {
            //过期天数是否超过系统允许
            if ( $retain_days < 0 ) {
                log::note('此实例已过期%s天，超过系统允许值%s天', $expire_days, $allow_days);
                log::note('用户余额不足，无法自动续费，将通知后台人员对实例进行销毁'); //请您尽快充值后，系统将自动解除挂起
            } else {
                $bool = Instance::suspend($instance['uid']);
                if ($bool) {
                    Log::note('用户%s的实例%s(%s)被成功挂起', $user['user_name'], $instance['hostname'], $instance_ip);
                    //通知客户充值
                    Notify::client('instance_expire_retain_auto', $uid, [
                        'instance_name' => $instance['hostname'],
                        'instance_ip'   => $instance['mainipaddress'],
                        'expire_days'   => $expire_days,
                        'retain_days'   => $retain_days,
                    ]);
                } else {
                    $msg = '实例挂起失败';
                    log::error($msg);
                    Notify::developer($msg);
                }
            }
        } else {
            //实例扣费日志数据包
            $log_instance_title = '实例：%s，IP：%s，%s自动续费';
            $feelog_instance_data = [
                'balance' => $balance1,
                'instance_ip' => $instance_ip,
                'uid' => $uid,
                'amount' => $price_instance,
                'title' => sprintf($log_instance_title, $instance['hostname'], $instance_ip, date('Y-m')),
            ];

            //云盾扣费日志数据包
            if ($mitigation_info) {
                $log_mitigation_title = '实例：%s，IP：%s的云盾%sGbps，%s自动续费';
                $feelog_mitigation_data = [
                    'balance' => $balance2,
                    'instance_ip' => $instance_ip,
                    'uid' => $uid,
                    'amount' => $price_mitigation,
                    'title' => sprintf($log_mitigation_title, $instance['hostname'], $instance_ip, $mitigation_ability, date('Y-m')),
                ];
            } else {
                $feelog_mitigation_data = NULL;
            }

            $transaction = '实例扣费、实例扣费日志、延长实例有效期、云盾扣费、云盾扣费日志';
            log::note('执行事务处理：%s：', $transaction);
            $bool_transact = User::transact(function() use ($uid, $instance, $feelog_instance_data, $feelog_mitigation_data) {
                //实例扣费
                $balance1 = $feelog_instance_data['balance'];
                if (User::set_money($uid, $balance1)) {
                    log::note('#tab实例扣费成功');
                } else {
                    log::warn('#tab实例扣费失败');
                    return false;
                }

                //实例扣费日志
                if ( Feelog::pay_instance($feelog_instance_data) ) {
                    log::note('#tab实例扣费日志写入成功');
                } else {
                    log::warn('#tab实例扣费日志写入失败');
                    return false;
                }

                //延长实例有效期
                $expire = strtotime("+1 month", $instance['expire']);
                if (Instance::update(['expire' => $expire], ['id' => $instance['id']])) {
                    log::note('#tab实例有效期更新为%s', date('Y-m-d H:i:s', $expire));
                } else {
                    log::warn('#tab实例新有效期更新失败');
                    return false;
                }

                if ($feelog_mitigation_data) {
                    //云盾扣费
                    $balance2 = $feelog_mitigation_data['balance'];
                    if (User::set_money($uid, $balance2)) {
                        log::note('#tab云盾扣费成功');
                    } else {
                        log::warn('#tab云盾扣费失败');
                        return false;
                    }

                    //实例扣费日志
                    if ( Feelog::pay_mitigation($feelog_mitigation_data) ) {
                        log::note('#tab云盾扣费日志写入成功');
                    } else {
                        log::warn('#tab云盾扣费日志写入失败');
                        return false;
                    }
                } else {
                    log::note('#tab未购买云盾，无需云盾费用');
                }

                return true;
            });

            if ( $bool_transact ) {
                log::note('#tab事务成功完成');
            } else {
                $email_content = '实例扣费日志数据包：<br>%s<br>云盾扣费日志数据包：<br>%s';
                $email_content = sprintf($email_content, Arr::to_html($feelog_instance_data), Arr::to_html($feelog_mitigation_data));
                Notify::developer(sprintf('事务处理失败：%s', $transaction), $email_content);
            }
        }
    }
}
System::continues(); //continue;

?>