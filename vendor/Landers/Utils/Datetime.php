<?php
namespace Landers\Utils;

/**
 * 日期时间类
 * @author Landers
 */
class Datetime {
	public static $second 	= 1;
	public static $minute		= 60;
	public static $hour		= 3600;
	public static $day		= 86400;
	public static $week 		= 604800;
	public static $month		= 2592000;
	public static $year		= 31104000;

	/**
	 * 打印调试可视化时间日期
	 * @param  [type]  $datas [description]
	 * @param  boolean $is    [description]
	 * @return [type]         [description]
	 */
	public static function debug($datas, $is = true) {
		$datas = (array)$datas;
		foreach ($datas as &$items) {
			if (is_array($items)) {
				foreach ($items as &$item) {
					$item = self::format($item, 'Y-m-d H:i:s');
				}; unset($item);
			} else {
				$items = self::format($items, 'Y-m-d H:i:s');
			}

		}; unset($items);
		dp($datas, $is);
	}

 	/**
 	 * 格式化日期时间
 	 * @param  strint/int 		$date   	[description]
 	 * @param  string 			$format 	[description]
 	 * @return strint/int         			[description]
 	 */
	public static function format($date, $format = 'Y-m-d') {
		if (!is_numeric($date)) $date = strtotime($date);
		if (!$format) return $date;
		return date($format, $date);
	}

	/**
	 * 两日期时间相距
	 * @param  [type] $begin [description]
	 * @param  [type] $end   [description]
	 * @param  [type] $intv  [description]
	 * @return [type]        [description]
	 */
	public static function diff($begin, $end , $intv = 'd'){
		$begin = self::format($begin, 0);
		$end = self::format($end, 0);
		switch ($intv) {
			case 'i' : $k = self::$minute; break;
			case 'h' : $k = self::$hour; break;
			case 'd' : $k = self::$day; break;
			case 'w' : $k = self::$week; break;
			case 'm' : $k = self::$month; break;
			case 'y' : $k = self::$year; break;
			case 's' : $k = 1;
		};
		return floor(($end - $begin) / $k);
	}

	/**
	 * 距离今天的天数 (即使相差1秒，也算1天)
	 * @param  [type] $date [description]
	 * @return [type]        [description]
 	 */
	public static function diff_now_days($begin){
		$begin = self::format($begin, 0);
		$end = time();
		if ($begin === $end) return 0;
		$ret = ($end - $begin) / self::$day;
		return $ret > 0 ? ceil($ret) : floor($ret);
	}

	/**
	 * 取得时间位移后的结果
	 * @param [type] $intv   [description]
	 * @param [type] $offset [description]
	 * @param [type] $date   [description]
	 */
	public static function add($intv, $offset, $date = NULL, $format = NULL){
		if ( !$date ) $date = time();
		else $date = self::format($date, 0);
		switch(strtolower($intv)){
			case 'd' : $intv = 'days'; break;
			case 'm' : $intv = 'minutes'; break;
			case 'M' : $intv = 'months'; break;
			case 'y' : $intv = 'years'; break;
			case 'h' : $intv = 'hours'; break;
			case 's' : $intv = 'seconds'; break;
			case 'w' : $intv = 'weeks'; break;
		}
		$ret = strtotime("+$offset $intv", $date);
		return self::format($ret, $format);
	}

	/**
	 * 时效性检查
	 * @param  [type] $begin [description]
	 * @param  [type] $end [description]
	 * @return [type]     [description]
	 */
	public static function expire($begin, $end) {
		if ($begin && $end) {
			if (self::is_between_dates($begin, $end))
				return array(true, '时效正常', 0);
			else
				return array(false, '已过期', 1);
		} else if ($begin) {
			 $begin = strtotime($begin);
			 if (time() > $begin) return array(true, '长期有效', 0);
			 else return array(false, '未到时效', 2);
		} else if ($end) {
			 $end = strtotime($end);
			 if (time() < $end) return array(true, '时效正常', 0);
			 else return array(false, '已过期', 1);
		} else return array(true, '长期有效', 0);
	}

	/**
	 * 时间转换成星期
	 * @param  [type] $date [description]
	 * @param  string $pre  [description]
	 * @return [type]       [description]
	 */
	public static function week($date, $pre='星期'){
		$a = array('日','一','二','三','四','五','六');
		$date = self::format($date, 0);
		$w = date("w", $date);
		return $pre.$a[$w];
	}

	/**
	 * 秒数解析出天，时，分，秒
	 * @param  [type]  $n      [description]
	 * @param  boolean $is_day [description]
	 * @return [type]          [description]
	 */
	public static function parse($n, $is_day = false){
		$s = gmstrftime('%d:%H:%M:%S',$n);
		$a = explode(':', $s);
		$a[0] = (int)$a[0] - 1;
		if ( !is_day) $a[1] = (int)$a[1] + $a[0]*24;
		$keys = array('days', 'hours', 'minutes', 'seconds');
		return array_combine($keys, $a);
	}

	public static function differ_yearweek($year1, $week1, $year2 = NULL, $week2 = NULL){
		$day1 = self::get_first_day_by_year_week($year1, $week1);
		if ( !$year2 || !$week2 ){
			$atmp = self::get_yearweek(time());
			$year2 = $atmp['year'];
			$week2 = $atmp['week'];
		}
		$day2 = self::get_first_day_by_year_week($year2, $week2);
		return (int)(($day2 - $day1) / self::$week);
	}

	/**
	 * 取得某日期是哪年的哪周
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	/*
	for($y = 1982; $y <= 2014; $y++) {
	    $date = "$y-12-31";
	    $yearweek = self::yearweek($date);
	    echo $date.'<br/>';
	    debug($yearweek, false);
	    echo (int)date('W', strtotime($date));
	    echo str_repeat('<BR/>', 2);
	}; exit();
	 */
	public static function yearweek($date = NULL){
		$date = self::format($date, 0);
		$y = (int)date('Y', $date);
		$m = (int)date('m', $date);
		$W = (int)date('W', $date);
		if ( $W >= 52 && $m == 1 ) $y--;
		if ( $W == 1 && $m == 12 ) $y++;
		return array('year' => $y, 'week' => $W);
	}

	/**
	 * 取得某年第一个星期中的第一天
	 * @param  [type] $year   [description]
	 * @param  [type] $format [description]
	 * @return [type]         [description]
	 */
	public static function firstday_in_firstweek_on_year($year, $format = NULL) {
		$ret = strtotime("$year-01-01 00:00:00");
		$W = (int)date('W', $ret); //本年第几周
		$w = (int)date('w', $ret); //今天是周几
		if ($W >= 52) $ret += ($w ? self::$week : 0);
		$ret -= $w * self::$day;
		return self::format($ret, $format);
	}

	/**
	 * 取得某年第几周的第一天
	 * @param  [type] $year [description]
	 * @param  [type] $week [description]
	 * @return [type]       [description]
	 */
	public static function firstday_in_week_on_year($year, $week, $format = NULL){
		$base = self::firstday_in_firstweek_on_year($year);
		$ret = $base + ($week - 1) * self::$week;
		return self::format($ret, $format);
	}

	/**
	 * 取得某年第几周的日期段
	 * @param  [type] $year [description]
	 * @param  [type] $week [description]
	 * @return [type]       [description]
	 */
	public static function dates_in_week_on_year($year, $week, $format = NULL){
		$begin = self::firstday_in_week_on_year($year, $week);
		$end = $begin + self::$week - self::$second;
		return array('begin' => self::format($begin, $format), 'end' => self::format($end, $format));
	}

	/**
	 * 取得某天所在星期的第一天
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public static function firstday_in_week_on_date($date, $format = NULL){
		$date = self::format($date, 0);
		$date = strtotime(date('Y-m-d 00:00:00', $date));
		$w = (int)date('w', $date) or $w = 7;
		$ret = $date - $w * self::$day;
		return self::format($ret, $format);
	}

	/**
	 * 取得某天所在星期的最后一天
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public static function lastday_in_week_on_date($date, $format = NULL){
		$date = self::format($date, 0);
		$date = strtotime(date('Y-m-d 23:59:59', $date));
		$w = (int)date('w', $date) or $w = 7;
		$ret = $date + (7 - $w - 1) * self::$day;
		return self::format($ret, $format);
	}

	/**
	 * 取得某一天的上周的日期段
	 * [dates_in_lastweek_on_date description]
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public static function dates_in_lastweek_on_date($date, $format){
		$date = self::format($date, 0);
		$date = $date - self::$week;
		$begin = self::firstday_in_week_on_date($date);
		$end = self::lastday_in_week_on_date($date);
		return array('begin' => self::format($begin, $format), 'end' => self::format($end, $format));
	}

	/**
	 * 指定时间日期是否在begin与end之间
	 * @param  [type]  $begin [description]
	 * @param  [type]  $end   [description]
	 * @param  [type]  $date  [description]
	 * @return boolean        [description]
	 */
	public static function is_between_dates($begin, $end, $date = NULL){
		$date = self::format($date, 0);
		$begin = self::format($begin, 0);
		$end = self::format($end, 0);
		$atmp = array($begin, $end); sort($atmp);
		$begin = $atmp[0]; $end = $atmp[1];
		return $date >= $begin && $date < $end;
	}

	public static function dates_between($begin, $end = NULL, $format = NULL) {
		$end or $end = time(); $ret = array();
		$begin = self::format($begin, 0);
		$end = self::format($end, 0);
		for ($i = $begin; $i < $end; $i += self::$day) {
			$ret[] = $format ? self::format($i, $format) : $i;
		}
		$ret[] = $format ? self::format($end, $format) : $end;
		$ret = array_unique($ret);
		return $ret;
	}

	/**
	 * 是否在指定日期时间之前
	 * @param  [type]  $date [description]
	 * @return boolean       [description]
	 */
	public static function is_before($date){
		$date = self::format($date, 0);
		return time() < $date;
	}

	/**
	 * 是否在指定日期时间之后
	 * @param  [type]  $date [description]
	 * @return boolean       [description]
	 */
	public static function is_after($date){
		$date = self::format($date, 0);
		return time() > $date;
	}
}
?>