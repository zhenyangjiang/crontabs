<?php
use Landers\Utils\Arr;
use Landers\Framework\Core\Log;

function deduct_transact($uid, $instance_update, $feelog_data, $callbacks = array()) {
    return User::transact(function() use ($uid, $instance_update, $feelog_data) {
        //实例扣费
        $balance_instance = $feelog_data['balance'];
        if (User::set_money($uid, $balance_instance)) {
            log::note('#tab实例扣费成功');
        } else {
            log::warn('#tab实例扣费失败');
            return false;
        }

        //实例扣费日志
        if ( Feelog::create($feelog_data) ) {
            log::note('#tab实例扣费日志写入成功');
        } else {
            log::warn('#tab实例扣费日志写入失败');
            return false;
        }

        //更新实例
        if (Instance::update($instance_update['data'], $instance_update['awhere'])) {
            log::note('#tab实例有效期更新为%s', date('Y-m-d H:i:s', $instance_update['data']['expire']));
        } else {
            log::warn('#tab实例新有效期更新失败');
            return false;
        }

        foreach ($callbacks as $callback) {
            if (!$callback())  return false;
        }
        return true;
    });
}


function suspend_transact($instance, $user, $some_days, $callback) {
    log::note('#tab已过期第%s天，尚处于系统允许值%s天内，相关数据将继续保留%s天', $some_days['expire'], $some_days['allow'], $some_days['retain']);

    $bool_transact = NULL;

    //执行挂起实例
    $status_text = Instance::status($instance);
    if ($instance['status'] !== 'SUSPENDED') {
        Log::note('#tab当前为%s状态，需执行挂起操作和云盾降级', colorize($status_text, 'yellow'));
        $bool_transact = Mitigation::transact( function() use ( $instance, $user ) {
            //降级云盾
            if ( !Mitigation::down_grade($instance) ) {
                return false;
            }

            //取消自动续费
            if ( !Instance::update([
                'is_auto_renew' => 0
            ], [
                'id' => $instance['id']
            ]) ) {
                return false;
            }

            //挂起实例
            if ( !Instance::suspend($instance) ) {
                $msg = '实例挂起失败';
                log::error("#tab$msg");
                Notify::developer($msg);
                return false;
            } else {
                Log::note('#tab用户“%s”的实例“%s(%s)”被成功挂起', $user['user_name'], $instance['hostname'], $instance['mainipaddress']);
            }

            //执行回调（通常为通知客户充值）
            $callback && $callback();

            return true;
        });
    } else {
        Log::note('#tab当前为%s状态，无需执行挂起操作', colorize($status_text, 'yellow'));
    }
    return $bool_transact;
}

function destroy_instance($instance, $some_days) {
    log::note('此实例已过期%s天，超过系统允许值%s天，执行销毁实例', $some_days['expire'], $some_days['allow']);
    $bool = Instance::destroy($instance);
    Log::noteSuccessFail('实例销毁%s！', $bool);
    if (!$bool) {
        Notify::developer('实例销毁失败！', Arr::to_html($instance));
    }
}