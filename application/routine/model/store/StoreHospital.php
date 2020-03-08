<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/12
 */

namespace app\routine\model\store;

use think\Log;
use app\admin\model\store\StoreProductAttrValue as StoreProductAttrValuemodel;
use basic\ModelBasic;
use traits\ModelTrait;
use service\SystemConfigService;

class StoreHospital extends ModelBasic
{
    use  ModelTrait;

    protected function getSliderImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

	public static function getValidProduct($productId,$field = '*')
    {
		$item = self::where('is_del',0)->where('is_show',1)->where('id',$productId)->field($field)->find();
		//$attrStock = self::getStock($item['id']);
		//$attrSales = self::getSales($item['id']);
        //$item['stock'] = $attrStock>0?$attrStock:$item['stock'];//库存
        return $item;
    }
    //获取库存数量
    public static function getStock($productId)
    {
        return StoreProductAttr::storeProductAttrValueDb()->where(['product_id'=>$productId])->sum('stock');
    }
    //获取总销量
    public static function getSales($productId)
    {
        return StoreProductAttr::storeProductAttrValueDb()->where(['product_id'=>$productId])->sum('sales');
    }

	public static function getName($productId)
	{
		return self::where('id', $productId)->value('store_name');
	}

    public static function getBaseInfo($productId)
    {
        return self::where('id', $productId)->field('store_name, latitude, longitude')->select()->toArray();
    }

    public static function validWhere()
    {
        return self::where('is_del',0)->where('is_show',1);
    }

    /**
     * 新品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getNewProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_new',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }


    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_hot',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotProductLoading($field = '*',$offset = 0,$limit = 0)
    {
        $model = self::where('is_hot',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($offset,$limit);
        return $model->select();
    }

    /**
     * 精品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBestProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_best',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }


    /**
     * 优惠产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBenefitProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_benefit',1)
            ->where('is_del',0)->where('mer_id',0)->where('stock','>',0)
            ->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
		return $model->select()->each(function($e){
			$attrStock = self::getStock($e['id']);
			if($attrStock > 0) $e['stock'] = $attrStock;
		});
		//for($res as $key => $val){
		//	$res[$key]['stock'] = self::getStock($val['id']);
		//}
		//return $res;
    }

    public static function cateIdBySimilarityProduct($cateId,$field='*',$limit = 0)
    {
        $pid = StoreCategory::cateIdByPid($cateId)?:$cateId;
        $cateList = StoreCategory::pidByCategory($pid,'id') ?:[];
        $cid = [$pid];
        foreach ($cateList as $cate){
            $cid[] = $cate['id'];
        }
        $model = self::where('cate_id','IN',$cid)->where('is_show',1)->where('is_del',0)
            ->field($field)->order('sort DESC,id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function isValidProduct($productId)
    {
        return self::be(['id'=>$productId,'is_del'=>0,'is_show'=>1]) > 0;
    }

    public static function getProductStock($productId,$uniqueId = '')
    {
        return  $uniqueId == '' ?
            self::where('id',$productId)->value('stock')?:0
            : StoreProductAttr::uniqueByStock($uniqueId);
    }

    public static function decProductStock($num,$productId,$unique = '')
    {
        if($unique){
            $res = false !== StoreProductAttrValuemodel::decProductAttrStock($productId,$unique,$num);
            $res = $res && self::where('id',$productId)->setInc('sales',$num);
        }else{
            $res = false !== self::where('id',$productId)->dec('stock',$num)->inc('sales',$num)->update();
        }
        return $res;
    }

	public static function incProductStock($num,$productId,$unique = '')
    {
        if($unique){
            $res = false !== StoreProductAttrValuemodel::incProductAttrStock($productId,$unique,$num);
            //$res = $res && self::where('id',$productId)->setInc('sales',$num);
        }else{
            $res = false !== self::where('id',$productId)->inc('stock',$num)->dec('sales',$num)->update();
        }
        return $res;
    }

	public static function getHospitalLevelConfig()
	{
		$hospitalLevelConfig = SystemConfigService::get('hospital_level_config');
		$hospitalLevelConfig = str_replace("\r\n", "\n", $hospitalLevelConfig);
		$hospitalLevels = explode("\n", $hospitalLevelConfig);
		$res = [];
		foreach($hospitalLevels as $level)
		{
			$items = explode("=", $level);
			if(count($items) == 2)
				$res[$items[0]] = $items[1];
		}
		return $res;
	}

    public static function getProductTypesConfig()
	{
		$productTypesConfig = SystemConfigService::get('product_type_config');
		$productTypesConfig = str_replace("\r\n", "\n", $productTypesConfig);
		$productTypes = explode("\n", $productTypesConfig);
		$res = [];
		foreach($productTypes as $type)
		{
			$items = explode("=", $type);
			if(count($items) == 2)
				$res[$items[0]] = $items[1];
		}
		return $res;
	}
}
