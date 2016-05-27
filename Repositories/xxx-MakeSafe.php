<?php
use Landers\Framework\Core\Response;
use Landers\Substrate\Utils\Arr;

//确保大网安全
Class MakeSafe {
    /**
     * 计算组中的攻击量总和
     * @param  [type] $group [description]
     * @return [type]        [description]
     */
    private static function calcTotalByGroup($group) {
        $ret = 0;
        foreach ($group as $item) {
            $ret += $item['bps0'];
        }
        return $ret;
    }

    /**
     * 取得数据中心的最高防护值
     * @param  [type] $group_id [description]
     * @return [type]           [description]
     */
    private static function getDataCenterMaxDefend($fw_group_id) {
        $datacenter = DataCenter::find([
            'fw_group_id' => $fw_group_id
        ]);
        if ( $datacenter ) {
            return $datacenter['max_mbps'];
        } else {
            $ret = 0;
            Response::warn('未找到 fw_group_id=%s 的数据中心，因此无法确定其对应的最大防护值，暂返回 %s', $fw_group_id, $ret);
            return $ret;
        }
    }

    /**
     * 检查攻击
     * @param  [type] $attack_pack [description]
     * @return [type]              [description]
     */
    public static function check($attack_pack) {
        foreach ($attack_pack as $fw_group_id => &$group) {
            $total = self::calcTotalByGroup($group);
            $max = self::getDataCenterMaxDefend($fw_group_id);
            Response::note('当前组[%s]的最高防护值为：%s', $fw_group_id, $max);
            Response::note('当前组的攻击总量为：%s', $total);
            Response::note('当前组的总攻击总已超总防护值，需要作策略牵引...');
            if ($total >= $max) {
                //牵引掉免费防护的IP
                Response::note('#tab牵引掉免费防护的IP');
                $ips = array_keys($group);
                $ips = Mitigation::filteFree($ips);
                if ($ips) {
                    foreach ($ips as $ip) {
                        BlackHole::doBlock($ip, $group[$ip]['bps0']);
                        unset($group[$ip]);
                    }
                }

                //如果还是超出依次牵引从大到小
                $total = self::calcTotalByGroup($group);
                $group = Arr::sort($group, 'bps0');
                if ($total > $max) {
                    Response::note('#tab依次牵引攻击量大到小，直至正常...');
                    while ($total > $max) {
                        $ip = key($group);
                        Response::note('#tab牵引IP：%s', $ip);
                        BlackHole::doBlock($ip, $group[$ip]['bps0']);
                        unset($group[$ip]);
                        $total = self::calcTotalByGroup($group);
                    }
                    Response::note('#tab牵引完毕', $ip);
                } else {
                    Response::note('#tab免费防护的IP牵引后，剩余攻击量正常');
                }
            }
        }
        return $attack_pack;
    }

    /**
     * 对组中攻击根据最高防护值进行策略牵引
     * @param  [type] $group [description]
     * @return [type]        [description]
     */
    // $group = self::BlockFreeByGroup($group, $max);
    // public static function BlockFreeByGroup($group, $max) {
    //     $ips = array_keys($group);
    //     $ips = Mitigation::filteFree($ips);
    //     if ($ips) {
    //         foreach ($ips as $ip) {
    //             BlackHole::doBlock($ip, $group[$ip]['bps0']);
    //             unset($group[$ip]);
    //         }
    //     }
    //     return $group;
    // }
}