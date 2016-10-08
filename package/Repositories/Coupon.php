<?php
namespace App\Repositories;

use Landers\Laravel\Bases\Repositories\StaticRepository;
use App\Models\CouponModel;
use Yzz\LaravelBase\Services\Search;
use Landers\Laravel\Bases\Exceptions\iException;

class Coupon extends StaticRepository
{
    use Search;

    public static $DAO = CouponModel::class;

    /**
     * 取得对应的面额
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function money($key_id, $purpose, $is_check)
    {
        if (!$key_id) return 0;
        $model = self::findBy($key_id);
        if ($is_check) {
            if ( !$model ) {
                throw new iException('无效代金券！' . $key_id);
            }
            if ( $model->status ) {
                throw new iException('代金券已失效！');
            }

            if ( $model->expire <= time() ) {
                throw new iException('代金券已过期！');
            }

            if ( $model->purpose != strtoupper($purpose) ) {
                throw new iException('代金券使用不当！');
            }

            return (float)$model->money;
        } else {
            return $model ? (float)$model->money : 0;
        }
    }

    /**
     * 用掉代金券
     * @param  [type] $key_id [description]
     * @return [type]         [description]
     */
    public static function used($key_id)
    {
        if (!$key_id) return true;

        if ($model = self::findBy($key_id)) {
            $model->status = 1;
            return $model->save();
        } else {
            return false;
        }
    }

    /**
     * 对用户来说获得某张代金券
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public static function obtain($key)
    {
        $coupon = self::where('uid', 0)->where('status', 0)->where('key', $key)->first();
        if ($coupon) {
            $coupon->uid = oauth()->id;
            $coupon->update();
            return $coupon;
        } else {
            throw new \Exception('优惠码不存在或已经使用');
        }
    }
}
