<?php
namespace Landers\Utils;

Class ApiResult {
    private $pack;
    public function __construct() {
        $no_data = NULL;
        if (func_num_args() == 1) {
            $arg = func_get_arg(0);
            if ( is_array($arg) || is_object($arg) ) {
                $status = 'OK'; $message = ''; $data = &$arg;
            } elseif (is_string($arg)) {
                $status = 'ERROR'; $message = $arg; $data = $no_data;
            } else {
                $status = 'ERROR'; $message = '服务器繁忙'; $data = $no_data;
            }
        } else {
            if (func_num_args() == 2) {
                list($status, $xvar1) = func_get_args();
            } else {
                list($status, $xvar1, $xvar2) = func_get_args();
            }
            if (isset($xvar1) && isset($xvar2)) {
                $data = &$xvar1; $message = &$xvar2;
            } else {
                if ($status) {
                    $data = &$xvar1;
                    $message = '';
                } else {
                    $message = &$xvar1;
                    $data = $no_data;
                }
            }
        }
        $this->pack = [
            'status' => $status,
            'data'   => $data,
            'message'=> $message
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
        }
        return $o->get();
    }
}