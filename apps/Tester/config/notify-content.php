<?php
$app_path = __DIR__ . '/../../';
$file1 = include ($app_path . 'ExpireHandler/config/notify-content.php');
$file2 = include ($app_path . 'ExpireRemind/config/notify-content.php');
$file3 = include ($app_path . 'DefendBilling/config/notify-content.php');

return array_merge($file1, $file2, $file3);
?>