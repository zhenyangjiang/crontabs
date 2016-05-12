<?php

//确保大网安全
Class MakeSafe {
    private static function calcTotalByGroup($group) {
        $ret = 0;
        foreach ($group as $item) {
            $ret += $item['bps0'];
        }
        return $ret;
    }

    private static function getDataCenterMaxDefend($group_id) {
        return 200 * 1000;
    }

    public static function filte($attack_pack) {
        foreach ($attack_pack as $group_id => $group) {
            $total = self::calcTotalByGroup($group);
            $max = self::getDataCenterMaxDefend($group_id);
            if ($total >= $max) {

            }
        }
    }

    public static function policy($group) {

    }
}