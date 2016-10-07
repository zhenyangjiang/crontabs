#!/usr/bin/env php
<?php
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('PRC');
set_time_limit(0);

$_SERVER['DOCUMENT_ROOT'] = __DIR__;
define('ENV_charset'      , 'utf-8');
ini_set('default_charset' , ENV_charset);

require_once __DIR__ . '/vendor/autoload.php';

\Landers\Framework\Core\System::init('LCLI');
?>