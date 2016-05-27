<?php
class VirtTop {
    public static function getData(&$error = NULL){
        $arr = self::get($error);
        if (!$arr) return false;
        $str = implode("\n", $arr);
        $speed = self::getNetWorkSpeed();
        $ret = self::parse($str, $speed, $error);
        if (!$ret) return false;
        return $ret;
    }

    private static function get(&$error = NULL){
        $command1 = 'virt-top -n 2 -d 1 --stream';
        exec($command1, $output, $return);
        if ($return) {
            $error = 'Error Code: '.$return;
            return false;
        } else {
            return $output;
        }
    }

    private static function parse($str, $speed, &$error = NULL) {
        if (!$str) {
            $error = '空数据包！';
            return false;
        }
        $ret = preg_split('/virt-top/is', $str);
        if (!$ret) {
            $error = '数据包解析错误！';
            return false;
        }
        $ret = explode("\n", $ret[2]);
        array_shift($ret);
        $keys = preg_split('/\s/', preg_replace(array('/\s{2,}/', '/\%/'), array(' ',''), pos($ret)));
        array_shift($ret); array_shift($keys);
        foreach ($ret as $i => &$item) {
            $item = preg_split('/\s/', preg_replace('/\s{2,}/ ', ' ', $item));
            array_shift($item);
            $item = array_combine($keys, $item);
            $item['TXBY'] = $speed[$item['NAME']]['RXBY'];
            $item['RXBY'] = $speed[$item['NAME']]['TXBY'];
            unset($item['ID']); unset($item['S']);
        }
        return $ret;
    }

    private static function getNetWorkInfo()
    {
		$dev = file('/proc/net/dev');

		$devices = array();

		foreach ($dev as $k => $x) {
			$_dev = preg_split('/[\\s]+/', str_replace(':', ': ', trim($x)));
			$_dev[0] = trim($_dev[0], ':');

			foreach ($_dev as $kk => $vv) {
				$tmp[$kk] = trim($vv);
			}

			// $devices[$tmp[0]] = $tmp;
			if (strpos($tmp[0], "viif") !== false) {
                $vps = str_replace("viif", "", $tmp[0]);
    			$devices[$vps] = [
                    'RXBY' => $tmp[1],
                    'TXBY' => $tmp[9],
                ];
			}

		}

        return $devices;
    }

    public static function getNetWorkSpeed()
    {
        $data = array();
        $info1 = self::getNetWorkInfo();
        usleep(1000000);
        $info2 = self::getNetWorkInfo();
        foreach ($info1 as $k => $v) {
            $data[$k] = [
                'RXBY' => $info2[$k]['RXBY'] - $v['RXBY'],
                'TXBY' => $info2[$k]['TXBY'] - $v['TXBY'],
            ];
        }

        return $data;
    }
}

// $r = VirtTop::getData();
// // $r = VirtTop::getNetWorkSpeed();
// print_r($r);
?>
