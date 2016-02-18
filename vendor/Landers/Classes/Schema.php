<?php
namespace Landers\Classes;

use Landers\Utils\Arr;
use Landers\Utils\Str;
use Landers\Utils\Fso;

class Schema {
	private static $db;
	public static $debug = false;

	public static function init($db) {
		self::$db = &$db;
	}

	/**
	 * 创建基本表结构
	 * @param  [type] $dtname [description]
	 * @return [type]         [description]
	 */
	public static function create_basic_table($dtname){
		$sql = "CREATE TABLE IF NOT EXISTS `".$dtname."` (
			`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录编号',
			`sitekey` varchar(50) NOT NULL COMMENT '站点标识',
			`admid` smallint(6) NOT NULL COMMENT '后台用户ID',
			`sid` smallint(6) NOT NULL COMMENT '所属栏目',
			`appid` smallint(6) NOT NULL COMMENT '应用标识',
			`meta_title` varchar(300) DEFAULT NULL COMMENT 'SEO标题',
			`meta_keyword` varchar(300) DEFAULT NULL COMMENT 'SEO关键词',
			`meta_describe` varchar(900) DEFAULT NULL COMMENT 'SEO描述',
			`hits` mediumint(9) DEFAULT '0' COMMENT '点击次数',
			`sorter` mediumint(9) DEFAULT '100000' COMMENT '排序',
			`htmlurl` varchar(255) NULL COMMENT '静态URL',
			`locked_at` datetime NULL COMMENT '锁定时间',
			`created_at` datetime NOT NULL COMMENT '添加时间',
			`updated_at` datetime NOT NULL COMMENT '更新时间',
			`deleted_at` datetime NULL COMMENT '删除时间',
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		return self::$db->execute($sql);
	}

	//取得所有数据库名称
	public static function gets(){
		$sql = 'show databases';
		return self::$db->query($sql);
	}

	//导出表结构
	public static function export_structure($dt){
		$arr = self::$db->query("SHOW CREATE TABLE `$dt`");
		$str = $arr[0]['Create Table'];
		$str = preg_replace('/CREATE TABLE/i', 'CREATE TABLE IF NOT EXISTS', $str);
		$str = preg_replace('/AUTO_INCREMENT=\d+/i', 'AUTO_INCREMENT=1', $str);
		return $str.";";
	}

	//导出表数据
	public static function export_data($dt, $opts = NULL){ //opts: where , step, action, expects, repdata, where_update_tpl
		$db = &self::$db; $aret = array(); $opts or $opts = array();
		$where = $opts['where']; $where = $where ? "where $where" : '';
		$expects = $opts['expects'] or $expects = array();
		$action = $opts['action'] or $action = 'insert';
		$step = $opts['step'] or $step = 10;
		$repdata = $opts['repdata'] or $repdata = array();
		$expects = Str::split($expects);
		$allfields = $db->fields($dt, false);

		if ($action == 'insert') {
			if ($c = $db->query_count("select count(0) from $dt")) { //有数据
				$p = 0; while ($p <= $c) {
					$sql = "select * from $dt $where limit $p, $step";
					$data = $db->query($sql, 1);
					if (!$tmp_c = count($data))	break;
					$p += $tmp_c; $fields = array_keys($data[0]);

					$expects[] = 'id'; $expects = array_unique($expects);
					foreach($expects as $field) $fields = Arr::remove($fields, $field);

					$fields = implode('`,`', $fields);
					$sql = "insert into `$dt` (`$fields`) values \n";
					$vals = array();
					foreach($data as $item) {
						foreach($expects as $field) unset($item[$field]);

						$atmp = array(); foreach( $item as $k => $v) {
							if (is_null($v)) {
								$atmp[] = 'NULL';
							} else {
								if ($vv = $repdata[$k]) $v = $vv;
								$v = str_replace(';', '; ', $v);
								$sidechar = $allfields[$k]['sidechar'];
								$atmp[] = $sidechar.strtr($v, array("'" => "''", '\\' => '\\\\')).$sidechar;
							}
						}
						$vals[] = '('.implode(', ', $atmp).')';
					}
					$sql .= implode(",\n", $vals).";\n";

					$aret[] = $sql;
				}
			}

			return $aret;
		}

		if ($action == 'update') {
			$where_update_tpl = $opts['where_update_tpl'] or Response::error('未指定where_update_tpl选项参数！');
			if ($c = $db->query_count("select count(0) from $dt")) { //有数据
				$p = 0; while ($p <= $c) {
					$sql = "select * from $dt $where limit $p, $step";
					$data = $db->query($sql);
					if (!$tmp_c = count($data))	break;
					$p += $tmp_c; $fields = array_keys($data[0]);

					$expects[] = 'id'; $expects = array_unique($expects);
					foreach($expects as $field) $fields = Arr::remove($fields, $field);

					$fields = '`'.implode('`,`', $fields).'`';
					foreach($data as $item) {
						$where_update = Tpl::replace($where_update_tpl, $item);
						$sql = "update `$dt` set %s where $where_update \n";

						foreach($expects as $field) unset($item[$field]);

						$sets = array(); foreach( $item as $k => $v) {
							if ($vv = $repdata[$k]) $v = $vv;

							$sidechar = $allfields[$k]['sidechar'];
							if (is_null($v)) $sets[$k] = 'NULL';
							else $sets[$k] = $sidechar.strtr($v, array("'" => "''", '\\' => '\\\\')).$sidechar;
						}
						$a = array(); foreach($sets as $k => $v) $a[] = "`$k`=$v";
						$aret[] = sprintf($sql, implode(', ', $a));
					}
				}
			}

			return $aret;
		}
	}

	//导出
	private static function push_check_save_for_export(&$data, &$dat, $file, $maxsize) {
		$data[] = $dat; $test = implode("\n", $data);
		if (strlen($test) > $maxsize) {
			$pop = $data[count($data)-1]; array_pop($data);

			//保存卷
			Fso::write($file, implode("\n", $data));
			$data = array($pop); //重新开始

			return true;
		} else return false;
	}
	public static function export($dts, $path, $maxsize) {
		if (!is_array($dts)) return; $aret = array(); $part = 1;
		$tpl	= rtrim($path, '/').'/db_{part}.sql';
		$file	= str_replace('{part}', $part, $tpl);

		foreach ($dts as $dt) {
			//表结构
			$str = "drop table if exists `$dt`;\n".self::export_structure($dt);
			if (self::push_check_save_for_export($aret, $str, $file, $maxsize)) {
				$file = str_replace('{part}', ++$part, $tpl);
			}

			//表数据
			$atmp = self::export_data($dt);
			foreach($atmp as $item) {
				if (self::push_check_save_for_export($aret, $item, $file, $maxsize)) {
					$file = str_replace('{part}', ++$part, $tpl);
				}
			}
		}

		//保存最后卷
		$file = str_replace('{part}', $part, $tpl);
		return !!Fso::write($file, implode("\n", $aret));
	}

	//导入SQL
	public static function import_sql($lines){
		if (!is_array($lines)) $lines = explode("\n", $lines);
		$buffer = ''; foreach ($lines as $line) {
			if (trim($line) == '') continue;
			if (substr(ltrim($line), 0, 2) == '--')	continue;
			if (substr($line, -1) != ';') {$buffer .= $line; continue;}
			if ($buffer) {$line = $buffer . $line; $buffer = ''; }
			if ($line = substr($line, 0, -1)) {
				if (!self::$db->execute($line)) {
					dp($line);
					return false;
				}
			}
		}
		return true;
	}

	//从文件导入
	public static function import_file($file){
		$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		return self::import_sql($lines);
	}

	//生成增加、改变字段的sql
	private static 	$def_need_len = array(
		'VARCHAR'	=> 100,
		'TINYINT'	=> 1,
		'SMALLINT'	=> 6,
		'MEDIUMINT'	=> 9,
		'INT'		=> 11,
		'BIGINT'	=> 20
	);
	private static $def_no_len = array(
		'FLOAT'		=> '',
		'DOUBLE'	=> '',
		'TINYTEXT'	=> '',
		'MEDIUMTEXT'=> '',
		'TEXT'		=> '',
		'LONGTEXT'	=> '',
	);
	private static function build_add_change_sql($dt, $act, $name, $type, $length, $values, $isnull, $default , $comment, $extra, $oriname=NULL){

		//类型处理
		$type = strtoupper($type);
		switch($type){
			case 'ENUM' :; case 'SET':
				$tmp = explode(',', str_replace('，', ',', $values));
				foreach($tmp as &$item) {
					$a = explode('|', $item);
					$item = "'".trim($a[0])."'";
				}
				unset($item); $tmp = implode(',' , $tmp);
				$type = "ENUM($tmp)"; $length = '';
				break;
			default :
				if ( !strlen($default) ) {
					if ($isnull !=  'NOT NULL') {
						$default = 'NULL';
					}
				} else {
					$default = "'$default'";
				}
				break;
		}

		//长度处理
		if (isset(self::$def_need_len[$type])) {
			$length or $length = self::$def_need_len[$type]; //本该有长度，当前无长度，则当前长度为默认长度
		}

		if (isset(self::$def_no_len[$type])) {
			!$length or $length = ''; //本该有长度，当前无长度，则当前长度为默认长度
		}

		$arr = array(); $act = strtoupper($act);
		$arr[] = "ALTER TABLE `$dt`";
		switch($act){
			case 'ADD' 		:
				$arr[] = "$act";
				break;
			case 'CHANGE'	:
				$oriname or $oriname = $name;
				$arr[] = "$act `$oriname`";
				break;
		}
		$arr[] = "`$name` $type".($length ? "($length)" : '');
		$arr[] = is_string($isnull) ? $isnull : (is_null($isnull) ? 'NULL' : ($isnull ?  'NULL' : 'NOT NULL'));

		$arr[] = ($default && !$extra)  ? "DEFAULT $default" : '';
		$arr[] = $extra ? $extra : '';
		$arr[] = "COMMENT '$comment'";
		return implode(' ' , $arr);
	}

	//增加字段
	public static function field_add($dt, array $data){ //$data结构: {name, type, length, values, isnull, default, comment(text), extra}
		extract($data); $comment or $comment = $text;
		$sql = self::build_add_change_sql($dt, 'add', $name, $type, $length, $values, $isnull, $default, $comment, $extra);
		if (self::$debug) debug($sql);
		return self::$db->execute($sql);
	}

	//修改字段
	public static function field_change($dt, $field, $data){
		extract($data); $comment or $comment = $text;
		$sql = self::build_add_change_sql($dt, 'change', $name, $type, $length, $values, $isnull, $default, $comment, $extra, $field);
		if (self::$debug) debug($sql);
		return self::$db->execute($sql);
	}

	//删除字段
	public static function field_drop($dt, $fields){
		if (!$fields) return true;
		$fields = Str::split($fields);
		$sql = "alter table `$dt` "; $drops = array();
		foreach ($fields as $field) {
			if (!self::$db->field_exists($dt, $field)) continue;
			$drops[] = "drop `$field`";
		}
		if (!$drops) return true; //没有可删除的
		$sql .= implode(',', $drops).';';
		if (self::$debug) debug($sql);
		return self::$db->execute($sql);
	}

	//同步字段
	public static function field_sync($dt, $data, &$error = NULL){//$data结构: {oldname, name, type, length, values, isnull, default, comment(text), extra}
		$oldname = $data['oldname'];
		if ($oldname) {
			if (self::$db->field_exists($dt, $oldname)){
				$bool = self::field_change($dt, $oldname, $data);
				$act = 'modify'; $msg = '修改';
			} else {
				pause();
			}
		} else {
			$name = $data['name'];
			if (!self::$db->field_exists($dt, $name)){
				$bool = self::field_add($dt, $data);
				$act = 'add'; $msg = '添加';
			} else {
				if (count($data) == 1) { //数组中只有一个元素，即name, 则认为是删除
					$bool = self::drop_field($dt, $name);
					$act = 'delete'; $msg = '删除';
				} else { //字段更新
					$bool = self::field_change($dt, $name, $data);
					$act = 'update'; $msg = '更新';
				}
			}
		}
		if (!$act) {
			$error = '没有执行任何操作！';
			return false;
		}
		if (!$bool) {
			$error = $msg.'时出错';
			return false;
		}
		return $act;
	}

	/**
	 * 合并数据表
	 * @param  [type] $dt_dst     [description]
	 * @param  [type] $dt_src     [description]
	 * @param  [type] $rel_fields [description]
	 * @param  [type] $exts       [description]
	 * @param  string $where      [description]
	 * @return [type]             [description]
	 * 	$rel_fields参数格式：
	 * array(
	 * 	'$dt_dst中的字段名' => '$dt_src中的字段名'
	 * )
	 * 返回：合并了多少条
	 */
	public static function dt_combine($dt_dst, $dt_src, $rel_fields, $exts, $where = ''){
		$db = &self::$db; $exts or $exts = array();
		$fields_src = array_values($rel_fields);
		$fields_dst = array_keys($rel_fields);
		$fields_src = MySQL::conv_fields($fields_src);
		$fields_dst = MySQL::conv_fields($fields_dst);

		$fields_src = Str::split($fields_src);
		$fields_dst = Str::split($fields_dst);
		$dst_fields = $db->fields($dt_dst, false);
		foreach ($exts as $k => $v) {
			$fields_dst[] = MySQL::conv_fields($k);
			$sidechar = $dst_fields[$k]['sidechar'];
			$fields_src[] = "$sidechar$v$sidechar";
		}

		//检查字段数量
		$dst_fields = MySQL::conv_fields(array_keys($dst_fields));
		$dst_fields = Str::split($dst_fields);
		$diff		= array_diff($dst_fields, $fields_dst);
		if ($diff) exit(sprintf('缺少字段：%s', implode(', ', $diff)));

		$fields_src = implode(', ', $fields_src);
		$fields_dst = implode(', ', $fields_dst);
		$sql = "insert into $dt_dst (%s) select %s from $dt_src".($where ? " where $where" : '').' order by id asc';
		$sql = sprintf($sql, $fields_dst, $fields_src); //debug($sql, false);

		if (!$bool = $db->execute($sql)) return false;
		if ($bool) return $db->affect_rows();
	}
}

?>
