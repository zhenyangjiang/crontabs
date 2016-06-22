<?php
use Landers\Substrate\Utils\Http;
use Landers\Framework\Services\OAuthService;
use Landers\Substrate\Traits\MakeInstance;
use Landers\Framework\Core\Config;
use Landers\Substrate\Classes\ApiResult;

Class OAuthHttp {

    private static $config;
    private static $oauth;
    const HTTP_RETRY_COUNT = 2;

    public static function init() {
        self::$config = Config::get('oauth');
    }

    private static function oauth() {
        $oauth = &self::$oauth;
        $apiurl = self::$config['apiurl'];
        $oauth or $oauth = new OAuthService( $apiurl );
        return $oauth;
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

    public static function post( $url, $data, Array $header = [] ) {
        $client_id = self::$config['client_id'];
        $client_secret = self::$config['client_secret'];
        $auth_header = self::oauth()->getAccessTokenHeader($client_id, $client_secret);
        $content = Http::post($url, $data, [
            'header' => array_merge($auth_header, $header, [
                'X-Requested-With' => 'XMLHttpRequest'
            ])
        ]);
        if (is_array($content)) {
            return $ret;
        } else {
            if ($content) {
                $ret = json_decode($content, true);
            } else {
                $ret = NULL;
            }
            $ret or $ret = [
                'error' => 'invalid_access',
                'message' => 'cannot connect remote host',
                'status_code' => 400,
                'success' => false,
            ];

            if ( $ret['error'] == 'invalid_request' ||
                 $ret['error'] == 'invalid_token' ||
                 $ret['error'] == 'access_denied'
            ) {
                $auth_header = self::oauth()->getAccessTokenHeader($client_id, $client_secret, true);
                return self::post( $url, $data, $auth_header);
            } else {
                return $ret;
            }

        }



    }
}
OAuthHttp::init();