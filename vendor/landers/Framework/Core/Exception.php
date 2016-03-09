<?php
namespace Landers\Framework\Core;

class Exception extends \Exception{
    protected function log() {
        echo 'saving log...';
    }
}