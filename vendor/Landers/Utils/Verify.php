<?php
namespace Landers\Utils;

Class Verify {
    public static function match($p, $s) {
        return preg_match($p, $s) >= 1 ? true : false;
    }

    public static function is_positive_integer($n) {
        return self::match('/^[0-9]*[1-9][0-9]*$/', $n);
    }

    public static function is_letter($s){
        return self::match('/^[a-zA-Z]+$/', $s);
    }

    public static function is_letter_numeric_bottomline($s){
        return self::match('/^[a-zA-Z][a-zA-Z0-9_]{5,19}$/', $s);
    }

    public static function is_chinaese($s){
        return self::match('/^[\x{4e00}-\x{9fa5}]+$/u', $s);
    }

    public static function is_chinaese_part($s){
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

    public static function is_bool($val) {
        return $val && (
            $val === true ||
            $val === 'true' ||
            $val === 1 ||
            $val === '1' ||
            $val === false ||
            $val === 'false' ||
            $val === 0 ||
            $val === '0'
        );
    }

    public static function is_json($s) {
        return !is_null(json_decode($s));
    }

    public static function data(array &$data, array $dataset = array(), &$error = NULL, $check_all = false) {
        $ret = array();
        $data or $data = array();
        $infos or $infos = array();
        if (!$dataset) return true;
        foreach ($dataset as $item){
            $key        = trim($item['key'],'`');
            if (!array_key_exists($key, $data)) continue;
            $val        = &$data[$key];
            $name       = sprintf('【%s】', $item['name'] ? : $key);
            $errmsg     = $item['errmsg'];
            $errval     = $item['errval'];
            $verify     = $item['verify'];
            $max        = $item['max'];
            $min        = $item['min'];
            $dtype      = $item['dtype'] ? : 'string';
            $isnull     = $item['isnull'];
            $default    = $item['default'];
            // dp($_POST, false);

            //检查空值情况
            if ( !strlen($val) ){//空值、空串处理
                if (strlen($default)) {
                    $val = $default;
                    continue;
                }
                if ( $isnull ) {
                    $val = NULL;
                    continue;
                } else {
                    $ret[] = sprintf('%s不能为空！', $name);
                    continue;
                }
            }

            //数据长度验证
            switch( $dtype ) {
                case 'numeric'  :
                    //是否为数字
                    if (!is_numeric($val)) {
                        $ret[] = sprintf('%s必须是【数字型】', $name);
                        continue;
                    } else {
                        //转换成相应的数字型
                        $val = strpos((string)$val, '.') === false ? (int)$val : (float)$val;

                        //检查数据是否在有效范围内
                        if ($min && $max){
                            if ($val < $min || $val > $max) {
                                $ret[] = sprintf('%s只能介于'.$min.'和'.$max.'之间', $name);
                                continue;
                            }
                        } else {
                            if ($min && $val < $min) {
                                $ret[] = sprintf('%s不能小于'.$min, $name);
                                continue;
                            }
                            if ($max && $val > $max) {
                                $ret[] = sprintf('%s不能大于'.$max, $name);
                                continue;
                            }
                        }
                    };
                    break;

                case 'string'   :; case 'text':;
                    //数据特征验证
                    $len = strlen($val);
                    //检查数据是否在有效范围内
                    if ($min && $max){
                        if ($len < $min || $len > $max) {
                            $ret[] = sprintf('%s长度只能介于'.$min.'和'.$max.'之间', $name);
                            continue;
                        }
                    } else {
                        if ($min && $len < $min) {
                            $ret[] = sprintf('%s长度不能小于'.$min, $name);
                            continue;
                        }
                        if ($max && $len > $max) {
                            $ret[] = sprintf('%s长度不能大于'.$max, $name);
                            continue;
                        }
                    };
                    break;
            };

            //数据校验
            switch ($verify) {
                case 'letter_numeric_bottomline':
                    if (!self::is_letter_numeric_bottomline($val)) {
                        $ret[] = sprintf('%s必须是字母、数字、下划线组合', $name);
                        continue;
                    }; break;

                case 'chinaese_part' :
                    if (!self::is_chinaese_part($val)) {
                        $ret[] = sprintf('%s必须是含有中文', $name);
                        continue;
                    }; break;

                case 'numeric':
                    if (!is_numeric($val)) {
                        $ret[] = sprintf('%s必须是【数字型】', $name);
                        continue;
                    }; break;
                case 'json':
                    if (!self::is_json($val)) {
                        $ret[] = sprintf('%s必须为JSON型', $name);
                        continue;
                    }; break;
                case 'letter' :
                    if (!self::is_letter($val)) {
                        $ret[] = sprintf('%s必须为英文字母', $name);
                        continue;
                    }; break;
                case 'chinaese' :
                    if (!self::is_chinaese($val)) {
                        $ret[] = sprintf('%s必须为中文', $name);
                        continue;
                    }; break;
                case 'email'    :
                    if (!self::is_email($val)) {
                        $ret[] = sprintf('%s无效email格式', $name);
                        continue;
                    }; break;
                case 'url'      :
                    if (!self::is_url($val)){
                        $ret[] = sprintf('%s无效URL格式', $name);
                        continue;
                    }; break;

                case 'zipcode'  :
                    if (!self::is_zipcode($val)){
                        $ret[] = sprintf('%s无效邮政编码格式', $name);
                        continue;
                    }; break;
                case 'certno'   :
                    if (!self::is_certno($val)) {
                        $ret[] = sprintf('%s无效身份证号格式', $name);
                        continue;
                    }; break;
                case 'qq'       :
                    if (!self::is_qq($val)) {
                        $ret[] = sprintf('%s无效QQ号', $name);
                        continue;
                    }; break;
                case 'ip'       :
                    if (!self::is_ip($val)) {
                        $ret[] = sprintf('%s无效IP地址格式', $name);
                        continue;
                    }; break;
                case 'tel'      :
                    if (!self::is_tel($val)) {
                        $ret[] = sprintf('%s无效电话号码格式', $name);
                        continue;
                    }; break;
                case 'fax'      :
                    if (!self::is_tel($val)) {
                        $ret[] = sprintf('%s无效传真号码格式', $name);
                        continue;
                    }; break;
                case 'mobile'   :
                    if (!self::is_mobile($val)) {
                        $ret[] = sprintf('%s无效手机号码格式', $name);
                        continue;
                    }; break;
                case 'mobile_cmcc'   :
                    if (!self::is_mobile_cmcc($val)) {
                        $ret[] = sprintf('%s无效中国移动手机号码', $name);
                        continue;
                    }; break;
                case 'mobile_cucc'   :
                    if (!self::is_mobile_cucc($val)) {
                        $ret[] = sprintf('%s无效中国联通手机号码', $name);
                        continue;
                    }; break;
                case 'mobile_ctcc'   :
                    if (!self::is_mobile_ctcc($val)) {
                        $ret[] = sprintf('%s无效中国电信手机号码', $name);
                        continue;
                    }; break;
                case 'bool'  :
                    if ( !self::is_bool($val) ) {
                        $ret[] = sprintf('%s为无效【布尔值】', $name);
                        continue;
                    }; break;
                case 'positive_integer':
                    if (!self::is_positive_integer($val)) {
                        $ret[] = sprintf('%s必须为正整数', $name);
                        continue;
                    }; break;
                case 'date'     :
                    if (!self::is_date($val)){
                        $ret[] = sprintf('%s无效日期格式', $name); continue;
                    }; break;
                case 'datetime' :
                    if (!self::is_datetime($val)){
                        $ret[] = sprintf('%s无效时间日期格式', $name); continue;
                    }; break;
                case 'time'     :
                    if (!self::is_time($val)){
                        $ret[] = sprintf('%s无效时间格式', $name); continue;
                    }; break;
            };

            //不为指定的错误值
            if ( $errval != '*' && $errval === $val) {
                $ret[] = $errmsg ? : sprintf('%s不能为【'.$errval.'】');
                continue;
            }

            if ( !$check_all ) {
                if (count($ret) > 0) {
                    $error = pos($ret);
                    return false;
                }
            }
        }

        if (count($ret) > 0){
            $error = "提交的数据还存在以下错误：\n\n";
            foreach ($ret as $i => $err){
                $error .= ($i+1).'. '.$err."\n";
            };
            return false;
        } else {
            return true;
        }
    }
}