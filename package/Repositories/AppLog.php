<?php
use Landers\Framework\Modules\Log;
use Landers\Framework\Core\System;

class AppLog extends Log {
    protected $path;

    public function __construct() {
        $this->path = System::app('path').'/logs/';
    }
}
