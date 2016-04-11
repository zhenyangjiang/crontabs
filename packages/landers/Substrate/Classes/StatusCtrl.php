<?php
namespace Landers\Substrate\Classes;
/**
 * 状态控制类
 * @author Landers
 */
class StatusCtrl {
    // private $statuses = array(
    //     'stat_key1'    => array(
    //         'value' => 'stat_value1',
    //         'text'  => '文本1',
    //         'color' => '颜色1',
    //         'action'=> array(
    //             'action_key1', 'action_key2'
    //         )
    //     ),
    //     'stat_key2'    => array(
    //         'value' => 'stat_value2',
    //         'text'  => '文本2',
    //         'color' => '颜色2',
    //         'action'=> array(
    //             'action_key3', 'action_key4'
    //         )
    //     ),
    // );
    // private $actions = array(
    //     'action_key1'    => array(
    //         'verify'    => 'stat_now_key',
    //         'status'     => 'stat_changeto_key',
    //         'text'      => '文本',
    //     )
    // );
    private $field, $statuses = array(), $actions = array();
    private $DAO; //数据访问对象，通常是Model类的实例对象
    public function __construct($DAO, $statuses, $actions, $field = 'status') {
        $this->DAO = $DAO;
        $this->statuses = $statuses;
        $this->actions = $actions;
        $this->field = $field;
    }

    public function __call($method, $args) {
        switch (count($args)) {
            case 0: return $this->DAO->$method();
            case 1: return $this->DAO->$method($args[0]);
            case 2: return $this->DAO->$method($args[0], $args[1]);
            case 3: return $this->DAO->$method($args[0], $args[1], $args[2]);
            case 4: return $this->DAO->$method($args[0], $args[1], $args[2], $args[3]);
            default: return call_user_func_array([$this->DAO, $method], $args);
        }
    }

    /**
     * 执行改变状态的动作
     * @param  int          $dat_id         数据ID
     * @param  string       $action_key     动作标识
     * @param  function     $callback       回调函数
     * @param  boolean      $is_force       是否强制
     * @return boolean
     */
    public function doaction($dat_id, $action_key, $is_force = false, $callback = NULL) {
        $src_status = $this->find($dat_id, $this->field);
        $action = $this->actions[$action_key];
        $dst_status = $action['status'];

        //源状态与目标状态一致，由$callback决定返回值
        if ($src_status == $dst_status) {
            if (!$callback) return true;
            else return $callback($dst_status);
        }

        //正常流程
        $verify_statuses = (array)$action['verify'];
        if ( $is_force || in_array($src_status, $verify_statuses) ) {
            $data = array($this->field => $dst_status);
            $awhere = array('id' => $dat_id);
            if (!$callback) {
                return $this->update($data, $awhere);
            } else {
                return $this->transact(function() use ($dst_status, $data, $awhere, $callback){
                    if (!$this->update($data, $awhere)) {
                        return false;
                    }
                    return $callback($dst_status);
                });
            }
        } else {
            return false;
        }
    }

    public function status($stat_key) {
        return $this->statuses[$stat_key]['text'];
    }
}