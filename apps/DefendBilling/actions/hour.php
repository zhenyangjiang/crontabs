<?php

/**
 * Created by PhpStorm.
 * User: gbf
 * Date: 2016/10/29
 * Time: 10:50
 */
class hour
{
    public static $total_mbps,$max_mbps,$dest_ip;
    public static  function  handle($total_mbps,$max_mbps,$dest_ip)
    {
        self::$total_mbps = $total_mbps;
        self::$max_mbps   = $max_mbps;
        self::$dest_ip    = $dest_ip;

    }
    public static function checkNetSafe()
    {
        $total_mbps =  self::$total_mbps;
        $max_mbps   =  self::$max_mbps;
        $dest_ip    =  self::$dest_ip ;
        //经过不断对 $total_mbps 做减算，还是超过了最高防护，继续牵引
        $text = sprintf('当前总流量 %s >= 大网安全流量 %s，超大网安全，需立即强制牵引 >>>', $total_mbps, $max_mbps);
        Response::note(colorize($text, 'yellow',  'flash'));

        // 在本次牵引之前，此ip是否被牵引了
        $block_exists = BlackHole::exists($dest_ip);

        //强制牵引
        if (IPBase::block($dest_ip, $item['mbps'], 'force')) {
            $total_mbps -= $item['mbps'];
            Alert::ipBlock($dest_ip, [
                'reason' => '超大网安全且被攻击速率过高'
            ]);
        }

        //计费扣费
        Response::note('对此云盾IP进行结算费用：');
        if ( $block_exists ) {
            Response::reply(' 在本次牵引之前，此IP就已被牵引了，无需再计费...');
        } else {
            if ( Mitigation::isTrial($mitigation) ) {
                Response::echoWarn('试用期云盾免计费。');
            } else {
                //当前攻击是否超过用户购买的最高防护能力
                if ($item['mbps'] > $ability_mbps || $item['pps'] > $ability_pps) {
                    $text = '当前攻击已超过用户购买防护阈值，按用户购买值计算';
                    Response::note($text);
                    DDoSHistory::billing($uid, $DDoSHistory, $price_rules, $ability_mbps);
                } else {
                    $text = '当前攻击未超过用户购买防护阈值，按实际峰值计算';
                    DDoSHistory::billing($uid, $DDoSHistory, $price_rules);
                }
            }
        }

    }
}