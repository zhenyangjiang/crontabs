<?php
namespace Landers\Classes;

use Landers\Utils\Str;

/**
 * 简单模板替换、解析类
 * @author Landers
 */
class Tpl {
    public static function replace($tpl, $a, $loop_key = '', $is_inner = false){
        $str_inner = $is_inner ?  'inner_' : '';
        if ($loop_key === true && strpos($tpl, '{foreach}') === false) {
            $tpl = sprintf('{%sforeach}%s{%sendfor}', $str_inner, $tpl, $str_inner); $loop_key = '';
        }
        if ($loop_key) $loop_key = " $loop_key";
        $start  = sprintf('{%sforeach%s}', $str_inner, $loop_key);
        $end    = sprintf('{%sendfor}', $str_inner); $a = (array)$a;
        $tpl_in = Str::between($tpl, $start, $end);
        if (!$tpl_in || $loop_key === false || is_null($loop_key)) { //$a为一维数组
            $k = array_keys($a); $v = array_values($a);
            foreach($k as &$item) $item = '{'.$item.'}'; unset($item);
            return str_replace($k, $v, $tpl);
        } else { //$a为二维数组
            while($tpl_in){
                $tmp = array(); foreach($a as $i => $item){
                    $item['index_no'] = $i + 1;
                    $tmp[] = self::replace($tpl_in, $item);
                }; $tmp = implode("\n", $tmp);
                $tpl_temp = str_replace(array($start, $end), array(''), $tpl);
                $tpl = str_replace($tpl_in, $tmp, $tpl_temp);
                $tpl_in = Str::between($tpl, $start, $end);
            }; return $tpl;
        }
    }

    //模板解析 (最多二维数组)
    public static function parse($tmpl, &$data = array()){
        if (!$data) return $tmpl;
        $reg = '/\{foreach(.*?)\}(.*?)\{endfor\}/si';
        preg_match_all($reg, $tmpl, $matchs);

        //先替换嵌套子列表
        if ( $matchs ) foreach ($matchs[1] as $i => $key) {
            $key    = trim($key);
            $tpl    = $matchs[0][$i];
            $dat    = &$data[$key];
            $dat    = array_values((array)$dat);

            $sub_reg = '/\{inner_foreach(.*?)\}(.*?)\{inner_endfor\}/si';
            preg_match_all($sub_reg, $matchs[2][$i], $sub_matchs);
            if ($sub_matchs) {
                foreach ($sub_matchs[1] as $j => $sub_key) {
                    $sub_key    = trim($sub_key);
                    $sub_tpl    = $sub_matchs[0][$j];

                    foreach ($dat as &$item){
                        $sub_dat = &$item[$sub_key] or $sub_dat = array();
                        $sub_dat = array_values($sub_dat);
                        $sub_dat = Tpl::replace($sub_tpl, $sub_dat, $sub_key, true);
                    }; unset($item);
                    $tpl = preg_replace('/\{inner_foreach '.$sub_key.'\}(.*?)\{inner_endfor\}/si', sprintf('{%s}', $sub_key), $tpl, 1);
                }
            }

            $dst    = Tpl::replace($tpl, $dat, $key);
            $tmpl   = preg_replace('/\{foreach '.$key.'\}(.*?)\{endfor\}/si', $dst, $tmpl, 1);
        };
        //debug($tmpl);

        //替换其它非{foreach}
        return Tpl::replace($tmpl, $data);
    }
}
