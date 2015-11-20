<?php
namespace Landers\Utils;
class Img {
    public static function showPlaceholder($url, $w = '', $h = '') {
        $s = '<img src="%s" width="%s" height="%s"/>';
        return sprintf($s, $url, $w, $h);
    }

    //占位图片URL
    public static function placeholder($w, $h = ''){
        $h or $h = $w; return sprintf('https://placeholdit.imgix.net/~text?txtsize=20&txt=%s%C3%97%s&w=%s&h=%s', $w, $h, $w, $h);
    }
}