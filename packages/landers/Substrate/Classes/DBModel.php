<?php
namespace Landers\Substrate\Classes;

use Landers\Substrate\Utils\Arr;
use Landers\Substrate\Utils\Str;
use Landers\Substrate\Utils\Json;
use Landers\Substrate\Utils\Verify;


/**
 * 数据模型基础类
 * @author Landers
 */
Class DBModel {
    public $db, $dt, $name;
    public $SQL, $fields;
    public $debug = false;
    public $field_id = 'id';
    public $field_key = 'key';

    private $errors = array();

    private static $models = array();
    public static function make($db, $dt) {
        $key = md5($dt);
        $model = Arr::get(self::$models, $key);
        if ($model) return $model;
        $models[$key] = new self($db, $dt);
        return $models[$key];
    }

    public function __construct($db, $dt, $name = '记录', $dt_parter = NULL) {
        $this->db           = $db;
        $this->dt           = $dt;
        $this->name         = $name;
        $this->SQL          = new SQL($db, $this->dt, $dt_parter);
        $this->fields       = &$this->SQL->fields;
    }

    /**
     * 开启调试
     */
    public function debug($enable = true){
        $this->debug = $enable;
        return $this;
    }

    /**
     * 显示调试信息
     * @param  string  $sql      [description]
     * @param  boolean $is_debug [description]
     */
    private function show_debug($sqls) {

        if ( $this->debug) {
            $msg = ''; $sqls = (array)$sqls;
            $this->db->transact(function() use ($sqls, &$msg){
                $msg = array();
                foreach ($sqls as $sql) {
                    $ret = $this->db->execute($sql);
                    $msg[] = PHP_EOL.( $ret === false ? '【ERROR】' : '【OK】').$sql.PHP_EOL.PHP_EOL;
                }
                return false;
            });
            $is_ajax = class_exists(Request::class) && Request::is_ajax();
            if ($is_ajax) ob_start();
            if (function_exists('dp')) {
                $msg[] = $this->db->error();
                $msg[] = $this->db->errno();
                dp($msg, !$is_ajax, 4);
            } else {
                print_r($msg); if ($is_ajx) exit();
        }
            if ($is_ajax) {
                $message = ob_get_contents(); ob_end_clean();
                ApiResult::make()->debug($message)->output();
            }

        }
    }

    /**
     * 保存错误信息
     * @param  string $key      键名
     * @param  string $text     错误文本
     * @param  string $sql      sql语句
     * @return none
     */
    private function set_errors($key, $sql = NULL, $error = NULL) {
        $errors = &$this->errors;
        if (!array_key_exists($key, $errors)) $errors[$key] = array();
        if ($sql) {
            $error or $error = $this->db->error();
            $errno = $this->db->errno();
        } else {
            $errno = '';
        }
        $errors[$key][] = array('sql' => $sql, 'error' => $error, 'errno' => $errno);
    }

    /**
     * 取得错误信息
     * @param  string      $key      键名
     * @return string
     */
    public function get_errors($key = NULL, $index = NULL, $attach_no = true) {
        $errors = &$this->errors;
        if (!$key) return $errors;
        $errors = $errors[$key];
        if (!$errors) return $index ? '' : array();
        foreach ($errors as $i => &$item) {
            $item = ($attach_no ? ($i+1) . '. ' : ''). Arr::get($item, 'error');
        }; unset($item);
        if (!$index) return implode("\n", $errors);
        $ret = $errors[$key][$index];
        unset($errors[$key]);
        return $ret;
    }

    /**
     * 过滤掉数据表不存在的字段
     * @param  string     $fields    字段列表
     * @return string
     */
    public function filter_fields($fields){
        $fields = Str::split($fields);
        foreach ($fields as $i => $item) {
            if (!array_key_exists($item, $this->fields)) unset($fields[$i]);
        }
        return implode(',', $fields);
    }

    /**
     * 解析单参数或多参数时opts的值
     * @return array
     */
    public function parse_opts(array $args) {
        if (count($args) == 1) {
            if (is_array($args[0])) {
                $opts = &$args[0];
            } else {
                $opts = array(
                    'awhere'     => $args[0],
                    'fields'     => NULL,
                    'order'      => NULL,
                    'owhere'     => array(),
                    'group'      => NULL,
                    'unions'     => array(),
                );
            }
        } else {
            $opts = array(
                'awhere'     => Arr::get($args, 0),
                'fields'     => Arr::get($args, 1),
                'order'      => Arr::get($args, 2),
                'owhere'     => Arr::get($args, 3),
                'group'      => Arr::get($args, 4),
                'unions'     => Arr::get($args, 4),
            );
        }
        $_awhere = Arr::get($opts, 'awhere');
        $opts['awhere'] = $this->build_where_key_id($_awhere);
        return $opts;
    }

    /**
     * 取得数据列表
     * @param  array   $opts   选项 (is_page, fields, awhere, owhere, order, limit, group, unions)
     * @return array           列表 / NULL
     */
    public function lists($opts = array()) {
        return Arr::get($opts, 'is_page') ?  $this->listpage($opts) : $this->listall($opts);
    }

    /**
     * 取得全部列表数据（无分页）
     * @param  array    $opts     选项 (fields, awhere, owhere, order, limit, group, debug, unions)
     * @return array              列表 / NULL
     */
    public function listall($opts = array()) {
        $_awhere = Arr::get($opts, 'awhere');
        if (!$this->check_data('lists', $_awhere)) {
            return NULL;
        }
        $sql = $this->SQL->SelectSQL($opts);
        $this->show_debug($sql);
        $ret = $this->db->query($sql);
        if ( $ret === false ) {
            $this->set_errors('lists', $sql);
            return NULL;
        }
        if ($askey = Arr::get($opts, 'askey')) {
            $ret = Arr::rekey($ret, $askey);
        }
        return  $ret;
    }

    /**
     * 取得符合条件的ids
     * @param  array       $opts       选项 ( awhere, owhere, order, unions)
     * @return array                   ids数组
     */
    public function ids($opts = array()) {
        $opts['fields'] = $this->field_id;

        //取得所有记录的id
        $data = $this->listall($opts);

        if (is_null($data)) return NULL;
        else return Arr::flat($data);
    }

    /**
     * 取得符合条件的ids
     * @param  array       $opts       选项 ( awhere, owhere, order, unions)
     * @return array                   keys数组
     */
    public function keys($opts = array()) {
        $opts['fields'] = $this->field_key;

        //取得所有记录的id
        $data = $this->listall($opts);
        if (is_null($data)) return NULL;
        else return Arr::flat($data);
    }

    /**
     * 获取分页列表
     * @param  array  $opts  选项 (fields, awhere, owhere, order, limit, group, paesize, page, unions)
     * @return array         array(数据数组，分页数组) / NULL
     */
    public function listpage($opts = array()) {
        $opts['debug'] = Arr::get($opts, 'debug');

        //取得符合条件的ids
        $ids = $this->ids($opts);
        if (is_null($ids)) return NULL;

        //据ids找出分页条件
        $pagesize = Arr::get($opts, 'pagesize');
        $page = Arr::get($opts, 'page');

        $pager = new Pager($pagesize, $page);
        $ids = $pager->split($ids);
        $page = $pager->property();

        if ($ids) {
            //重置opts
            $keyid = $this->field_id;
            $opts = array(
                'fields'    => Arr::get($opts, 'fields'),
                'awhere'    => array($keyid => $ids),
                'order'     => "INSTR(',".implode(',', $ids).",',CONCAT(',',".$keyid.",','))"
            );

            //再次调用loadall
            $data = $this->listall($opts);
        } else {
            $data = array();
        }

        return array($data, $page);
    }

    /**
     * 找出一条记录
     * @return array/mixed
     */
    public function find() {
        $args = func_get_args();
        $opts = $this->parse_opts($args);
        $opts['limit']  = 1;

        $sql = $this->SQL->SelectSQL($opts);
        $this->show_debug($sql);
        $ret = $this->db->query_one($sql);
        if ( $ret === false ) {
            $this->set_errors(__FUNCTION__,  $sql);
            return NULL;
        }
        if (!$ret) return array();
        else return count($ret) > 1 ? $ret : pos($ret);
    }

    /**
     * 取得单条记录信息
     * @param  [type] $xvar   [description]
     * @param  [type] $fields [description]
     * @return [type]         [description]
     */
    public function info($xvar, $fields = NULL) {
        if (is_array($xvar)) {
            $ret = $fields ? Arr::slice($xvar, $fields) : $xvar;
            return count($ret) > 1 ? $ret : pos($ret);
        } else {
            return self::find($xvar, $fields);
        }
    }

    /**
     * 数据包合法性检查
     * @param  array      $data     数据包
     * @return boolean
     */
    private function check_data($error_key, $data) {
        if (!$data) return true;
        $error = NULL; $ret = true;
        foreach ($data as $name => $value) {
            if (is_numeric($name)) continue;
            if (is_array($value)) continue;
            $error = NULL;
            $info = $this->fields[$name];
            $label = $info['text'] or $label = $info['comment'];
            $length = (int)$info['length'];
            $default = $info['default'];
            $isnull = $info['isnull'] === 'NULL';
            if ($isnull && !$value) continue;
            if (!$isnull && !$value && $default) continue;
            switch ($info['type']){
                case 'VARCHAR':; case 'CHAR':;
                    $my_length = strlen($value);
                    if ($my_length > $length) {
                        $error = sprintf("%s【%s】长度%s字节,已超过%s字节！", $label, $value, $my_length, $length);
                    }; break;
                case 'TINYINT':; case 'SMALLINT':; case 'MEDIUMINT':;
                case 'INT':; case 'BIGINT':; case 'FLOAT':; case 'DOUBLE':;
                case 'DECIMAL':; case 'YEAR':;
                    if (!is_numeric($value) && strpos($value, "'") !== false) {
                        $error = sprintf("%s【%s】必须为数字型！", $label, $value);
                    }; break;
                case 'DATETIME':; case 'TIMESTAMP':
                    if ( !Verify::is_datetime($value) ) {
                        $error = sprintf("%s【%s】日期时间格式不对！", $label, $value);
                    }; break;
                case 'DATE':;
                    if ( !Verify::is_date($value) ) {
                        $error = sprintf("%s【%s】日期格式不对！", $label, $value);
                    }; break;
                case 'TIME':;
                    if ( !Verify::is_time($value) ) {
                        $error = sprintf("【%s】：%s\n时间格式不对！", $text, $value);
                    }; break;
            }
            if ($error) {
                $this->set_errors($error_key, '', $error);
                $ret = false;
            }
        }
        return $ret;
    }

    /**
     * 转换成明确的awhere条件
     * @param  array/string/int     $where_key_id   操作条件 (where条件 / key/ id)
     * @return array
     */
    public function build_where_key_id($where_key_id) {
        if (is_array($where_key_id)) {
            return $where_key_id;

        } elseif (is_numeric($where_key_id)) {
            return [$this->field_id => $where_key_id];

        } elseif (is_string($where_key_id)) {
            return [$this->field_key => $where_key_id];

        } else {
            return array();
        }
    }

    /**
     * [check_uniques description]
     * @param  array        $data       数组包
     * @param  array        $uniqus     唯一值字段列表
     * @return boolean
     */
    public function check_uniques(array $data, array $uniques, $except_id  = NULL) {
        if ($uniques) {
            $bool = false;
            foreach ($uniques as $items) {
                if (!is_array($items)) $items = (array)$items;
                $awhere = Arr::slice($data, $items);
                if ($except_id) $awhere[$this->field_id] = $except_id;
                $count = $this->count($awhere);
                if ($count) {
                    $texts = array();
                    foreach ($items as $item) {
                        $text = $this->fields[$item]['text'];
                        $texts[] = sprintf('【%s = %s】', $text, $data[$item]);
                    }
                    $texts = implode('且', $texts);
                    $error = sprintf('已存在%s的%s', $texts, $this->name);
                    $this->set_errors(__FUNCTION__, '', $error);
                    $bool = true;
                }
            }
            if ($bool) debug($this->get_errors(__FUNCTION__));
            return false;
        } else {
            return true;
        }
    }

    /**
     * 插入新记录
     * @param  array  $data       数组包
     * @param  array  $uniqus     唯一值字段列表
     * @return Int                新记录ID
     */
    public function insert(Array $data, $uniques = array()){
        if (!$data) return NULL;

        //是否存在重复值记录
        if ( !$this->check_uniques($data, $uniques)) return NULL;

        //检查数据合法性
        if ( !$this->check_data('create', $data) ) return NULL;

        //插入记录
        $sql = $this->SQL->InsertSQL($data);
        $this->show_debug($sql);
        $bool = $this->db->execute($sql);
        if (!$bool) {
            $this->set_errors('create',  $sql);
            return NULL;
        }

        $ret = $this->db->newid();
        return $ret == 0 ? true : $ret;
    }

    //insert的别名
    public function create(Array $data, $uniques = array()) {
        return $this->insert($data, $uniques);
    }

    /**
     * 批量插入数据
     * @param  array    $datas      二唯数组包
     * @return array                新记录ids
     */
    public function insert_batch($datas) {
        $ret = array(); $method = __FUNCTION__;
        $this->db->transact(function() use ($datas, &$ret, $method){
            foreach ($datas as $data){
                $newid = $this->insert($data);
                if (!$newid) {
                    $errors = $this->get_errors('create');
                    $this->set_errors($method,  $errors[count($errors)-1]['sql']);
                    $ret = array();
                    return false;
                } else {
                    $ret[] = $newid;
                }
            };
        });
        return $ret;
    }

    /**
     * 批量导入数据
     * @param  array      $pack      二唯数组包
     * @return boolean               成功与否
     */
    public function import($pack) {
        if (is_string($pack)) $pack = json_decode($pack, true);
        if (!$pack) return NULL;
        $sql = $this->SQL->ImportSQL($pack);
        $this->show_debug($sql);

        $bool = $this->db->execute($sql);
        if (!$bool) {
            $this->set_errors(__FUNCTION__,  $sql);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 取得数量
     * @param  array    $awhere     条件
     * @param  array    $opts       选项 (debug, owhere, group, unions)
     * @return int
     */
    public function count($awhere = array(), array $opts = array()) {
        $opts['awhere'] = $awhere;
        $sql = $this->SQL->CountSQL($opts);
        $this->show_debug($sql);
        return $this->db->query_count($sql);
    }

    /**
     * 计算总和
     * @param  array    $awhere     条件
     * @param  array    $opts       选项 (debug, owhere, group, unions)
     * @return int
     */
    public function sum($field, array $awhere = array(), array $opts = array()) {
        $opts['awhere'] = $awhere;
        $sql = $this->SQL->SumSQL($field, $opts);
        $this->show_debug($sql);
        return $this->db->query_count($sql);
    }

    /**
     * 计算平均值
     * @param  array    $awhere     条件
     * @param  array    $opts       选项 (debug, owhere, group, $unions)
     * @return int
     */
    public function avg($field, array $awhere = array(), array $opts = array()) {
        $opts['awhere'] = $awhere;
        $sql = $this->SQL->AvgSQL($field, $opts);
        $this->show_debug($sql);
        return $this->db->query_count($sql);
    }

    /**
     * 统计最大
     * @param  array    $awhere     条件
     * @param  array    $opts       选项 (debug, owhere, group, unions)
     * @return int
     */
    public function max($field, array $awhere = array(), array $opts = array()) {
        $opts['awhere'] = $awhere;
        $sql = $this->SQL->MaxSQL($field, $opts);
        $this->show_debug($sql);
        return $this->db->query_count($sql);
    }

    /**
     * 统计最小
     * @param  array    $awhere     条件
     * @param  array    $opts       选项 (debug, owhere, group)
     * @return int
     */
     public function min($field, array $awhere = array(), array $opts = array()) {
        $opts['awhere'] = $awhere;
        $sql = $this->SQL->MinSQL($field, $opts);
        $this->show_debug($sql);
        return $this->db->query_count($sql);
    }

    /**
     * 更新记录
     * @param  array                $data                   更新数据包
     * @param  array/string/id      $awhere_key_id          并条件 (where条件/key/id)
     * @param  array                $opts                   选项 (opts)
     * @return boolean
     */
    public function update(array $data, $awhere_key_id = array(), array $opts = array()) {
        $awhere = $this->build_where_key_id($awhere_key_id);
        $owhere = Arr::get($opts, 'owhere', array());
        $unions = Arr::get($opts, 'unions', array());

        //data项中如果有数组数据，转换成json编码
        foreach($data as &$item) {
            if (is_array($item)) $item = Json::encode($item);
        }; unset($item);

        //检查数据合法性
        if ( !$this->check_data(__FUNCTION__, $data) ) {
            return false;
        }

        //更新
        $sqls = $this->SQL->UpdateSQL($data, $awhere, $owhere, $unions);
        $this->show_debug($sqls);
        $bool = $this->db->querys($sqls);
        if (!$bool) {
            $this->set_errors(__FUNCTION__,  $sqls);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 对某字段值置为相反
     * @param  array/string/int     $where_key_id       并条件 (where条件/key/id)
     * @param  string               $field              字段名
     * @return boolean
     */
    public function opposite($awhere_key_id, $field, $unions = array()){
        $awhere = $this->build_where_key_id($awhere_key_id);
        $data = array($field => "not `$field`");
        return $this->update($data, $awhere, compact('unions'));
    }

    /**
     * 对某字段增加数量
     * @param  array/string/int     $where_key_id       并条件 (where条件/key/id)
     * @param  string               $field              字段名
     * @return int                                      增加后的数量
     */
    public function increase($awhere_key_id, $field, $amount = 1){
        $awhere = $this->build_where_key_id($awhere_key_id);
        $data = array($field => "`$field` + $amount");
        $bool =  $this->update($data, $awhere);
        if ( !$bool ) return false;
        return $this->find($awhere, $field);
    }

    /**
     * 对某字段减少数量
     * @param  array/string/int     $where_key_id       并条件 (where条件/key/id)
     * @param  string               $field              字段名
     * @return int                                      减少后的数量
     */
    public function decrease($awhere_key_id, $field, $amount = 1){
        $awhere = $this->build_where_key_id($awhere_key_id);
        $data = array($field => "`$field` - $amount");
        $bool =  $this->update($data, $awhere);
        if ( !$bool ) return false;
        return $this->find($awhere, $field);
    }

    /**
     * 修改单条记录
     * @param  array/string/int     $awhere_key_id  并条件 (where/key/id)
     * @param  array                $data           数据包
     * @param  array                $opts           选项（awhere:额外限制条件, uniques:不能重复的字段列表 (有待开发))
     * @return boolean
     */
    public function modify($awhere_key_id, array $data, array $opts = array()) {
        $extra_where = Arr::get($opts, 'awhere', array());
        $uniques = Arr::get($opts, 'uniques', array());
        $awhere = $this->build_where_key_id($awhere_key_id);
        $awhere = array_merge($awhere, $extra_where);
        $bool = $this->update($data, $awhere, compact('uniques'));
        if (!$bool) {
            $this->set_errors(__FUNCTION__,  '', $this->get_errors('update', 0, false));
            return false;
        } else {
            return true;
        }
    }

    /**
     *  删除记录
     * @param  array/string/int     $where_key_id   并条件 (where条件/key/id)
     * @param  array                $opts           选项（owhere, unions)
     * @return boolean
     */
    public function delete($awhere_key_id, $opts = array()) {
        $awhere = $this->build_where_key_id($awhere_key_id);
        $owhere = Arr::get($opts, 'owhere', array());
        $unions = Arr::get($opts, 'unions', array());
        $sqls = $this->SQL->DeleteSQL($awhere, $owhere, $unions);
        $this->show_debug($sqls);
        $bool = $this->db->querys($sqls);
        if (!$bool) {
            $this->set_errors(__FUNCTION__,  $sqls);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 清空数据表
     * @return bool
     */
    public function clear(array $unions = array()){
        $sqls = $this->SQL->TruncateSQL($unions);
        $this->show_debug($sqls);
        return $this->db->querys($sqls);
    }

    /**
     * 拉取最后N条记录
     * @param $n
     * @param $fields
     * @param $LastId
     */
    public function pullLast($n, $opts = array(), $ignore = false, $action_key = NULL) {
        $action_key or $action_key = md5( $this->dt.date('Y-m'));
        $last_id = $ignore ? 0 : (int)Arr::get($_COOKIE, $action_key);
        $awhere = array_merge((array)$opts['awhere'], ["id>$last_id"]);
        $opts = array_merge($opts, [
            'awhere'=> $awhere,
            'limit' => $n,
            'order' => 'id desc',
        ]);
        $list = $this->lists($opts);
        if ($list) {
            setcookie($action_key, $list[0]['id'], time() + 3600 * 24 * 7, '/');
        }
        return $list;
    }

    /**
     * 同步数据表与$datas，（不可以全删除重建）
     * @param  array      $awhere     基础条件
     * @param  [type]     $datas      数据包
     * @return array
     */
    public function sync($datas, $base_awhere) {
        $datas or $datas = array(); $ids = array(); $keyid = $this->field_id;
        $c_insert = 0; $c_update = 0; $c_delete = 0;
        $this->db->transact(function() use ($base_awhere, $datas, &$c_insert, &$c_update, &$c_delete){
            foreach($datas as $data) {
                $id = (int)$data[$keyid];
                unset($data[$keyid]);
                if ($id) {
                    $awhere = array_slice($base_awhere, 0);
                    $awhere[$keyid] = $id;
                    $count = $this->count($awhere);
                    if ($count) { //更新
                        if ($this->update($data, $awhere)) {
                            $c_update++; $ids[] = $id;
                        } else {
                            return false;
                        }
                    } else { //新增
                        if ($newid = $this->insert(NULL, $data)) {
                            $c_insert++; $ids[] = $newid;
                        } else {
                            return false;
                        }
                    }
                } else { //新增
                    if ($newid = $this->insert(NULL, $data)) {
                        $c_insert++; $ids[] = $newid;
                    } else {
                        return false;
                    }
                }
            };

            $awhere = array_slice($base_awhere, 0);
            $awhere[] = $this->field_id.' not in ('.implode(',', $ids).')';
            if ($this->delete($awhere)) $c_delete = $this->db->affect_rows();
        });
        return array('insert' => $c_insert, 'update' => $c_update, 'delete' => $c_delete);
    }

    /**
     * 取得最后影响的行数
     * @return int
     */
    public function affect_rows() {
        return $this->db->affect_rows();
    }

    /**
     * 设置自动增量值
     * @param string    $dt   数据表
     * @param int       $n    值
     */
    public function set_auto_increment($dt, $n) {
        return $this->db->execute("alter table $dt AUTO_INCREMENT=$n");
    }

    /**
     * 是否有指定的字段
     * @param  [type]    $field   字段名
     * @return boolean
     */
    public function has_field($field) {
        return array_key_exists($field, $this->fields);
    }

    /**
     * 事务处理
     * @param  function     $callback   回调函数
     * @return boolean
     */
    public function transact($callback) {
        return $this->db->transact($callback);
    }

    /**
     * 执行查询动作的SQL
     * @param  string     $sql
     * @return boolean
     */
    public function execute($sql) {
        return $this->db->execute($sql);
    }

    /**
     * 返回由insert产生的最后一条记录ID
     * @return number
     */
    public function newid() {
        return $this->db->newid();
    }
}
?>