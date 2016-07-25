<?php
use Landers\Framework\Core\StaticRepository;
use Landers\Framework\Core\Config;
use Services\OAuthClientHttp;

class FirewallRule extends StaticRepository {
    protected static $connection = 'main';
    protected static $datatable  = 'ulan_firewall_rules';
    protected static $DAO;

    public static function apiurl( $path ) {
        return Config::get('hosts', 'api') . '/intranet/firewallrule' . $path;
    }

    public static function delete($awhere) {
        $lists = parent::lists([
            'awhere' => $awhere,
            'fields' => 'param_desc, uid',
        ]);

        $apiurl = self::apiurl('/deletefwrule');
        foreach ( $lists as $item ) {
            $ret = OAuthClientHttp::post($apiurl, $item);
            if ( !$ret['success'] ) {
                reportDevException('删除防火强规失败！', [
                    'ruleData' => $item
                ]);
            }
        }

        //删除记录
        return parent::delete($awhere);
    }
}
FirewallRule::init();
?>