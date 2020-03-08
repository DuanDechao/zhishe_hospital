<?php

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\store\StoreProductAttr;
use app\admin\model\store\StoreProductAttrResult;
use app\admin\model\store\StoreProductRelation;
use app\admin\model\system\SystemConfig;
use service\JsonService;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use app\admin\model\store\StoreHospital as HospitalModel;
use think\Url;
use app\admin\model\ump\StoreSeckill as StoreSeckillModel;
use app\admin\model\order\StoreOrder as StoreOrderModel;
use app\admin\model\ump\StoreBargain as StoreBargainModel;
use app\admin\model\system\SystemAttachment;
use think\Log;
use think\Db;


/**
 * 医院管理
 * Class StoreProduct
 * @package app\admin\controller\store
 */
class StoreHospital extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = HospitalModel::class;

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $type=$this->request->param('type');
        //获取分类
        //$this->assign('cate',CategoryModel::getTierList());
        //上线的医院机构
        $onsale =  HospitalModel::where(['is_show'=>1,'is_del'=>0])->count();
        //待上线的医院机构
        $forsale =  HospitalModel::where(['is_show'=>0,'is_del'=>0])->count();
        //仓库中产品
       // $warehouse =  HospitalModel::where(['is_del'=>0])->count();
        //已经售馨产品
       // $outofstock = HospitalModel::getModelObject()->where(HospitalModel::setData(4))->count();
        //警戒库存
       // $policeforce =HospitalModel::getModelObject()->where(HospitalModel::setData(5))->count();
        //回收站
        $recycle =  HospitalModel::where(['is_del'=>1])->count();

        $this->assign(compact('type','onsale','forsale','warehouse','outofstock','policeforce','recycle'));
        return $this->fetch();
    }
    /**
     * 异步查找产品
     *
     * @return json
     */
    public function product_ist(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['store_name',''],
            ['cate_id',''],
            ['excel',0],
            ['type',$this->request->param('type')]
        ]);
        return JsonService::successlayui(HospitalModel::ProductList($where));
    }
    /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=HospitalModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return JsonService::fail($is_show==1 ? '上架失败':'下架失败');
        }
    }
    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(HospitalModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }
    /**
     * 设置批量产品上架
     *
     * @return json
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return JsonService::fail('请选择需要上架的产品');
        }else{
            $res=HospitalModel::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return JsonService::successful('上架成功');
            else
                return JsonService::fail('上架失败');
        }
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
//        $this->assign(['title'=>'添加产品','action'=>Url::build('save'),'rules'=>$this->rules()->getContent()]);
//        return $this->fetch('public/common_form');
		$metalLabels = array();
		$metalPrices = Db::name('MetalPrice')->field('id, name')->select();
		foreach($metalPrices as $levelId => $metalPrice){
			array_push($metalLabels, ['value'=>$metalPrice['id'], 'label'=> $metalPrice['name']]);
		}
        $field = [
            Form::input('store_name','医院名称')->col(Form::col(24)),
            Form::input('store_info','医院简介')->type('textarea'),
            Form::input('keyword','医院关键字')->placeholder('多个用英文状态下的逗号隔开'),
            Form::frameImageOne('image','医院主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('550px')->spin(0),
            Form::frameImages('slider_image','医院轮播图(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('100%')->height('550px')->spin(0),
            Form::select('hospital_type', '医院类型')->setOptions(function(){
                $list = HospitalModel::getHospitalLevelConfig();
                $menus=[];
                foreach ($list as $menu) {
                    $menus[] = ['value' => $menu, 'label'=>$menu];
                }
                return $menus;
            })->filterable(1)->col(8),
            Form::input('city','所在城市')->col(8),
            Form::input('region','所在区')->col(8),
            Form::input('location','具体地址')->col(8),
            Form::number('longitude','经度')->min(0)->precision(2)->col(8),
            Form::number('latitude','纬度')->min(0)->precision(2)->col(8),
            Form::number('sales','预约量')->min(0)->precision(0)->col(8),
            Form::number('ficti','虚拟预约')->min(0)->precision(0)->col(8),
            Form::radio('is_show','医院状态',0)->options([['label'=>'上线','value'=>1],['label'=>'下线','value'=>0]])->col(8),
        ];
        $form = Form::create(Url::build('save'));
        $form->setMethod('post')->setTitle('添加医院')->components($field)->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','store/product/'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,1);
        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            'store_name',
            'store_info',
            'keyword',
            ['image',[]],
            ['slider_image',[]],
			'city',
            'hospital_type',
			'region',
			'location',
			'longitude',
			'latitude',
            'sales',
            'ficti',
            ['is_show',0],
        ],$request);
        if(!$data['store_name']) return Json::fail('请输入产品名称');
        if(!$data['store_info']) return Json::fail('请输入产品简介');
        if(!$data['keyword']) return Json::fail('请输入产品关键字');
        if(count($data['image'])<1) return Json::fail('请上传产品图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传产品轮播图');
        if($data['sales'] == '' || $data['sales'] < 0) return Json::fail('请输入销量');
        if($data['city'] == '') return Json::fail('请输入城市');
        if($data['region'] == '') return Json::fail('请输入地区');
        if($data['location'] == '') return Json::fail('请输入具体地址');
        if($data['longitude'] == 0) return Json::fail('请输入经度');
        if($data['latitude'] == 0) return Json::fail('请输入纬度');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();
        $data['description'] = '';
        HospitalModel::set($data);
        return Json::successful('添加产品成功!');
    }


    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = HospitalModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>HospitalModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = HospitalModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
		$metalLabels = array();
		$metalPrices = Db::name('MetalPrice')->field('id, name')->select();
		foreach($metalPrices as $levelId => $metalPrice){
			array_push($metalLabels, ['value'=>$metalPrice['id'], 'label'=> $metalPrice['name']]);
		}
		$sliderImage = json_decode($product->getData('slider_image'), 1);
		$sliderImage = $sliderImage ? $sliderImage : array();
        $form = Form::create(Url::build('update',array('id'=>$id)),[
            Form::input('store_name','医院名称',$product->getData('store_name')),
            Form::input('store_info','医院简介',$product->getData('store_info'))->type('textarea'),
            Form::input('keyword','关键字',$product->getData('keyword'))->placeholder('多个用英文状态下的逗号隔开'),
            Form::frameImageOne('image','医院主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('100%')->height('550px')->spin(0),
            Form::frameImages('slider_image','医院轮播图(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')), $sliderImage)->maxLength(5)->icon('images')->width('100%')->height('550px')->spin(0),
            Form::select('hospital_type', '医院类型', $product->getData('hospital_type'))->setOptions(function(){
                $list = HospitalModel::getHospitalLevelConfig();
                $menus=[];
                foreach ($list as $menu) {
                    $menus[] = ['value' => $menu, 'label'=>$menu];
                }
                return $menus;
            })->filterable(1)->col(8),
            Form::input('city','所在城市', $product->getData('city'))->col(8),
            Form::input('region','所在区', $product->getData('region'))->col(8),
            Form::input('location','具体地址', $product->getData('location'))->col(8),
            Form::number('longitude','经度', $product->getData('longitude'))->min(0)->precision(2)->col(8),
            Form::number('latitude','纬度', $product->getData('latitude'))->min(0)->precision(2)->col(8),
            Form::number('sales','预约数',$product->getData('sales'))->min(0)->precision(0)->col(8),
            Form::number('ficti','虚拟预约数',$product->getData('ficti'))->min(0)->precision(0)->col(8),
            Form::radio('is_show','产品状态',$product->getData('is_show'))->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
        ]);
        $form->setMethod('post')->setTitle('编辑产品')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }



    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'store_name',
            'store_info',
            'keyword',
            ['image',[]],
            ['slider_image',[]],
            'sales',
            'ficti',
			'hospital_type',
			'city',
			'region',
			'location',
			'longitude',
			'latitude',
            ['is_show',0],
        ],$request);
        if(!$data['store_name']) return Json::fail('请输入医院名称');
        if(!$data['store_info']) return Json::fail('请输入产品简介');
        if(!$data['keyword']) return Json::fail('请输入产品关键字');
        if(count($data['image'])<1) return Json::fail('请上传医院图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传医院轮播图');
        if(count($data['slider_image'])>5) return Json::fail('轮播图最多5张图');
        if($data['sales'] == '' || $data['sales'] < 0) return Json::fail('请输入销量');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        HospitalModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    public function attr($id)
    {
        if(!$id) return $this->failed('数据不存在!');
        $result = StoreProductAttrResult::getResult($id);
        $image = HospitalModel::where('id',$id)->value('image');
        $this->assign(compact('id','result','product','image'));
        return $this->fetch();
    }
    /**
     * 生成属性
     * @param int $id
     */
    public function is_format_attr($id = 0){
        if(!$id) return Json::fail('产品不存在');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $product = HospitalModel::get($id);
        if(!$product) return Json::fail('产品不存在');
        $attrFormat = attrFormat($attr)[1];
        if(count($detail)){
            foreach ($attrFormat as $k=>$v){
                foreach ($detail as $kk=>$vv){
                    if($v['detail'] == $vv['detail']){
                        $attrFormat[$k]['price'] = $vv['price'];
                        $attrFormat[$k]['cost'] = isset($vv['cost']) ? $vv['cost'] : $product['cost'];
                        $attrFormat[$k]['sales'] = $vv['sales'];
                        $attrFormat[$k]['pic'] = $vv['pic'];
                        $attrFormat[$k]['check'] = false;
                        $attrFormat[$k]['metal_diff'] = $vv['metal_diff'];
                        $attrFormat[$k]['metal_weight'] = $vv['metal_weight'];
                        $attrFormat[$k]['price_type'] = $vv['price_type'];
                        break;
                    }else{
                        $attrFormat[$k]['cost'] = $product['cost'];
                        $attrFormat[$k]['price'] = '';
                        $attrFormat[$k]['sales'] = '';
                        $attrFormat[$k]['pic'] = $product['image'];
                        $attrFormat[$k]['check'] = true;
                        $attrFormat[$k]['metal_diff'] = $product['metal_diff'];
                        $attrFormat[$k]['metal_weight'] = $product['metal_weight'];
                        $attrFormat[$k]['price_type'] = $product['price_type'];
                    }
                }
            }
        }else{
            foreach ($attrFormat as $k=>$v){
                $attrFormat[$k]['cost'] = $product['cost'];
                $attrFormat[$k]['price'] = $product['price'];
                $attrFormat[$k]['sales'] = $product['stock'];
                $attrFormat[$k]['pic'] = $product['image'];
                $attrFormat[$k]['check'] = false;
                $attrFormat[$k]['metal_diff'] = $product['metal_diff'];
                $attrFormat[$k]['metal_weight'] = $product['metal_weight'];
                $attrFormat[$k]['price_type'] = $product['price_type'];
            }
        }
        return Json::successful($attrFormat);
    }

    public function set_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $res = StoreProductAttr::createProductAttr($attr,$detail,$id);
        if($res)
            return $this->successful('编辑属性成功!');
        else
            return $this->failed(StoreProductAttr::getErrorInfo());
    }

    public function clear_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        if(false !== StoreProductAttr::clearProductAttr($id) && false !== StoreProductAttrResult::clearResult($id))
            return $this->successful('清空产品属性成功!');
        else
            return $this->failed(StoreProductAttr::getErrorInfo('清空产品属性失败!'));
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $data['is_del'] = 1;
        if(!HospitalModel::edit($data,$id))
            return Json::fail(HospitalModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }




    /**
     * 点赞
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function collect($id){
        if(!$id) return $this->failed('数据不存在');
        $product = HospitalModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(StoreProductRelation::getCollect($id));
        return $this->fetch();
    }

    /**
     * 收藏
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function like($id){
        if(!$id) return $this->failed('数据不存在');
        $product = HospitalModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(StoreProductRelation::getLike($id));
        return $this->fetch();
    }
    /**
     * 修改产品价格
     * @param Request $request
     */
    public function edit_product_price(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['price',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = HospitalModel::edit(['price'=>$data['price']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }

    /**
     * 修改产品库存
     * @param Request $request
     */
    public function edit_product_stock(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['stock',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = HospitalModel::edit(['stock'=>$data['stock']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }



}
