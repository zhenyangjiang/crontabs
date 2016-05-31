<?php
$app_path = __DIR__ . '/../../';
$file2 = include ($app_path . 'ExpireHandler/config/notify-content.php');
$file3 = include ($app_path . 'ExpireRemind/config/notify-content.php');

return array_merge($file2, $file3);
?>