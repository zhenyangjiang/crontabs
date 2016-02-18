<?php
use Landers\Utils\Arr;
use Landers\Framework\Core\Response;

function deduct_transact($uid, $instance_update, $feelog_data, $callbacks = array()) {
    return User::transact(function() use ($uid, $instance_update, $feelog_data, $callbacks) {
        //用户扣费
        $balance_instance = $feelog_data['balance'];
        $bool = User::set_money($uid, $balance_instance);
        Response::noteSuccessFail('#tab实例扣费%s', $bool);
        if (!$bool) return false;

        return Instance::transact(function() use ($uid, $instance_update, $feelog_data, $callbacks) {
            // 实例扣费日志
            $bool = Feelog::create($feelog_data);
            Response::noteSuccessFail('#tab实例扣费日志写入%s', $bool);
            if ( !$bool ) return false;

            // 更新实例
            if (Instance::update($instance_update['data'], $instance_update['awhere'])) {
                Response::note('#tab实例有效期更新为%s', date('Y-m-d H:i:s', $instance_update['data']['expire']));
            } else {
                Response::warn('#tab实例新有效期更新失败');
                return false;
            }

            if ($callbacks) {
                foreach ($callbacks as $callback) {
                    if (!$callback())  return false;
                }
            }
            return true;
        });
    });
}


function suspend_transact($instance, $user, $some_days, $callback) {
    Response::note('#tab已过期第%s天，尚处于系统允许值%s天内，相关数据将继续保留%s天', $some_days['expire'], $some_days['allow'], $some_days['retain']);

    $bool_transact = NULL;

    //执行挂起实例
    $status_text = Instance::status($instance);
    if ($instance['status'] !== 'SUSPENDED') {
        Response::note('#tab当前为%s状态，需执行云盾降级和挂起操作', colorize($status_text, 'yellow'));
        $bool_transact = Mitigation::transact( function() use ( $instance, $user ) {
            //降级云盾
            $bool = Mitigation::down_grade($instance);
            Response::noteSuccessFail('#tab云盾强制降级%s', $bool);
            if ( !$bool) return false;

            //取消自动续费
            $bool = Instance::update(
                ['is_auto_renew' => 0 ],
                ['id' => $instance['id']]
            );
            Response::noteSuccessFail('#tab自动续费取消%s', $bool);
            if ( !$bool) return false;

            //挂起实例
            if ( !Instance::suspend($instance) ) {
                $msg = '实例挂起失败';
                Response::error("#tab$msg");
                Notify::developer($msg);
                return false;
            } else {
                Response::note('#tab用户“%s”的实例“%s(%s)”被成功挂起', $user['user_name'], $instance['hostname'], $instance['mainipaddress']);
            }

            //执行回调（通常为通知客户充值）
            $callback && $callback();

            return true;
        });
    } else {
        Response::note('#tab当前为%s状态，无需执行挂起操作', colorize($status_text, 'yellow'));
    }
    return $bool_transact;
}

function destroy_instance($instance, $some_days) {
    Response::note('#tab实例已过期%s天，超过系统允许值%s天，执行销毁实例', $some_days['expire'], $some_days['allow']);
    $bool = Instance::destroy($instance);
    Response::noteSuccessFail('#tab实例销毁%s！', $bool);
    if (!$bool) {
        Notify::developer('实例销毁失败', Arr::to_html($instance));
    }
}