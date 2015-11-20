#!/usr/bin/env php
<?php
set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('PRC');
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
define('ENV_DEBUG_console'      , true);
define('ENV_charset'            , 'utf-8');
define('ENV_system_name'        , 'ÓÅÀ¶¿Æ¼¼');
define('ENV_sleep_time'         , 5);
ini_set('default_charset', ENV_charset);

header('content-type:text/html; charset=' . ENV_charset);
require_once __DIR__ . '/vendor/autoload.php';
\Landers\Framework\Core\System::init('LCLI');
?>