<?php
use Landers\Substrate\Utils\Arr;
use Landers\Framework\Core\Response;

function renew_transact($uid, $instance, $instance_update, $feelog_data, $callbacks = array()) {
    $result = User::transact(function() use ($uid, $instance, $instance_update, $feelog_data, $callbacks) {
        //用户扣费
        Response::note('#tab实例扣费...');
        $bool = User::pay_money($uid, $feelog_data['amount']);
        if ($bool) {
            Response::echoSuccess('成功扣费 %s', $feelog_data['amount']);
        } else {
            Response::bool(false);
            return false;
        }

        return Instance::transact(function() use ($uid, $instance, $instance_update, $feelog_data, $callbacks) {
            // 实例扣费日志
            Response::note('#tab写入实例扣费日志...');
            $bool = Feelog::create($feelog_data);
            Response::echoBool($bool);
            if ( !$bool ) return false;

            // 更新实例
            Response::note('#tab更新实例新有效期...');
            if (Instance::update($instance_update, ['id' => $instance['id']])) {
                Response::echoSuccess(date('Y-m-d H:i:s', $instance_update['expire']));
            } else {
                Response::echoBool(false);
                return false;
            }

            Response::note('#tab反挂起实例...');
            $bool = Instance::unsuspend($instance);
            Response::echoBool($bool);
            if (!$bool) return false;

            Notify::clientApi($uid, 'HANDLE-EXPIRE-SUCCESS-FOR-AUTO-RENEW', [
                'instance_name' => $instance['hostname'],
                'old_expire'    => date('Y-m-d H:i:s', $instance['expire']),
                'new_expire'    => date('Y-m-d H:i:s', $instance_update['expire']),
                'renew_money'   => $feelog_data['amount'],
            ]);

            if ($callbacks) {
                foreach ($callbacks as $callback) {
                    if (!$callback())  return false;
                }
            }

            return true;
        });
    });

    return Response::transactEnd($result);
}


function suspend_transact($instance, $user, $some_days, $callback) {
    Response::note('#tab已过期第%s天，尚处于系统允许值%s天内，相关数据将继续保留%s天', $some_days['expire'], $some_days['allow'], $some_days['retain']);

    //执行挂起实例
    $status_text = Instance::status($instance);
    if ($instance['status'] !== 'SUSPENDED') {
        Response::note('#tab当前为%s状态，需执行云盾降级和挂起操作', colorize($status_text, 'yellow'));
        $result = Mitigation::transact( function() use ( $instance, $user, $callback ) {
            //降级云盾
            Response::note('#tab强制降级云盾为免费方案...');
            $bool = Mitigation::down_grade($instance);
            Response::echoBool($bool);
            if ( !$bool) return false;

            //取消自动续费
            if ( $instance['is_auto_renew'] ) {
                Response::note('#tab取消自动续费...');
                $bool = Instance::update(
                    ['is_auto_renew' => 0 ],
                    ['id' => $instance['id']]
                );
                Response::echoBool($bool);
                if ( !$bool) return false;
            }

            //挂起实例
            Response::note('#tab挂起实例...');
            if ( !Instance::suspend($instance) ) {
                Response::echoBool(false);
                Notify::developer($msg);
                return false;
            } else {
                Response::echoBool(true);
            }

            //执行回调（通常为通知客户充值）
            $callback && $callback();

            return true;
        });

        return Response::transactEnd($result);
    } else {
        Response::note('#tab当前为%s状态，无需执行挂起操作', colorize($status_text, 'yellow'));

        //执行回调（通常为通知客户充值）
        $callback && $callback();

        return NULL;
    }
}

function destroy_instance($instance, $some_days) {
    Response::note('#tab实例已过期%s天，超过系统允许值%s天，执行销毁实例', $some_days['expire'], $some_days['allow']);
    if ( ENV_debug === true ) {
        Response::note('调试开启：执行虚拟销毁');
    } else {
        $bool = Instance::destroy($instance);
        Response::bool($bool, '#tab实例销毁%s！');
        if (!$bool) {
            Notify::developer('实例销毁失败', sprintf('<pre>%s</pre>', var_export($instance, true)));
        }
    }
}