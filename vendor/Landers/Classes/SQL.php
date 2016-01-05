<?php
namespace Landers\Classes;

use Landers\Utils\Arr;
use Landers\Utils\Str;
use Landers\Utils\Verify;
use Landers\Classes\Schema;

/**
 * SQL语句生成器
 * @author Landers
 */
class SQL {
	public $db, $dt, $fields;
	public $union_dts, $parter;
	// public $data = array();
	public $err, $err_text;

	public static $field_addtime = 'created_at';
	public static $field_updtime = 'updated_at';

	public function __construct(&$db = NULL, $dt = NULL, $parter = NULL){
		$this->db = &$db;
		$this->dt = $dt;
		$this->parter = $parter ? (is_array($parter) ? $parter : ['type' => $parter]) : NULL;
		$this->fields = $db->fields($dt, false);
	}

	public function __destruct(){}

	public static $error = array(
		'INSERT_NO_DATA'	=> '缺少插入的数据！',
		'INSERT_EXISTS'		=> '插入的数据中有不允许重复字段值！',
		'UPDATE_NO_DATA'	=> '缺少更新数据！',
		'UPDATE_NOT_FOUND'	=> '没有可更新的记录！',
		'UPDATE_EXISTS'		=> '更新的数据中有字段与其它记录的字段值重复！',
		'UPDATE_PROHIBIT'	=> '为了数据的安全，系统禁止无条件更新！',
		'DELETE_NOT_FOUND'	=> '没有可删除的记录！',
		'DELETE_PROHIBIT'	=> '为了数据的安全，系统禁止无条件删除！'
	);

    /**
     * 生成分表名称
     * @return [type]           [description]
     */
    private $exists_union_dts = [];
    private function build_union_dts(array $unions = array()) {
        if (!$parter = $this->parter) {
        	return $this->dt;
        }

        $args = func_get_args();
        $parter_type = Arr::get($parter, 'type');
        $parter_mode = Arr::get($parter, 'mode');
        switch ($parter_type) {
            case 'datetime' :
                if (!count($unions)) {
                    $ret = $this->dt.'_'.date($parter_mode, time());
                } else {
                    $ret = [];
                    foreach ($unions as $item) {
                        if (!is_numeric($item)) $item = strtotime($item);
                        $ret[] = $this->dt.'_'.date($parter_mode, $item);
                    }
                }
                break;
            case 'special':
                if (!count($unions)) {
                    $ret = $this->dt.'_'.($parter_mode ?:'');
                } else {
                    $ret = [];
                    foreach ($unions as $arg) {
                        $ret[] = $this->dt.'_'.$arg;
                    }
                }
                break;
        }
        if ($ret) {
            Schema::init($this->db);
            foreach ((array)$ret as $item) {
                if (array_key_exists($item, $this->exists_union_dts)) {
                    continue;
                }
                if (!$this->db->dt_exists($item)) {
                    //导出 dt 表结构
                    $sql = Schema::export_structure($this->dt);
                    $sql = str_replace("`$this->dt`", "`$item`", $sql);
                    $this->db->execute($sql);
                }
                $this->exists_union_dts[] = $item;
            }
            $this->union_dts = $ret;
        }
        return $ret;
    }

	public static function get_errmsg($errkey){
		$ret = self::$error[$errkey];
		return $ret ? $ret : $errkey;
	}

	public function build_where($awhere, $is_show_table = false){
		$fields = &$this->fields;
		$where1 = $where2 = array();
		if ($this->parter) $is_show_table = false;
		foreach((array)$awhere as $k => $v) {
			if (is_numeric($k)) {
				$where2[] = $v;
			} else {
				$_k = MySQL::conv_fields($k);
				if ($is_show_table) $_k = '`'.$this->dt.'`.'.$_k;
				if (!is_null($v)) {
					$_ 		= $fields[$k]['sidechar'];
					$dtype	= $fields[$k]['dtype'];
					$type	= $fields[$k]['type'];
					if (is_array($v) && count($v) == 1) $v = pos($v);
					if (is_array($v)) {
						if ($type == 'SET') {
							$atmp = array();
							foreach($v as $_v) $atmp[] = "find_in_set('$_v', $_k)>0";
							$where1[$k] = implode(' and ', $atmp);
						} else {
							$v_ = array(); foreach($v as &$_v) {
								if (is_null($_v)) $_v = "$_k is NULL";
								else {$v_[] = "$_$_v$_"; $_v = "$_k=$_$_v$_";}
							}; unset($_v);
							if (count($v_) == count($v)) {
								$where1[$k] = "$_k in (".implode(',', $v_).')';
							} else {
								$where1[$k] = implode(' or ', $v);
							}
						}
					} elseif (strlen($v)) {
						if ($dtype == 'text') $where1[$k] = "$_k like $_%$v%$_";
						else {
							if ($type == 'SET') $where1[$k] = "find_in_set('$v', $_k)>0";
							elseif ($type == 'DATETIME') $where1[$k] = "$_k like $_%$v%$_";
							else $where1[$k] = "$_k=$_$v$_";
						}
					} else {
						switch($dtype) {
							case 'string':; case 'text' : $where1[$k] = "$_k='' or $_k is NULL"; break;
							case 'datetime'	: $where1[$k] = "$_k is NULL"; break;
							case 'numeric'	: $where1[$k] = "$_k=0"; break;
						}
					}
				} else $where1[$k] = "$_k is NULL";
			}
		};
		$where = array_merge($where1, $where2);
		foreach ($where as &$item) $item = "($item)";
		return implode(' and ', $where);
	}

	private function build_data($a){
		$a = (array)$a; $ret = array();
		$db = &$this->db; $fields = &$this->fields;
		foreach($a as $k => $v) {
			$_ 		= $fields[$k]['sidechar'];
			$dtype	= $fields[$k]['dtype'];
			$type	= $fields[$k]['type'];
			$isnull	= $fields[$k]['isnull'];
			$default= $fields[$k]['default'];
			$_k 	= MySQL::conv_fields($k);

			if (!is_null($v)) {
				if (is_array($v)) {
					foreach($v as &$_v) $_v = $db->conv_value($_v); unset($_v);
					if ($_ == "'") $ret[$k] = "'".implode(',', $v)."'";
				} elseif (strlen($v)) {
					if (substr($v, 0, 3) === '```') {
						$ret[$k] = ltrim($v, '`');
					} else {
						$v = $db->conv_value($v);
						$ret[$k] = "$_$v$_";
					}
				} else {
					switch($dtype) {
						case 'string':; case 'text' : $ret[$k] = "''"; break;
						case 'datetime'	: $ret[$k] = 'NULL'; break;
						case 'numeric'	: $ret[$k] = '0'; break;
					}
				}
			} else {
				if (strlen((string)$default)) {
					$ret[$k] = $_.$default.$_;
				} else {
					if ($isnull == 'NOT NULL') {
						switch($dtype) {
							case 'string':; case 'text' :; $ret[$k] = "''"; break;
							case 'datetime'	: $ret[$k] = '1970-01-01'; break;
							case 'numeric'	: $ret[$k] = '0'; break;
						}
					} else {
						//修改时，有提交$k的值，设为NULL；可能input控件处于disabled导致没提交，需保持原值;
						//if (isset($_POST[$k])) $ret[$k] = 'NULL';
						$ret[$k] = 'NULL';
					}
				}
			}
		};
		return $ret;
	}

	private function addtimes(&$data, $is_add, $is_upd){
		$i_now = time(); $s_now = date('Y-m-d H:i:s', $i_now);
		$fields = &$this->fields;
		$add = self::$field_addtime; $upd = self::$field_updtime;
		if ($is_add && array_key_exists($add, $fields)){
			$data[$add] = @trim($data[$add]) or $data[$add] = NULL;
			if (!Verify::is_datetime($data[$add]) && !Verify::is_date($data[$add])){
				$_ = $fields[$add]['sidechar'];
				if ( !$_ ) $data[$add] = $i_now; else $data[$add] = $s_now;
			}
		}

		if ($is_upd && array_key_exists($upd, $fields)){
			$data[$upd] = @trim($data[$upd]) or $data[$upd] = NULL;
			if (!Verify::is_datetime($data[$upd]) && !Verify::is_date($data[$upd])){
				$_ = $fields[$upd]['sidechar'];
				if ( !$_ ) $data[$upd] = $i_now; else $data[$upd] = $s_now;
			}
		}
	}

	private function combine_wheres($awhere, $owhere, $is_show_table = false){
		$a = array();
		$awhere = $this->build_where($awhere, $is_show_table);
		$owhere = $this->build_where($owhere, $is_show_table);
		if ($awhere && $owhere) return sprintf('(%s) or (%s)', $awhere, $owhere);
		elseif ($awhere) return $awhere;
		elseif ($owhere) return $owhere;
		else return '';
	}

	//data:要添加的数据
	/**
	 * [InsertSQL description]
	 * @param Array $data 	数据
	 */
	public function InsertSQL(Array $data){
		$db = &$this->db;
		if (!$data) {
			$ret 			= false;
			$this->err		= 'INSERT_NO_DATA';
			$this->err_text = self::get_errmsg($this->err);
		} else {
			$dt = $this->build_union_dts();
			$dt = $this->db->conv_dt($dt);
			$this->addtimes($data, true, true);
			$data 		= $this->build_data($data);
			$fields 	= MySQL::conv_fields(array_keys($data));
			$values		= implode(', ', $data);
			$ret = "insert into $dt ($fields) values ($values)";
			$this->err	= $this->err_text = '';
		} return $ret;
	}

	//导入数据包
	public function ImportSQL(Array $datas) {
		$dt = $this->build_union_dts();
		$dt = $this->db->conv_dt($dt);
		$data0 = pos($datas); $fields = array_keys($data0);
		$has_addtime = $this->db->field_exists($dt, self::$field_addtime);
		$has_updtime = $this->db->field_exists($dt, self::$field_updtime);
		if ($has_addtime) $fields[] = self::$field_addtime;
		if ($has_updtime) $fields[] = self::$field_updtime;
		$fields = '`'.implode('`,`', $fields).'`';
		$ret = "insert into $dt ($fields) values".PHP_EOL;
		foreach ($datas as $data) {
			$this->addtimes($data, $has_addtime, $has_updtime);
			$row = $this->build_data($data);
			$values[] = '('.implode(', ', $row).')';
		}
		return $ret . implode(', '.PHP_EOL, $values);
	}

	public function UpdateSQL($data, $awhere, $owhere = NULL, $unions = array()){
		$db = &$this->db;
		if (!$data) {
			$ret 			= false;
			$this->err		= 'UPDATE_NO_DATA';
			$this->err_text = self::get_errmsg($this->err);
		} else {
			$dts = $this->build_union_dts($unions);
			$this->addtimes($data, false, true);
			$data = $this->build_data($data);
			$a = array(); foreach($data as $k => $v) $a[] = MySQL::conv_fields($k).'='.$v;
			$data	= implode(', ', $a);
			$where	= $this->combine_wheres($awhere, $owhere);
			$where = $where ? " where $where" : '';

			$ret = [];
			foreach ((array)$dts as $dt) {
				$dt = $this->db->conv_dt($dt);
				$ret[] = "update $dt set $data$where";
			}
		} return $ret;
	}

	public function CountSQL(array $opts = array()) {
		$opts['fields'] = ['count(0)'];
		return $this->SelectSQL($opts);
	}

	public function SumSQL($field, array $opts = array()) {
		$opts['fields'] = "sum($field)";
		return $this->SelectSQL($opts);
	}

	public function AvgSQL($field, array $opts = array()) {
		$opts['fields'] = "avg($field)";
		return $this->SelectSQL($opts);
	}

	public function MaxSQL($field, array $opts = array()) {
		$opts['fields'] = "max($field)";
		return $this->SelectSQL($opts);
	}

	public function MinSQL($field, array $opts = array()) {
		$opts['fields'] = "min($field)";
		return $this->SelectSQL($opts);
	}

	public function BuildTableUnionSQL($union_dts, $fields, $where) {
		$dts = Str::split($union_dts); $sqls = [];
		$dts = array_unique($dts); sort($dts);
		$tpl = 'select %s from %s%s';
		foreach ($dts as $dt) {
			$dt = $this->db->conv_dt($dt);
			$sqls[] = sprintf($tpl, $fields, $dt, $where);
		}
		return '(('.implode(') union (', $sqls).')) as union_temp_table_'.uniqid();
	}

	public function SelectSQL(Array $opts){ //fields, awhere, owhere, order , limit, group;
		$fields = Arr::get($opts, 'fields');
		$fields = MySQL::conv_fields($fields);

		$extra_fields = Arr::get($opts, 'extra_fields');
		if ($extra_fields) {
			if ( $fields == '*' ) $fields = '';
			$fields .= ', '.$extra_fields;
			$fields = ltrim($fields, ', ');
		}

		$_awhere = Arr::get($opts, 'awhere');
		$_owhere = Arr::get($opts, 'owhere');
		$_extra_dt = Arr::get($opts, 'extra_dt');
		$_order = Arr::get($opts, 'order');
		$_limit = Arr::get($opts, 'limit');
		$_group = Arr::get($opts, 'group');
		$where 	= $this->combine_wheres($_awhere, $_owhere, !!$_extra_dt);
		$where 	= $where ? " where $where" : '';
		$order = $_order ? ' order by '.$_order : '';
		$limit = $_limit ? ' limit 0, '.$_limit : '';
		$group = $_group ? ' group by '.$_group : '';

		$tpl = 'select %s from %s%s%s%s%s';
		if ($unions = Arr::get($opts, 'unions')) {
			//多表联合
			$union_dts = $this->build_union_dts($unions);
			if ( preg_match('/^\w{1,}\((\w{1,})\)$/', $fields, $matches) ){
				//select聚合函数
				$field = $matches[1] or $field = key($this->fields);
				$dt = $this->BuildTableUnionSQL($union_dts, $field, $where);
				$sql = sprintf($tpl, $fields, $dt, '', $group, '', '');
			} else {
				//常规select
				$dt = $this->BuildTableUnionSQL($union_dts, $fields, $where);
				$sql = sprintf($tpl, '*', $dt, '', $group, $order, $limit);
			}
		} else {
			$dt = $_extra_dt ? $this->dt.', '.$_extra_dt : $this->dt;
			$sql = sprintf($tpl, $fields, $dt, $where, $group, $order, $limit);
		}
		return $sql;
	}

	public function DeleteSQL($awhere, $owhere = NULL, $unions = array()){
		$awhere = (array)$awhere;
		if ($awhere || $owhere) {
			$dts = $this->build_union_dts($unions);
			$where	= $this->combine_wheres($awhere, $owhere);
			$where	= $where ? " where $where" : '';
			$ret = [];
			foreach ((array)$dts as $dt) {
				$dt = $this->db->conv_dt($dt);
				$ret[] = "delete from $dt$where";
			}
			$this->err	= $this->err_text = '';
		} else {
			$ret 		= false;
			$this->err	= 'DELETE_PROHIBIT';
			$this->err_text = self::get_errmsg($this->err);
		};
		return $ret;
	}

	public function TruncateSQL(array $unions = array()) {
		$dts = $this->build_union_dts($unions);
		$ret = array();
		foreach ((array)$dts as $dt) {
			$dt = $this->db->conv_dt($dt);
			$ret[] = sprintf('truncate %s', $dt);
		}
		return $ret;
	}


	public static function build_where_bylink($wheres, $link){
		if ( is_array($wheres)) {
			$link = " $link ";
			foreach ($wheres as &$item) {
				$item = "($item)";
			}; unset($item);
			$ret = implode($link, $wheres);
			return count($wheres) > 1 ? "($ret)" : $ret;
		} else return $wheres;
	}

	public static function build_order_custom_numbers($field, $dat){
		if (is_array($dat)) $dat = implode(',', $dat);
		return "INSTR(',$dat,',CONCAT(',',$field,','))";
	}

	public static function build_where_today($field){
		return "to_days(`$field`) = to_days(now())";
	}

	public static function build_where_recent_day($field, $days = 30){
		if ($days === 0) return self::build_where_today($field);
		return "date_format(`$field`,'%m%d') between date_format(now(),'%m%d') and date_format(date_add(now(), interval $days day),'%m%d')";
	}

	public static function build_repeat($dt, $fields, $repeat_field){
		$sub_sql = 'select $repeat_field from `$dt` group by $field having count(`$repeat_field`)>1';
		return "select $field from `$dt` where $repeat_field in ($sub_sql)";
	}

	public static function build_field_diff_days($field, $as_field = 'diff_days'){
		$year = date('Y');
		return "datediff(date_format($field, '$year%m%d'), date_format(now(),'$year%m%d')) as $as_field";
	}
}



/*
时间相减法
timediff('2008-08-08 08:08:08', '2008-08-08 00:00:00');
timediff(now(), $field);

*/


?>