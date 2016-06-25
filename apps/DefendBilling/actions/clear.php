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

$ret = Mitigation::update(['status' => 0], true);
if ( $ret ){
    Response::note('所有云盾的status被更新为正常（NORMAL）');
} else {
    Response::note('所有云盾的status更新失败');
}




