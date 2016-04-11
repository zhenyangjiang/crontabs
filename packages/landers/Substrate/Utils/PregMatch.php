<?php
namespace Landers\Substrate\Utils;
/**
 * 常用正则类
 * @author Landers
 */
class PregMatch {
	public static function match($p, $s) {
		return preg_match($p, $s) >= 1 ? true : false;
	}

	public static function is_positive_integer($n) {
		return self::match('/^[0-9]*[1-9][0-9]*$/', $n);
	}

	public static function is_all_english($s){
		return self::match('/^[a-zA-Z]+$/', $s);
	}

	public static function is_username($s){
		return self::match('/^[a-zA-Z][a-zA-Z0-9_]{5,19}$/', $s);
	}

	public static function is_username_has_cn($s){
		return self::match('/^[a-zA-Z][a-zA-Z0-9_][\x{4e00}-\x{9fa5}]{5,19}$/', $s);
	}

	public static function is_password($s){
		return self::match('/^[a-zA-Z][a-zA-Z0-9_]{5,19}$/', $s);
	}

	public static function is_all_chinaese($s){
		return self::match('/^[\x{4e00}-\x{9fa5}]+$/u', $s);
	}

	public static function is_part_chinaese($s){
		return self::match('/[\x{4e00}-\x{9fa5}]+/u', $s);
	}

	public static function is_mobile($s){
		//return self::match('/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|18[89]{1}[0-9]{8}$/', $s);
		return self::match('/^[1][358][0-9]{9}$/', $s);
	}

	public static function is_mobile_cmcc($s){ //中国移动
		return self::match('/^1(3[4-9]|5[01789]|8[78])\\d{8}$/', $s);
	}

	public static function is_mobile_cucc($s){ //中国联通
		return self::match('/^1(3[0-2]|5[256]|8[56])\\d{8}$/', $s);
	}

	public static function is_mobile_ctcc($s){ //中国电信
		return self::match('/^(18[09]|1[35]3)\\d{8}$/', $s);
	}

	public static function is_qq($s){
		//return self::match('/^0\d{2,3}$/', $s);
		return self::match('/^[1-9]*[1-9][0-9]*$/', $s);
	}

	public static function is_date($s){
		return self::match('/^(19|20)[0-9]{2}-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01])$/', $s);
	}

	public static function is_time($s){
		return self::match('/^(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9])$/', $s);
	}

	public static function is_datetime($s){
		return self::match('/^(19|20)[0-9]{2}-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01]) (0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9])$/', $s);
	}

	public static function is_certno($s){
		return self::match('/^\d{17}(\d|x)$/i', $s);
	}

	public static function is_ip($s){
		return self::match('/^(?:\d{1,2}|[0-1]\d{2}|[2][0-5]{2})\.(?:\d{1,2}|[0-1]\d{2}|[2][0-5]{2})\.(?:(?:\d{1,2}|[0-1]\d{2}|[2][0-5]{2})\.(?:\d{1,2}|[0-1]\d{2}|[2][0-5]{2})|(?:\d{1,2}|[0-2]\d{2})\.\*|\*\.\*)$/', $s);
	}

	public static function is_tel($s){
		return self::match('/^(0[0-9]{2,3}\-)?([2-9][0-9]{6,7})+(\-[0-9]{1,4})?$/', $s);
	}

	public static function is_email($s){
		return self::match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $s);
	}

	public static function is_url($s){
		return self::match(
			'/^http[s]?:\/\/'.
		    '(([0-9]{1,3}\.){3}[0-9]{1,3}'. // IP形式的URL- 199.194.52.184
		    '|'. // 允许IP和DOMAIN（域名）
		    '([0-9a-z_!~*\'()-]+\.)*'. // 域名- www.
		    '([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.'. // 二级域名
		    '[a-z]{2,6})'.  // first level domain- .com or .museum
		    '(:[0-9]{1,4})?'.  // 端口- :80
		    '((\/\?)|'.  // a slash isn't required if there is no file name
		    '(\/[0-9a-zA-Z_!~\'\.;\?:@&=\+\$,%#-\/^\*\|]*)?)$/', $s
		);
	}

	public static function is_win_path($s){
		return self::match('/[A-Za-z]\\:\\[^\:\?\"\>\<\*]*/', $s);
	}

	public static function is_zipcode($s){
		return self::match('/[1-9]\d{5}/', $s);
	}
}
?>