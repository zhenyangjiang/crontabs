<?php
use Landers\Framework\Core\Config;

return [
    'apiurl' => Config::get('hosts', 'api') . '/auth/login',
    'client_id' => 'DEMO_CLIENT_ID',
    'client_secret' => 'DEMO_CLIENT_SECRET',
];