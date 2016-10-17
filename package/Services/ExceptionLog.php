<?php
namespace Services;

class ExceptionLog {
    public static function handle($message, $e) {
        $class = get_class($e);
        \AppLog::error($message, $_SERVER);
    }
}