<?php
function pause() {
    $x = str_repeat('=', 40);
    $a = debug_backtrace(); $dat = &$a[0];
    echo ("\n".$x.'运行终止'.$x."\n");
    echo sprintf("终止于：%s(line:%s)\n\n", $dat['file'], $dat['line']);
    exit();
}

function dp($x, $is = true, $back = 0){
    switch(gettype($x)){
        case 'NULL'     : $type = '[NULL(空)]'; $x = strval($x); break;
        case 'integer'  : $type = '[整数]'; $x = strval($x); break;
        case 'long'     : $type = '[长整数]'; $x = strval($x); break;
        case 'double'   : $type = '[双精度]'; $x = strval($x); break;
        case 'string'   : $type = '[字符串('.strlen($x).')]'; $x = strval($x); break;
        case 'boolean'  : $type = '[布尔]'; $x = $x ? 'true' : 'false'; break;
        case 'resource' : $type = '[资源]'; break;
        case 'object'   : $type = '[对象]'; break;
        case 'array'    : $type = '[数组('.count($x).')]'; break;
        default         : $type = '[未知数据类型]'; $x = ''; break;
    }
    $a = debug_backtrace(); $dat = &$a[$back];
    echo sprintf("%s(line:%s)\n", $dat['file'], $dat['line']);
    echo $type.'：'; print_r($x); echo "\n\n";
    if ($is) exit();
}

function colorize($text, $color, $effect = '') {
    switch ($effect) {
        //case 'bold' : $effect = '1';
        case 'highlight' : $effect = 1; break;
        case 'underline' : $effect = 4; break;
        case 'flash': $effect = 5; break;
        case 'inverse' : $effect = 7; break;
    }
    switch ($effect) {
        case 1 : $text = " $text ";  break;
    }
    if ($effect) $effect = ';'.$effect;

    // 格式：'背景色;前景色;效果'
    // 背景色:
    // 40 Black
    // 41 Red
    // 42 Green
    // 43 Yellow
    // 44 Blue
    // 45 Magenta
    // 46 Cyan
    // 47 White
    //
    //前景色:
    //
    // 30 black
    // 31 dark red
    // 32 light green
    // 33 dark yellow
    // 34 dark blue
    // 35 light violet
    // 36 light blue, cyan
    // 37 white
    //
    // 效果:
    // 0 Reset All Attributes (return to normal mode)
    // 1 Bright (usually turns on BOLD)
    // 2 Dim
    // 3 Underline
    // 5 Blink
    // 7 Reverse
    // 8 Hidden

    switch ($color) {
        case 'error':;  $color = '41;33;5'; break;
        case 'success':; $color = '42'; break;
        case 'warn':;  $color = '43;30'; break;
        case 'info': $color = '44'; break;
        case 'note':; $color = '0'; break;

        case 'red' : $color = '31;40'; break;
        case 'green': $color = '32;40'; break;
        case 'yellow': $color = '33;40'; break;
        case 'blue': $color = '34;40'; break;
        case 'pink' : $color = '35;40'; break;
        case 'cyan' : $color = '36;40'; break;
        case 'gray' : $color = '37;40'; break;
        default:;
    };
    return chr(27).'['.$color.$effect.'m'."$text".chr(27).'[0m';
}
?>