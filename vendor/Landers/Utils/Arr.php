<?php
namespace Landers\Utils;

/**
 * 数组辅助类
 * @author Landers
 */

class Arr {
    //同步Str::split
    public static function split_string($xvar, $split = ','){
        if (!$xvar) return array();
        if (is_array($xvar)) return $xvar;
        if (is_array($split)) $split = implode('', $split);
        return preg_split('/[ '.$split.']+/', $xvar);
    }

    public static function to_html(&$a) {
        return sprintf('<pre>%s</pre>', var_export($a, true));
    }

    public static function once(&$arr, $key) {
        $ret = $arr[$key]; unset($arr[$key]); return $ret;
    }

    //数组中找出第一个isset测试为值的值
    public static function find_isset(&$arr, $keys){
        $arr or $arr = array();
        foreach ($keys as $key) if (isset($arr[$key])) return $arr[$key];
        return NULL;
    }

    //取得数组中最后一个元素
    public static function last(&$a) {
        return $a[count($a) -1 ];
    }

    //克隆一维数组
    public static function slice(&$arr, $ks = NULL) {
        if (!$ks) return $arr; $r = array();
        $ks = self::split_string($ks);
        foreach($ks as $k) {
            if (!array_key_exists($k, $arr)) continue;
            $r[$k] = $arr[$k];
        }; return $r;
    }

    //取数组中的元素值
    private static function _get(&$a, $key, $default = NULL){
        if (!is_array($a)) return $default;
        if (!array_key_exists($key, $a)) {
            return $default;
        } else {
            return $a[$key];
        }
    }
    public static function get($a, $keys, $default = NULL){
        if (!is_array($a)) return $default;
        $keys = explode('.', $keys);
        $c = count($keys);
        if ($c > 1) {
            $arr = [];
            for ( $i = 0; $i < $c - 1; $i++ ) {
                $key = $keys[$i];
                $arr[$key] = self::_get($a, $key, []);
                $arr = &$arr[$key]; $a = &$a[$key];
            }
            $lastkey = $keys[$c-1];
            return self::_get($arr, $lastkey, $default);
        } else {
            $key = implode('', $keys);
            return self::_get($a, $key, $default);
        }
    }

    //array_clones即名改名 array_clone_inner
    //克隆二维数组中每一元素的内部的某些元素
    public static function clone_inner(&$arr, $keys){
        $r = array(); foreach ($arr as $item){
            $r[] = self::slice($item, $keys);
        }
        if (!$r) return $r;
        if (count($r[0]) == 1) $r = self::flat($r);
        return $r;
    }

    //数组扁平化成一唯数组
    public static function flat(&$arr) {
        if (!is_array($arr)) return $arr; $r = array();
        foreach ($arr as $item) {
            $t = self::flat($item);
            if (is_array($t)) {
                $r = array_merge($r, $t);
            } else $r[] = $t;
        };
        return $r;
    }

    //是否为键值数组
    public static function is_keyval(&$a){
        return is_array($a) && !isset($a[0]) && count($a);
    }

    //二维数组key重命名
    public static function rekey(array $a, $as) {
        $aret = array();
        foreach($a as $item) $aret[(string)$item[$as]] = $item;
        return $aret;
    }

    public static function remove_keys(array $a, $keys){
        $keys = self::split_string($keys);
        foreach ($keys as $key) unset($a[$key]);
        return $a;
    }

    public static function remove($a, $values) {
        if (!$a) return array();
        if (self::is_keyval($a)) {
            $a = array_flip($a);
            $keys = self::split_string($values);
            foreach($keys as $item) unset($a[$item]);
            $a = array_flip($a);
            return $a;
        } else {
            $values = self::split_string($values);
            foreach ($values as $v) {
                $i = array_search($v, $a);
                if (is_numeric($i)) unset($a[$i]);
            }
            return array_values($a);
        }
    }

    public static function sort(array $data, $by, $order = 'desc') {
        $sort_by = array();
        foreach($data as $item) $sort_by[] = $item[$by];
        $order = strtolower($order) == 'asc' ? SORT_ASC : SORT_DESC;
        array_multisort($sort_by, $order, $data);
        return $data;
    }

    //数据根据某字段排序(支持并列）
    public static function sort_rank(array $data, $by, $key, $sort_order = SORT_DESC){
        $sort_by = array(); $rank = 1;
        $_data = array_slice($data, 0); //以下操作将此作为副本
        foreach($data as $item) $sort_by[] = $item[$by];
        array_multisort($sort_by, $sort_order, SORT_NUMERIC, $_data);

        $a = array(); foreach($_data as $item) {
            $v = (string)$item[$by]; $a[$v] or $a[$v] = array();
            $a[$v]['rank'] or $a[$v]['rank'] = $rank++;
            $a[$v]['keys'] or $a[$v]['keys'] = array();
            $a[$v]['keys'][] = $item[$key];
        }

        $aret = array(); foreach($a as $item){
            $rank = $item['rank']; $aret[$rank] or $aret[$rank] = array();
            foreach($item['keys'] as $key) {
                $data[$key]['rank'] = $rank;
                $aret[$rank][$key] = $data[$key];
            };
        }
        return $aret;
    }

    public static function split(array $a, $n) {
        $ret = array();
        $per = count($a) / $n;
        for ($i = 0; $i < $n - 1; $i++) {
            $ret[] = array_slice($a, $i, $per);
        }
        $ret[] = array_slice($a, $i * $per);
        return $ret;
    }

    //对两个数组的双层深度合度
    public static function merge(array &$arr1, array &$arr2) {
        $ret = [];
        if ($arr2) {
            foreach ($arr2 as $key => $dat) {
                if (!is_array($arr1[$key])) {
                    $ret[$key] = $dat;
                } else {
                    $ret[$key] = array_merge($dat, $arr1[$key]);
                }
            }
        }
        if ($arr1) {
            foreach ($arr1 as $field => $info) {
                if (!is_array($arr2[$field])) {
                    $ret[$field] = $info;
                } else {
                    $ret[$field] = array_merge($info, $arr2[$field]);
                }
            }
        }
        return $ret;
    }
}
?>