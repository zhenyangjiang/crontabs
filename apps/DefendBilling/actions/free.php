<?php
use Landers\Framework\Core\Response;
/**
 * Created by PhpStorm.
 * User: gbf
 * Date: 2016/10/25
 * Time: 16:25
 */
class free
{
    public $mitigations,$total_mbps,$max_mbps,$mitigation;
    public $valid          = array();
    public $month_free_ips = array();
    public $blocked_count  =  array();
    public  static  function handles(&$mitigations,$total_mbps=3,$max_mbps=1)
    {
        $instance =new self;
        $instance->blocked_count    = ['mitigation' => 0, 'ip' => 0];
        $instance->mitigations      = $mitigations;
        $instance->total_mbps       = $total_mbps;
        $instance->max_mbps         = $max_mbps;
        Response::noteColor('yellow', '总流量%s >= 本组最大防护%s，大网遭受威胁，需对 “包月且免费” 优先牵引....', $total_mbps, $max_mbps);
        $instance->handle();
    }
    public function handle()
    {

        $mitigations   = &$this->mitigations;

        $blocked_count = &$this->blocked_count;

        foreach ($mitigations as $index => $mitigation) {
            //是否免费版云盾
            $this->valid['is_free'] = (float)$mitigation['price'] == 0;

            //是否包月
            $this->valid['month'] = $mitigation['billing'] == 'month';

            //包月且免费，立即牵引
            $this->doBlock($mitigations,$mitigation,$blocked_count,$index);

        }

    }
    private function doBlock($mitigations,$mitigation,$blocked_count,$index)
    {

        //包月且免费，立即牵引
        if ( $this->valid['is_free'] && $this->valid['month'] ) {
            Response::note('#line');
            $blocked_count['mitigation']++;
            //显示云盾及IP攻击详细
            response_mitigation_detail($mitigation);

            Mitigation::block($mitigation, '超大网安全', function($item) use (&$blocked_count, &$total_mbps) {
                $blocked_count['ip']++;
                $total_mbps -= $item['mbps'];
            });

            //从总攻击量中减掉此项，并把此ip从该组移除
            $this->total_mbps -= $mitigation['sum_mbps'];

            //此云盾中的所有IP均被牵引了，将此云盾从数组中移除
            unset($mitigations[$index]);
        }
    }
    private  function end()
    {

        Response::note('#line');
        Response::reply('共计 %s 个云盾（含 %s 个“包月且免费IP”） 牵引完成', $blocked_count['mitigation'], $blocked_count['ip']);
        Response::note('#blank');
    }

}