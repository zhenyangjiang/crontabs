<?php
use Landers\Framework\Core\Config;
use Landers\Framework\Core\System;

//print_r($_POST); exit(PHP_EOL);
require (__DIR__.'/../../../web');
Config::init(__DIR__.'/../config/');

$pack = $_POST;
dp($pack);
$bool = ResourceUsage::import($pack);
output_api_result($bool);
