<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/25
 */

namespace app\routine\model\address;


use basic\ModelBasic;
use traits\ModelTrait;

class AddressCity extends ModelBasic
{
    use ModelTrait;

    public static function setCityInfo($insertData)
    {
        self::beginTrans();
        $res = self::set($insertData);
        self::checkTrans($res);
        return $res;
    }

    public static function getCityList() {
        return self::select()->toArray();
    }

    //public static function getUserDefaultAddress($uid,$field = '*')
    //public{
    //public    return self::userValidAddressWhere()->where('uid',$uid)->where('is_default',1)->field($field)->find();
    //}
}
