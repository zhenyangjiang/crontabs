<?php

/**
 * Created by PhpStorm.
 * User: gbf
 * Date: 2016/10/25
 * Time: 16:25
 */
class free
{
    public $group,$total_mbps,$max_mbps,$mitigation;
    public $valid = array();
    public $month_free_ips =array();
    public function __construct($group,$total_mbps,$max_mbps)
    {
        $this->group      = $group;
        $this->total_mbps = $total_mbps;
        $this->max_mbps   = $max_mbps;
        Response::noteColor('yellow', '总流量%s >= 本组最大防护%s，大网遭受威胁，需对 “包月且免费” 优先牵引....', $total_mbps, $max_mbps);

    }
    public function handle()
    {
        if (!$group) dp($group);
        $group = &$this->group;
        foreach ($group as $dest_ip => $item) {
            //读取云盾表中该ip的云盾配置
            $this->mitigation = $item['mitigation'];
            //是否免费版云盾
            $this->vaild['is_free'] = (float)$mitigation['price'] == 0;
            //是否包月
            $this->vaild['is_month']  = $mitigation['billing'] == 'month';

            $this->doBlock($group,$dest_ip,$item);
        }
    }
    private function  doBlock($group,$dest_ip,$item)
    {
        //包月且免费，立即牵引
        $total_mbps = $this->total_mbps;
        $max_mbps = $this->max_mbps;
        $is_month   = $this->vaild['is_month'];
        $is_free    = $this->vaild['is_free'];
        $mitigation = $this->mitigation;
        if ( $is_month && $is_free ) {
            Response::note('#line');
            $this->month_free_ips[] = $dest_ip;
            //按月计费：仅防护，由ExpireHandler进行到期扣取次月
            Response::note(
                'IP：%s，计费方案：按月计费，防护阈值：%sMbps / %spps',
                $dest_ip, $mitigation['ability_mbps'], $mitigation['ability_pps']
            );

            Response::note('当前攻击速率：%sMbps，攻击报文：%spps', $item['mbps'], $item['pps']);

            if (BlackHole::block($dest_ip, $item['mbps'], 'force')) {
                Alert::ipBlock($dest_ip, [
                    'reason' => '超大网安全'
                ]);

                //从总攻击量中减掉此项，并把此ip从该组移除
                $total_mbps -= $item['mbps'];
                unset($group[$dest_ip]);
            }
        }
    }
    public function  __destruct()
    {
        Response::note('#line');
        Response::noteColor('green', '共计 %s 个“包月且免费IP” 牵引完成', count($month_free_ips));
    }
}