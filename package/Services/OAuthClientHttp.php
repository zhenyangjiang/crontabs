<?php
namespace Services;

use Landers\Substrate\Utils\Http;
use Landers\Framework\Services\OAuthHttp;
use Landers\Substrate\Traits\MakeInstance;
use Landers\Substrate\Classes\ApiResult;

Class OAuthClientHttp {

    private static $oauthHttp;

    public static function init() {
        $config = config('oauth');
        $apiurl = $config['apiurl'];
        $client_id = $config['client_id'];
        $client_secret = $config['client_secret'];
        self::$oauthHttp = new OAuthHttp($apiurl, 'client_credentials', $client_id, $client_secret );
    }

    public static function parse($ret) {
        if ( !$ret ) return false;

        if ( is_numeric($ret)) {
            return (float)$ret;
        }

        if (is_string($ret)) {
            if ( $ret === 'true' ) return true;
            $ret = json_decode($ret, true);
            if ( !$ret ) return false;
        }

        if (is_bool($ret)) {
            return ApiResult::make()->bool();
        }

        if (is_array($ret)) {
            return (object)$ret;
        }

        if (is_object($ret)) {
            return $ret;
        }
    }

    public static function post($url, Array $data = [] ) {
        return self::$oauthHttp->post($url, $data);
    }
}
OAuthClientHttp::init();