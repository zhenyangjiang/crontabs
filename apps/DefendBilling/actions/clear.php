<?php
// use Ulan\Modules\ddosinfo;
// use Ulan\Modules\history;
// use Ulan\Modules\blackhole;
// use Ulan\Modules\instance;
// use Ulan\Modules\log;

$modules = ['ddosinfo', 'history', 'blackhole'];
foreach ($modules as $module) {
    call_user_func([$module, 'truncate']);
}
Response::note('%s清空完成', implode('、', $modules));

$ret = Instance::update(['net_state' => 0, 'net_state_updtime' => NULL], true);
if ( $ret ){
    Response::note('所有实列的net_state被更新为正常（0）');
} else {
    Response::note('实例的net_state更新失败');
}




