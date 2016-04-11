<?php
namespace Landers\Substrate\Utils;

defined('ENV_DEBUG_client') or define('ENV_DEBUG_client', true);
defined('ENV_PATH_root') or define('ENV_PATH_root', $_SERVER['DOCUMENT_ROOT']);
defined('_DIR_') or define('_DIR_', DIRECTORY_SEPARATOR);

class Debug {
	private static function file_to_url($file){
		return str_replace(array(
			ENV_PATH_root, _DIR_
		), 	'/',  $file);
	}
	public static function var_dump($x, $is_show = false) {
		$a = array();
		$a[] = 'border:1px solid #666666';
		$a[] = 'background-color:#333333';
		$a[] = 'color:#ffffff';
		$a[] = 'text-align:left';
		$a[] = 'margin-top:5px; padding:5px;';
		$a[] = 'word-wrap:break-word;word-break:normal;';
		$style	 = implode(';', $a);
		if (is_array($x) || is_object($x)) {
			$ret = "<pre style='$style'>".print_r($x, true).'</pre>';
		} else {
			if (is_string($x)) $x = htmlspecialchars($x, ENT_NOQUOTES);
			$ret = "<textarea style='$style height:300px;width:99.8%;'>$x</textarea>";
		}

		if ($is_show) echo $ret;
		return $ret;
	}

	public static function show($x = NULL, $is = true, $opts = NULL, $back = 0){
		$opts or $opts = array();
		if (!array_key_exists('is_ext', $opts)) $opts['is_ext'] = true; extract($opts);
		if (ENV_DEBUG_client !== false || $opts['is_force']){
			switch(gettype($x)){
				case 'NULL' 	: $type = '[NULL(空)]'; $x = strval($x); break;
				case 'integer'	: $type = '[整数]'; $x = strval($x); break;
				case 'long'		: $type = '[长整数]'; $x = strval($x); break;
				case 'double'	: $type = '[双精度]'; $x = strval($x); break;
				case 'string' 	: $type = '[字符串('.strlen($x).')]'; $x = strval($x); break;
				case 'boolean'	: $type = '[布尔]'; $x = $x ? 'true' : 'false'; break;
				case 'resource' : $type = '[资源]'; break;
				case 'object'	: $type = '[对象]'; break;
				case 'array' 	: $type = '[数组('.count($x).')]'; break;
				default			: $type = '[未知数据类型]'; $x = ''; break;
			}
			if ($is_ext) {
				$html = '<div style="zoom:1; overflow:hidden; clear:both;">';
				$html .= '<span style="float:fleft;">%s</span>';
				$html .= '<span style="float:right;">%s %s</span>';
				$html .= '</div>'; $a = debug_backtrace(0); $dat = &$a[$back];
				$reload	= '<a href="javascript:window.location.reload();">刷新</a>';

				$url = self::file_to_url($dat['file']);
				$codeline = sprintf('<a title="%s">%s(%s行)</a>', $dat['file'], $url, $dat['line']);
				echo sprintf($html, $type, $codeline, $reload);
			}
			self::var_dump($x, true); if ($is) exit();
		}
	}

	public static function pause($back = 0){
		$a = debug_backtrace(0); $dat = &$a[$back];
		$url = self::file_to_url($dat['file']);
		$msg = '程序暂停中：'.$url.'：第'.$dat['line'].'行';
		self::show($msg, true, NULL, $back);
	}
}

if (!function_exists('dp')) {
	function dp($xvar, $is = true) {Debug::show($xvar, $is, array(), 1);}
}
if (!function_exists('dpp')) {
	function dpp() {Debug::pause(1);}
}
if (!function_exists('pause')) {
	function pause() {Debug::pause(1);}
}
?>