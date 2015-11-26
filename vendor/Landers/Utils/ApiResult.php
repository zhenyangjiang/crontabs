<?php
namespace Landers\Utils;

Class ApiResult {
    private $pack;
    public function __construct() {
        $no_data = NULL; $base_code = 1000;
        if (func_num_args() == 1) {
            $arg = func_get_arg(0);
            if ( is_array($arg) || is_object($arg) || is_numeric($arg)) {
                $success = true; $message = ''; $data = &$arg; $code = 0;
            } elseif (is_string($arg)) {
                $success = false; $message = $arg; $data = $no_data; $code = $base_code + 1;
            } else {
                $success = false; $message = '服务器繁忙'; $data = $no_data; $code = $base_code + 101;
            }
        } else {
            if (func_num_args() == 2) {
                list($success, $xvar) = func_get_args();
                if ($success) {
                    $data = &$xvar; $message = ''; $code = 0;
                } else {
                    $data = $no_data;
                    if (is_numeric($xvar)) {
                        $code = &$xvar; $message = '服务器繁忙';
                    } else {
                        $message = $xvar; $code = -1;
                    }
                }
            } elseif (func_num_args() == 3) {
                list($success, $xvar1, $xvar2) = func_get_args();
                $parse = auto_parse_args([$xvar1, $xvar2]);
                $message = $parse['string'];
                if ($success) {
                    unset($parse['string']);
                    $data = pos($parse);
                    $code = 0;
                } else {
                    $data = $no_data;
                    $code = $parse['integer'];
                }
            } elseif (func_num_args() == 4) {
                list($success, $data, $message, $code) = func_get_args();
                if (
                    ($success && $code > 0) ||
                    (!$success && $code == 0)
                ) throw new \Exception('状态与错误代码有冲突');
            }
        }
        $this->pack = [
            'success'       => $success,
            'code'          => $code,
            'data'          => $data,
            'csrf_token'    => function_exists('csrf_token') ? csrf_token() : '',
            'message'       => $message
        ];
    }

    public function get() {
        return $this->pack;
    }

    public function output(){
        echo json_encode($this->pack); exit();
    }

    public static function make() {
        $args = func_get_args();
        switch (func_num_args()) {
            case 1 : $o = new static($args[0]); break;
            case 2 : $o = new static($args[0], $args[1]); break;
            case 3 : $o = new static($args[0], $args[1], $args[2]); break;
            case 4 : $o = new static($args[0], $args[1], $args[2], $args[4]); break;
        }
        return $o->get();
    }
}