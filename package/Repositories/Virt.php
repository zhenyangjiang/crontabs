<?php

use ULan\Virt\Admin;
use ULan\Virt\EndUser;

class Virt {
    private static $IP = '172.31.51.1';
    private static $admin;
    private static $enduser;

    private static function instance($method) {
        if ( in_array($method, ['suspend', 'unsuspend', 'delete_vs']) ) {
            $key = '3vxanrl6c1g0kpo0fyvgu2xoik04p5zo';
            $pass = '9p6ucbfgwr3jdzumhqsjbidimtndsb8f';
            self::$admin or self::$admin = new Admin(self::$IP, $key, $pass);
            return self::$admin;
        } else {
            $key =  '5XDK5O2OUIPJEYSZ';
            $pass = '6btepv9c8beck1aclxe951sadgc8mxjh';
            self::$enduser or self::$enduser = new EndUser(self::$IP, $key, $pass);
            return self::$enduser;
        }
    }

    public static function __callStatic($method, $args) {
        $virt = self::instance($method);
        return call_user_func_array(array($virt, $method), $args);
    }
}