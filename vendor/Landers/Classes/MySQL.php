<?php
namespace Landers\Classes;

use Landers\Utils\Arr;

/**
 * MySQL驱动
 * @author Landers
 */
class MySQL{
	private static $conns = array();
	private $conn, $connid, $dbname, $log_path;
	private $dbd_cache, $query_sqls = array(), $is_debug = false;

	public function clear_cache(){
		$this->dbd_cache->clear();
	}

	public function set_log_path($path) {
		$this->log_path = $path;
	}

	public function save_log($sql) {
		if ($path = $this->log_path) {
			$file = rtrim($path, DIRECTORY_SEPARATOR.'/').'/sqls/'.date('Y-m-d').'.log';
			$a = debug_backtrace(0); $line_width = 100;
			$arr = array($sql, str_repeat('=', $line_width), var_export($a, true), str_repeat('-', $line_width));
			$arr = array_merge($arr, array('', '', '', '', ''));
			$content = implode(PHP_EOL, $arr);
			$fso = '\Landers\Utils\Fso';
			if (class_exists($fso)) {
				$ret = $fso::write($file, $content, true);
			} else {
				$ret = !!@file_put_contents($file, $content, true);
			}
			return $ret;
		}
	}

	public function __construct($host, $user, $pwd, $dbname = NULL, $pconnect = false, $charset='utf8', $port = '3306', $is_error_break = true){
		$this->dbd_cache = new dbd_cache(true);
		$this->dbname = $dbname;
		$fun_conn	= $pconnect ? 'mysqli_pconnect' : 'mysqli_connect';
		$this->connid = md5($fun_conn.$host.$port.$user.$pwd);
		$this->conn = Arr::get(self::$conns, $this->connid);
		$this->conn or $this->conn = call_user_func($fun_conn, $host, $user, $pwd, NULL, $port);

		//$this->conn = @call_user_func($fun_conn, "$host:$port", $user, $pwd);
		$this->conn or die("Can not connect database host.\n");
		$this->execute("set character set '$charset'");//读库
		$this->execute("set names '$charset'");  		//写库
		if ($dbname && !$this->active($dbname) && $is_error_break) die("Database is not exists!\n");
	}

	/**
	 * 错误信息
	 */
	public function error(){return mysqli_error($this->conn);}
	public function errno(){return mysqli_errno($this->conn);}

	/**
	 * 数据库是否存在
	 * @param  [type] $dbname [description]
	 * @return [type]         [description]
	 */
	public function db_exists($dbname){
		$sql = 'show databases'; $dbns = array();
		$ret = $this->query($sql);
		if ($ret === false) return false;
		foreach ($ret as $item) $dbns[] = $item['Database'];
		$dbns = array_flip($dbns);
		return array_key_exists( $dbname, $dbns);
	}
	public function version() {return mysqli_get_server_info($this->conn);}
	//public function rows(&$rs){return mysqli_num_rows($rs);}
	public function close(){
		if (!$this->conn) return;
		@mysqli_close($this->conn);
		if ($this->is_debug) {
			debug($this->query_sqls, false);
			debug(dbd_cache::$data, false);
		}
	}
	/**
	 * 选取数据库
	 * @param  [type] $dbname [description]
	 * @return [type]         [description]
	 */
	public function active($dbname){
		$bool = mysqli_select_db($this->conn, $dbname);
		if ($bool) $this->dbname = $dbname;
		return $bool;
	}

	/**
	 * 返回由insert产生的最后一条记录ID
	 * @param  [type] $this->conn [description]
	 * @return [type]             [description]
	 */
	public function newid(){return mysqli_insert_id($this->conn);}
	public function __destruct(){$this->close();}

	/**
	 * 执行查询
	 * @param  [type]  $sql_rs     [description]
	 * @param  integer $fetch_type [description]
	 * @param  boolean $is_cache   [description]
	 * @return [type]              [description]
	 */
	public function query($sql_rs, $fetch_type = 1, $is_cache = true){
		if (!is_string($sql_rs)) {
			$rs = &$sql_rs;
		} else {
			if (!$sql = &$sql_rs) return false;
			if ($ret = $this->dbd_cache->get($sql)) return $ret;
			if ($this->is_debug) $this->query_sqls[] = $sql;
			if (!is_string($sql)) debug(debug_backtrace(0));
			$rs = mysqli_query($this->conn, $sql);
			if (!$rs) {
				$this->save_log($sql);
				return false;
			}
		}
		if ($fetch_type) {
			$ret = array();
			while($row = $this->fetch($rs, $fetch_type)){
				foreach($row as $k => &$v){
					if ($v === (string)(int)$v) $v = (int)$v;
					elseif ($v === (string)(float)$v) $v = (float)$v;
				};
				unset($v); reset($row); $ret[] = $row;
			};
			if ($is_cache) $this->dbd_cache->set($sql, $ret);
			return $ret;
		} else return $rs;
	}

	/**
	 * 执行多条SQL语句
	 * @param  [type] $sqls [description]
	 * @return [type]       [description]
	 */
	public function querys($sqls){
		$sqls = (array)$sqls;
		foreach($sqls as $sql) {
			if ( !$this->execute($sql) ) return false;
		}
		return true;
	}

	/**
	 * 执行查询单条记录的SQL
	 * @param  [type]  $sql        [description]
	 * @param  integer $fetch_type [description]
	 * @param  boolean $is_cache   [description]
	 * @return [type]              [description]
	 */
	public function query_one($sql, $fetch_type = 1, $is_cache = true){
		$ret = $this->query($sql, $fetch_type, $is_cache);
		if ($ret && is_array($ret)) $ret = pos($ret);
		return $ret;
	}

	/**
	 * 执行查询数量的SQL
	 * @param  [type] $sql [description]
	 * @return [type]      [description]
	 */
	public function query_count($sql) {
		$ret = $this->query_one($sql, 2, false);
		return $ret ? (int)pos($ret) : NULL;
	}

	/**
	 * 执行查询动作的SQL
	 * @param  [type]  $sql           [description]
	 * @param  boolean $is_save_error [description]
	 * @return [type]                 [description]
	 */
	public function execute($sql, $is_save_error = true){
		if (!$sql) return false;
		$ret = mysqli_query($this->conn, $sql);
		if (!$ret && $is_save_error) $this->save_log($sql);
		return $ret;
	}

	/**
	 * 取得记录集数据
	 * @param  [type] &$rs  [description]
	 * @param  [type] $mode [description]
	 * @return [type]       [description]
	 */
	public function fetch(&$rs, $mode){
		if (!$rs) return;
		switch($mode){
			case 1 : return mysqli_fetch_assoc($rs);
			case 2 : return mysqli_fetch_row($rs);
			case 3 : return mysqli_fetch_object($rs);
			case 4 : return mysqli_fetch_array($rs);
		};
	}

	/**
	 * 用回调函数进行事务处理
	 * @param  function  $callback [description]
	 * @return boolean
	 */
	public function transact($callback) {
		$conn = &$this->conn;
		$this->execute('BEGIN', $conn);
		if ( $ret = $callback() ) {
			$this->execute('COMMIT', $conn);
			return true;
		} else {
			$this->execute('ROLLBACK', $conn);
			return false;
		}
	}

	/**
	 * 事务处理一组SQL语句
	 * @param  array  $sqls [description]
	 * @return boolean
	 */
	public function transactSqls($sqls) {
		$sqls = (array)$sqls;
		return $this->transact(function() use ($sqls) {
			return $this->querys($sqls);
		});
	}

	/**
	 * 执行SQL后影响的行数
	 * @return [type] [description]
	 */
	public function affect_rows(){
		$x = mysqli_affected_rows($this->conn);
		return $x < 0 ? 0 : $x;
	}

	/**
	 * 取得指定表的字段信息
	 * @param  [type]  $dt           [description]
	 * @param  boolean $is_only_name [description]
	 * @return [type]                [description]
	 */
	public function fields($dt, $is_only_name = true){
		$cachekey = md5($dt.($is_only_name ? 1 : 0));
		if ($ret = $this->dbd_cache->get($cachekey)) return $ret;
		if ($is_only_name) {
			$sql = "SHOW COLUMNS FROM `$dt`";
			$data = $this->query($sql);
			if ( $data === false) return array();
			$data = Arr::rekey($data, 'Field');
			$data = array_keys($data);
		} else {
			$sql = "SHOW FULL COLUMNS FROM `$dt`";
			$data = $this->query($sql);
			if ( $data === false) return array();
			$data = Arr::rekey($data, 'Field');
			foreach ($data as &$row) {
				$row['name'] = Arr::once($row, 'Field');
				$row['type'] = Arr::once($row, 'Type');
				$row['comment'] = Arr::once($row, 'Comment');
				$row['default'] = Arr::once($row, 'Default');
				$row['isnull'] = Arr::once($row, 'Null');
				$row['extra'] = Arr::once($row, 'Extra');
				unset($row['Collation']); unset($row['Key']); unset($row['Privileges']);
				$row['isnull'] = $row['isnull'] === 'YES' ? 'NULL' : 'NOT NULL';
				strlen($row['default']) or $row['default'] = '';
				$row['length'] = ''; $row['values'] = '';
				$row['type'] = strtoupper($row['type']);
				preg_match('/^(.+)\((.+)\)$/', $row['type'] , $match);
				if ($match) {
					$row['type'] = strtoupper($match[1]);
					if (is_numeric($match[2])) $row['length'] = $match[2];
					else if (is_string($match[2])) {
						$row['values'] = str_replace("'", '', $match[2]);
					} else $row['values'] = '';
				}
				$atmp = json_decode($row['comment'], true);
				if (is_array($atmp)){
					$row['text'] = $atmp['text'] or $row['text'] = '';
				} else {
					$row['text'] = $row['comment'];
				}
				//加入sidechar
				switch($row['type']){
					case 'TINYINT':; case 'SMALLINT':; case 'MEDIUMINT':;
					case 'INT':; case 'BIGINT':; case 'FLOAT':; case 'DOUBLE':;
					case 'DECIMAL':; case 'YEAR':; case 'BIT':; case 'BOOL':;
						$row['dtype'] = 'numeric'; $row['sidechar'] = ''; break;

					case 'DATETIME':; case 'DATE':; case 'TIME':; case 'TIMESTAMP':
						$row['dtype'] = 'datetime'; $row['sidechar'] = "'"; break;

					case 'VARCHAR':; case 'CHAR':; case 'ENUM':; case 'SET':;
						$row['dtype'] = 'string'; $row['sidechar'] = "'"; break;

					case 'TINYTEXT':; case 'MEDIUMTEXT':; case 'TEXT':; case 'LONGTEXT':;
					case 'TINYBLOB':; case 'MEDIUMBLOB':; case 'BLOB':; case 'LONGBLOB':;
						$row['dtype'] = 'text'; $row['sidechar'] = "'"; break;
				}
			}
		};
		$this->dbd_cache->set($cachekey, $data);
		return $data;
	}

	/**
	 * 枚举当前数据库所有数据表
	 * @return [type] [description]
	 */
	public function tables(){
		$data = $this->query('show tables');
		if ($data) {
			foreach ($data as &$item) $item = pos($item); unset($item);
		}
		return $data;
	}

	/**
	 * 是否存在指定数据表
	 * @param  [type] $dt [description]
	 * @return [type]     [description]
	 */
	public function dt_exists($dt){
		$tables = $this->tables();
		$dt = trim($dt,'`');
		return in_array($dt, $tables);
	}

	/**
	 * 指定表中是否存在指定字段
	 * @param  [type] $dt    [description]
	 * @param  [type] $field [description]
	 * @return [type]        [description]
	 */
	public function field_exists($dt, $field){
 		if (!$field) exit(sprintf('%s提示：%s', __METHOD__, 'field参数不能为空！'));
		$cachekey = md5($dt.$field);
		if ($ret = $this->dbd_cache->get($cachekey)) return $ret;

		$sql = "DESCRIBE $dt `$field`";
		$ret = !!$this->query($sql, 2);
		$this->dbd_cache->set($cachekey, $ret);
		return $ret;
	}

	/**
	 * 转义字段值
	 * @param  [type] $v [description]
	 * @return [type]    [description]
	 */
	public function conv_value($v){
		if (is_numeric($v)) return $v;
		return mysqli_real_escape_string($this->conn, $v);
		//return str_replace(array("\\'", "'"), array("'", "''"), $v);
	}

	/**
	 * 转义数据表名称
	 * @param  [type]  $str [description]
	 * @param  boolean $is  [description]
	 * @return [type]       [description]
	 */
	public static function conv_dt($str, $is = true) {
		if (strpos($str, ' ') !== false) return $str; //dt:有可能是一sql子句
		$str = trim($str, '`');
		if (!$is) return $str;
		else return "`$str`";
	}

	/**
	 * 转义字段列表
	 * @param  [type] $x [description]
	 * @return [type]    [description]
	 */
	public static function conv_fields($x){
		if ($x === '*' || !$x ) return '*';
		if (is_string($x)) $x = explode(',', $x);
		foreach ($x as &$i) {
			$i = str_ireplace(
					array('`', 	'.', 	' as ', 	'(',	')', 	'``', 	'(`0`)'),
					array('', 	'`.`', 	'` as `', 	'(`', 	'`)', 	'`',	'(0)'),
					trim($i)
				);
			if (strpos($i, '(') === false) $i = "`$i`";
		}; unset($i);
		return implode(', ', $x);
	}
}


class dbd_cache {
	private $is_cache;
	public static $data = array();
	public function __construct($is_cache = true) {
		$this->is_cache = $is_cache;
	}

	private static function get_contain(){
		$a = debug_backtrace(0); $a = $a[2];
		return md5($a['class'].'_'.$a['function']);
	}

	public function get($key){
		if (!is_string($key) || !$this->is_cache) return;
		$contain = self::get_contain();
		$d = &self::$data[$contain];
		$d or $d = array();
		$ret = Arr::get($d, md5($key), array());
		return Arr::get($ret, 'value');
	}

	public function set($key, $value){
		if (!is_string($key) || !$this->is_cache) return;
		$contain = self::get_contain();
		$d = &self::$data[$contain];
		$d or $d = array();
		$d[md5($key)] = array(
			'key'	=> $key,
			'value'	=> $value
		);
	}

	public function clear(){
		foreach (self::$data as &$item) {
			$item = NULL; unset($item);
		}
		self::$data = array();
	}
}
?>
