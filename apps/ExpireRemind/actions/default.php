<?php
use Landers\Substrate\Utils\Datetime;
use Landers\Framework\Core\System;
use Landers\Framework\Core\Response;

Response::note(['【实例即将到期提醒】（'.System::app('name').'）开始工作','#dbline']);

$before_days = Settings::get('instance_expire_before_days');

$begin = time();
$end = Datetime::add('days', $before_days, $begin);
$instances = Instance::lists([
    'awhere' => [sprintf('`expire` between %s and %s', $begin, $end)],
    'awhere' => ['mainipaddress' => '123.1.1.8']
]);
if (!$instances) {
    Response::note('暂无%s天内到期在实例', $before_days);
    System::continues();
}
Response::note(['#blank', '逐一对即将过期的实例进行相关操作...']);
foreach ($instances as $instance) {
    Response::note('#line');
    $instance_ip = $instance['mainipaddress'];
    $is_auto_renew = $instance['is_auto_renew'];
    response_instance_detail($instance);

    // dp(System::isDoneToday($instance['id']));

    if ( System::isDoneToday($instance['id']) ) {
        Response::note('此实例今天 (%s) 已通知提醒过。', date('Y-m-d'));
        continue;
    }


    //确定实例所属用户
    $uid = $instance['uid'];
    $user = User::get($uid);
    $user_moeny = $user['money'];
    response_user_detail($user);

    //是否试用
    if ( Instance::isTrial($instance) ) {
        Response::note('此实例处于试用期：%s，无需通知提醒。', date('Y-m-d H:i:s', $instance['trial_expire']));
        continue;
    }

    //确定实例及按月计费云盾每月所需费用
    $mitigation_info = Mitigation::find_ip($instance_ip);
    $price_mitigation = $mitigation_info ? $mitigation_info['price'] : 0;
    $fee_price = $instance['price'] +  $price_mitigation;

    //确定过期日期和过期天数
    $expire_date = date('Y-m-d H:i:s', $instance['expire']);
    $days = abs(Datetime::diff_now_days($instance['expire']));
    $email_content = [
        'instance_name' => $instance['hostname'],
        'instance_ip'   => $instance['mainipaddress'],
        'expire_date'   => $expire_date,
        'days'          => $days,
    ];

    //余额不足
    if ($is_auto_renew) { //自动续费
        if ($user_moeny < $fee_price) {
            Response::note('通知用户“云主机即将%s天后%s到期，余额不足以自动续费下个月”', $days, $expire_date);
            Notify::clientApi($uid, 'REMIND-NOT-ENOUGH-BALANCE-FOR-AUTO-RENEW', $email_content);
        } else {
            Response::note('余额足矣，无需通知');
        }
    } else { //手工续费
        if ($user_moeny < $fee_price) { //余额不足以支付下个月，需要提醒
            Response::note('通知用户“云主机即将%s天后%s到期，余额不足需充值，并手工续费”', $days, $expire_date);
            Notify::clientApi($uid, 'REMIND-NOT-ENOUGH-BALANCE-FOR-MANUAL-RENEW', $email_content);
        } else { //余额足够支付下个月，需要提醒
            Response::note('通知用户“云主机即将%s天后%s到期，余额足够，需手工续费”', $days, $expire_date);
            Notify::clientApi($uid, 'REMIND-ENOUGH-BALANCE-FOR-MANUAL-RENEW', $email_content);
        }
    }
}
System::continues();

?>