<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace app\admin\model\ump;

use app\admin\model\order\StoreOrder;
use app\admin\model\store\StoreProductRelation;
use app\admin\model\system\SystemConfig;
use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\store\StoreProduct;
use service\PHPExcelService;

/**
 * Class StoreSeckill
 * @package app\admin\model\store
 */
class StoreDuobaoCode extends ModelBasic
{
    use ModelTrait;
    public static function initDuobaoCodes($duobaoId, $term, $total){
		$base_code = 100000000; //原始数
		$codeArr = range($base_code +1, $base_code + $total);
		shuffle($codeArr);
		foreach($codeArr as $code){
			$res = self::set([
				'duobao_id'=>$duobaoId,
				'term'=>$term,
				'code'=>$code,
			]);
			if(!$res) return false;
		}
		return true;
    }

	public static function buyDuobaoCode($duobaoId, $term, $uid, $count){

	}
}
