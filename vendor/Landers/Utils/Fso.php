<?php
namespace Landers\Utils;

defined('ENV_PATH_root') or define('ENV_PATH_root', str_replace('/' , DIRECTORY_SEPARATOR , $_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR);

/**
 * 文件系统对象类
 * @author Landers
 */
class Fso {
    protected static $_ = DIRECTORY_SEPARATOR;

    /**
     * 转换成目录格式（即最后加上"/"）
     * @param  [type] $dir 目录路径
     * @return [type]      [description]
     */
    private static function to_dir($dir){
        return rtrim($dir, self::$_).self::$_;
    }

    //取得对象大小
    public static function size($path){
        if (is_dir($path)){
            $a = self::file_gets($path, true);
        } elseif (is_file($path)){
            $a = array($path);
        } else return NULL;
        $size = 0; foreach($a as $item)
            $size += filesize($item);
        return $size;
    }

    /**
     * 用最佳单位的字节
     * @param  [type]  $bytes     [description]
     * @param  integer $precision 小数点保留位数
     * @return [type]             [description]
     */
    public static function bytes_size($bytes, $precision = 2){
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        //$bytes /= pow(1024, $pow);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * 取得对象信息
     * @param  [type] $path 目标路径信息
     * @return [type]       [description]
     */
    public static function info($path) {
        if (!file_exists($path)) return NULL;
        $ret = array();
        $ret['size'] = self::size($path);
        $ret['ctime'] = date('Y-m-d',filectime($path));
        $ret['mtime'] = date('Y-m-d',filemtime($path));
        $ret['atime'] = date('Y-m-d',fileatime($path));
        return $ret;
    }

    /**
     * 文件/目录是否可写
     * @param  [type]  $path 目标路径
     * @return boolean       [description]
     */
    public static function is_writable($path) {
        if (is_dir($path)) {
            $path = self::to_dir($path);
            $test_file = $path.'/$$$test$$$.txt';
            if (self::write($test_file, 'test')) {
                @unlink($test_file);
                return true;
            } else return false;
        } elseif (is_file($path)) {
            return is_writable($path);
        } else return false;
    }

    /**
     * 获得目录所有文件对象
     * @param  [type]  $dir        目录路径
     * @param  boolean $is_all_sub 是否所有子目录
     * @param  [type]  $filter     过渡通配符
     * @return [type]              [description]
     */
    public static function file_gets($dir, $is_all_sub = false, $filter = NULL) {
        $dir = self::to_dir($dir);
        if (!$filter) {
            $ret = array(); @$dh = opendir($dir);
            while ($sub = @readdir($dh)){
                if ($sub != '.' && $sub != '..'){
                    $str = $dir.$sub;
                    if (is_file($str)) $ret[] = str_replace(
                        ['/', str_repeat(self::$_, 2)], self::$_, $str
                    );
                }
            }; @closedir($dh);
        } else $ret = glob($dir.$filter);
        if ($is_all_sub) {
            $dirs = self::dir_gets($dir);
            foreach ($dirs as $path) {
                $path = self::to_dir($path);
                $atmp = self::file_gets($path, $is_all_sub, $filter);
                $ret = array_merge($ret, $atmp);
            };
        }; return $ret;
    }

    /**
     * 读取文件内容
     * @param  [type]  $file         文件路径
     * @return [type]                [description]
     */
    public static function read($file){
        if (!is_file($file)) return NULL;
        return file_get_contents($file);
    }

    /**
     * 写内容到文件
     * @param  [type]  $file      文件路径
     * @param  [type]  $content   内容
     * @param  boolean $is_append 是否追加
     * @return [type]
     */
    public static function write($file, $content, $is_append = false){
        $dir = dirname($file);
        if (!file_exists($dir) && !self::dir_make($dir)) return false;
        $mode = $is_append ? FILE_APPEND : NULL;
        $bool = !!@file_put_contents($file, $content, $mode);
        return $bool ? str_replace(
            array(rtrim(ENV_PATH_root, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR, '//'),
            array('',                                        '/',                 '/'),
            $file
        ) : false;
    }

    /**
     * 删除文件
     * @param  [type] $file 文件路径
     * @return [type]       [description]
     */
    public static function file_delete($file){
        if (!is_file($file)) return true;
        return @unlink($file);
    }

    /**
     * 文件复制
     * @param  [type] $src [description]
     * @param  [type] $dst [description]
     * @return [type]      [description]
     */
    public static function file_copy($src, $dst){
        if (!is_file($src)) return false;
        if (is_dir($dst)) {
            $dst = $dst.pathinfo($src, PATHINFO_BASENAME);
        } else {
            $str_right = mb_substr($dst, mb_strlen($dst, 'utf-8') - 1, 1, 'utf-8');
            if ( $str_right == self::$_ ) {
                if (!self::dir_make($dst)) return false;
                return self::file_copy($src, $dst);
            } else {
                $dir = dirname($dst);
                if (!self::dir_make($dir)) return false;
            }
        }
        if ($src == $dst) return false;
        return copy($src, $dst) ? $dst : false;
    }

    /**
     * 文件、目录重命名
     * @param  [type]  $src      源文件/目录路径
     * @param  [type]  $new_name 新名字
     * @param  boolean $is_force 是否强制
     * @return [type]            [description]
     */
    public static function rename($src, $new_name, $is_force = false){
        if (!file_exists($src)) return false;

        $dst = dirname($src).self::$_.trim($new_name, self::$_);
        if ($is_force){
            if (is_file($dst)) @unlink($dst);
            if (is_dir($dst)) if (!self::dir_delete($dst, true)) return false;
        } else {
            if (file_exists($dst)) return false;
        }
        if ($src == $dst) return false;
        return @rename($src, $dst) ? $dst : false;
    }

    /**
     * 文件移动
     * @param  [type] $file 源文件路径
     * @param  [type] $dir  目标目录路径
     * @return [type]       [description]
     */
    public static function file_move($file, $dir){
        if (!is_file($file)) return false;
        if (!self::dir_make($dir)) return false;

        $dir = self::to_dir($dir);
        $dst = $dir.pathinfo($file, PATHINFO_BASENAME);
        if ($file == $dst) return false;
        return @rename($file, $dst) ? $dst : false;
    }
    /**
     * 文件内容搜索
     * @param  [type] $dir     目标目录路径
     * @param  [type] $keyword 关键词
     * @param  [type] $filter  过滤通配符
     * @return [type]          [description]
     */
    public static function file_search($dir, $keyword, $filter = NULL){
        $files = self::file_gets($dir, true, $filter);

        //关键词转换成正则
        $chrs = '().+[]^$-{}/'; $a = str_split($chrs);
        foreach ($a as $i => $item) {
            $a[$item] = '\\'.$item; unset($a[$i]);
        };
        $keyword = strtr($keyword, $a);

        $ret = array(); foreach ($files as $item) {
            $content = file_get_contents($item);
            if (preg_match("/$keyword/i", $content)) $ret[] = $item;
        };
        return $ret;
    }

    /**
     * 取得子目录
     * @param  [type] $dir 目标目录路径
     * @return [type]      [description]
     */
    public static function dir_gets($dir){
        if (!is_dir($dir)) return array();
        $ret = array(); @$dh = opendir($dir);
        while ($sub = @readdir($dh)){
            if ($sub != '.' && $sub != '..'){
                $str = $dir.$sub;
                if (is_dir($str)) $ret[] = self::to_dir($str);
            }
        };
        @closedir($dh);
        return $ret;
    }

    /**
     * 清空目录中所有文件
     * @param  [type]  $dir 目标目录路径
     * @param  boolean $is  是否在最初的目录，递归中的is将为false
     * @return [type]       [description]
     */
    public static function dir_clear($dir, $is = false){
        if (!is_dir($dir)) return true;

        $files  = self::file_gets($dir);
        foreach($files as $item) @unlink($item);
        $dirs = self::dir_gets($dir);
        foreach($dirs as $item) self::dir_clear($item, true);
        if ($is) if (!@rmdir($dir)) return false;
        return true;
    }

    /**
     * 删除目录
     * @param  [type]  $dir         目标目录路径
     * @param  boolean $is_force    是否强制
     * @return [type]               [description]
     */
    public static function dir_delete($dir, $is_force = true){
        if (!is_dir($dir)) return true;

        $files  = self::file_gets($dir);
        foreach($files as $item) @unlink($item);
        $dirs   = self::dir_gets($dir);
        if (count($files)==0 && count($dirs)==0){
            return @rmdir($dir);
        } else {
            if (!$is_force) return false;
            if (!self::dir_clear($dir)) return false;
            else return @rmdir($dir);
        }
    }

    /**
     * 创建多级目录
     * @param  [type] $dir 目标目录多级路径
     * @return [type]      [description]
     */
    public static function dir_make($dir){
        $dir = str_replace(DIRECTORY_SEPARATOR, '/', $dir);//针对win系统
        $dir = str_replace('//', '/', $dir);
        $a = explode('/', $dir);
        $ret = self::$_ == '/' ? '/' : '';
        foreach ($a as $item){
            if (!$item) continue;
            $ret .= $item.self::$_;
            if (is_dir($ret)) continue;
            mkdir($ret); chmod($ret, 0777);
        };
        return is_dir($ret) ? $ret : false;
    }

    public static function make_date_path($dir, $mode = 3) {
        $ret = array(); switch((int)$mode){
            case 0 : break;
            case 1 : $dir .= date('Y'); break;
            case 2 : $dir .= date('Ym'); break;
            case 3 : $dir .= date('Ymd'); break;
            case 4 : $dir .= date('Ym').self::$_.date('d'); break;
            case 5 : $dir .= date('Y').self::$_.date('m').$_.date('d'); break;
            default: break;
        };
        return self::to_dir($dir);
    }

    /**
     * 建立日期模式目录
     * @param  [type]  $dir  目标目录路径
     * @param  integer $mode 日期格式
     * @return [type]        [description]
     */
    public static function dir_make_by_date($dir, $mode = 3, $sub_dir = ''){
        $dir = self::make_date_path($dir, $mode);
        if ($sub_dir) $dir .= $dir.self::$_.$sub_dir;
        return self::dir_make($dir) ? $dir : false;
    }

    /**
     * 目录复制
     * @param  [type] $src 源目录路径
     * @param  [type] $dst 目标目录路径
     * @return [type]      [description]
     */
    public static function dir_copy($src, $dst){
        if (!is_dir($src)) return false;
        if (!self::dir_make($dst)) return false;

        $src = self::to_dir($src);
        $dst = self::to_dir($dst);

        $files = self::file_gets($src, true);
        foreach($files as $file){
            $t = pathinfo($file); $file = $t['basename'];
            $srcfile = $src.$file; $dstfile = $dst.$file;
            if (is_file($srcfile)) copy($srcfile, $dstfile);
        }
        $dirs = self::dir_gets($src);
        foreach($dirs as $dir){
            $t = pathinfo($dir); $dir = $t['basename'];
            $srcdir = self::to_dir($src.$dir);
            $dstdir = self::to_dir($dst.$dir);
            self::dir_copy($srcdir, $dstdir);
        };
        return !!glob($dst.'*.*');
    }

    /**
     * 目录移动
     * @param  [type]  $src          源目录路径
     * @param  [type]  $dst          目标目录路径
     * @param  boolean $is_move_self true:是否仅移动本身 / flase:移动其子目录及文件
     * @return [type]                [description]
     */
    public static function dir_move($src, $dst, $is_move_self = false){
        if (!is_dir($src)) return false;
        if (!self::dir_make($dst)) return false;
        if ($is_move_self) {
            $arr = array($src);
        } else {
            $dirs = self::dir_gets($src);
            $files = self::file_gets($src);
            $arr = array_merge($dirs, $files);
        }
        foreach ($arr as &$item){
            $basename = pathinfo($item, PATHINFO_BASENAME);
            $isdir = is_dir($item);
            $item = array(
                'src' => $isdir ? $item : trim($item, self::$_),
                'dst' => $dst.$basename.($isdir ? self::$_ : ''),
            );
        }; unset($item);
        $ret = true;
        foreach ($arr as $item){
            if (file_exists($item['dst'])) {
                if (is_dir($item['src'])) {
                    if (!self::dir_copy($item['src'], $item['dst'])) $ret = false;
                    else self::dir_delete($item['src'], true);
                    //debug(sprintf('copydir:%s -> %s', $item['src'], $item['dst']));
                }
                if (is_file($item['src'])) {
                    if (!self::file_copy($item['src'], $item['dst'])) $ret = false;
                    else unlink($item['src']);
                }
            } else {
                if (!rename($item['src'], $item['dst'])) $ret = false;
            }
        }
        return $ret;
    }

}