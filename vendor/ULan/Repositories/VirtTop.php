<?php
class VirtTop {
    public static function getData(&$error = NULL){
        $arr = self::get($error);
        if (!$arr) return false;
        $str = implode("\n", $arr);
        $ret = self::parse($str, $error);
        if (!$ret) return false;
        return $ret;
    }

    private static function get(&$error = NULL){
        $command1 = 'virt-top -n 2 -d 1 --stream';
        exec($command1, $output, $return);
        if ($return) {
            $error = 'Error Code: '.$return;
            return false;
        } else {
            return $output;
        }
    }

    private static function parse($str, &$error = NULL) {
        if (!$str) {
            $error = '空数据包！';
            return false;
        }
        $ret = preg_split('/virt-top/is', $str);
        if (!$ret) {
            $error = '数据包解析错误！';
            return false;
        }
        $ret = explode("\n", $ret[2]);
        array_shift($ret);
        $keys = preg_split('/\s/', preg_replace(array('/\s{2,}/', '/\%/'), array(' ',''), pos($ret)));
        array_shift($ret); array_shift($keys);
        foreach ($ret as $i => &$item) {
            $item = preg_split('/\s/', preg_replace('/\s{2,}/ ', ' ', $item));
            array_shift($item);
            $item = array_combine($keys, $item);
            unset($item['ID']); unset($item['S']);
        }
        return $ret;
    }
}
?>